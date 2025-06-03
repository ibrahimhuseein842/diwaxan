<?php
require_once 'config.php';
checkLogin();

// Only start session if one isn't already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize message variables
$success_message = $_SESSION['success_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;

// Clear session messages after retrieving them
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);

// Add this at the very top to handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    
    $action = $_POST['action'] ?? '';
    $response = ['success' => false, 'error' => 'هەڵەیەک ڕوویدا'];
    
    try {
        switch ($action) {
            case 'add_youtube':
            case 'add_instagram':
            case 'add_tiktok':
                $title = sanitize($_POST['title'] ?? '');
                $url = sanitize($_POST['url'] ?? '');
                $type = str_replace('add_', '', $action);
                $table = $type . '_posts';
                
                if (!empty($title) && !empty($url) && isset($_FILES['image'])) {
                    $image_path = uploadFile($_FILES['image'], $type);
                    if ($image_path) {
                        $stmt = $pdo->prepare("INSERT INTO $table (title, image_path, " . ($type === 'youtube' ? 'video_url' : 'post_url') . ") VALUES (?, ?, ?)");
                        if ($stmt->execute([$title, $image_path, $url])) {
                            $response = [
                                'success' => true,
                                'message' => "پۆستی $type بە سەرکەوتووی زیادکرا!"
                            ];
                        }
                    }
                }
                break;
                
            case 'add_activity':
                $title = sanitize($_POST['title'] ?? '');
                $description = sanitize($_POST['description'] ?? '');
                $url = sanitize($_POST['url'] ?? '');
                
                if (!empty($title) && isset($_FILES['image'])) {
                    $image_path = uploadFile($_FILES['image'], 'activity');
                    if ($image_path) {
                        $stmt = $pdo->prepare("INSERT INTO activities (title, description, image_path, activity_url) VALUES (?, ?, ?, ?)");
                        if ($stmt->execute([$title, $description, $image_path, $url])) {
                            $response = [
                                'success' => true,
                                'message' => 'چالاکی بە سەرکەوتووی زیادکرا!'
                            ];
                        }
                    }
                }
                break;
                
            case 'add_sponsor':
                $company_name = sanitize($_POST['company_name'] ?? '');
                $website_url = sanitize($_POST['website_url'] ?? '');
                
                if (!empty($company_name) && isset($_FILES['logo'])) {
                    $logo_path = uploadFile($_FILES['logo'], 'sponsor');
                    if ($logo_path) {
                        $stmt = $pdo->prepare("INSERT INTO sponsors (company_name, logo_path, website_url) VALUES (?, ?, ?)");
                        if ($stmt->execute([$company_name, $logo_path, $website_url])) {
                            $response = [
                                'success' => true,
                                'message' => 'سپۆنسەر بە سەرکەوتووی زیادکرا!'
                            ];
                        }
                    }
                }
                break;
                
            case 'add_admin':
                if (isSuperAdmin()) {
                    $username = sanitize($_POST['username'] ?? '');
                    $password = $_POST['password'] ?? '';
                    $role = sanitize($_POST['role'] ?? 'admin');
                    
                    if (!empty($username) && !empty($password)) {
                        // Check if username already exists
                        $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ?");
                        $stmt->execute([$username]);
                        
                        if (!$stmt->fetch()) {
                            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                            $stmt = $pdo->prepare("INSERT INTO admins (username, password, role) VALUES (?, ?, ?)");
                            if ($stmt->execute([$username, $hashed_password, $role])) {
                                $response = [
                                    'success' => true,
                                    'message' => 'ئەdmینی نوێ بە سەرکەوتووی زیادکرا!'
                                ];
                            }
                        } else {
                            $response = ['success' => false, 'error' => 'ئەم ناوی بەکارهێنەرە پێشتر بەکارهاتووە!'];
                        }
                    }
                }
                break;
                
            case 'delete_item':
                $table = sanitize($_POST['table'] ?? '');
                $id = (int)($_POST['id'] ?? 0);
                
                $allowed_tables = ['youtube_posts', 'instagram_posts', 'tiktok_posts', 'activities', 'sponsors'];
                if (in_array($table, $allowed_tables) && $id > 0) {
                    // Get image path before deletion for cleanup
                    $image_column = ($table === 'sponsors') ? 'logo_path' : 'image_path';
                    $stmt = $pdo->prepare("SELECT $image_column FROM $table WHERE id = ?");
                    $stmt->execute([$id]);
                    $item = $stmt->fetch();
                    
                    if ($item) {
                        // Delete from database
                        $stmt = $pdo->prepare("DELETE FROM $table WHERE id = ?");
                        if ($stmt->execute([$id])) {
                            // Delete image file
                            deleteFile($item[$image_column]);
                            
                            $response = [
                                'success' => true,
                                'message' => 'بڕگە بە سەرکەوتووی سڕایەوە!'
                            ];
                        } else {
                            $response = ['success' => false, 'error' => 'هەڵەیەک لە سڕینەوەدا ڕوویدا!'];
                        }
                    } else {
                        $response = ['success' => false, 'error' => 'بڕگەکە نەدۆزرایەوە!'];
                    }
                } else {
                    $response = ['success' => false, 'error' => 'داواکارییەکە نادروستە!'];
                }
                break;
    
            // هەروەها بۆ delete_prediction-یش JSON response زیادبکە:
            case 'delete_prediction':
                $id = (int)($_POST['id'] ?? 0);
                
                try {
                    if ($id > 0) {
                        $stmt = $pdo->prepare("DELETE FROM guest_predictions WHERE id = ?");
                        if ($stmt->execute([$id])) {
                            echo json_encode([
                                'success' => true,
                                'message' => 'پێشبینیەکە بە سەرکەوتووی سڕایەوە'
                            ]);
                            exit;
                        }
                    }
                    
                    throw new Exception('هەڵەیەک ڕوویدا لە سڕینەوەی پێشبینیەکە');
                } catch (Exception $e) {
                    echo json_encode([
                        'success' => false,
                        'error' => $e->getMessage()
                    ]);
                    exit;
                }
                break;
    
            // بۆ update_prediction-یش:
            case 'update_prediction':
                $id = (int)($_POST['id'] ?? 0);
                $status = sanitize($_POST['status'] ?? '');
                
                $allowed_statuses = ['pending', 'approved', 'rejected'];
                if ($id > 0 && in_array($status, $allowed_statuses)) {
                    try {
                        $stmt = $pdo->prepare("UPDATE guest_predictions SET status = ? WHERE id = ?");
                        if ($stmt->execute([$status, $id])) {
                            echo json_encode([
                                'success' => true,
                                'message' => 'دۆخەکە بە سەرکەوتووی نوێکرایەوە!'
                            ]);
                            exit;
                        }
                    } catch (PDOException $e) {
                        error_log($e->getMessage());
                    }
                }
                
                echo json_encode([
                    'success' => false,
                    'error' => 'هەڵەیەک ڕوویدا لە نوێکردنەوەدا'
                ]);
                exit;
                break;
            case 'edit_admin':
                if (isSuperAdmin()) {
                    try {
                        $id = (int)($_POST['id'] ?? 0);
                        $username = sanitize($_POST['username'] ?? '');
                        $password = $_POST['password'] ?? '';
                        $role = sanitize($_POST['role'] ?? 'admin');
                        
                        if ($id > 0 && !empty($username)) {
                            // Check if username exists for other admins
                            $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ? AND id != ?");
                            $stmt->execute([$username, $id]);
                            
                            if (!$stmt->fetch()) {
                                $sql = "UPDATE admins SET username = ?, role = ?";
                                $params = [$username, $role];
                                
                                // Only update password if provided
                                if (!empty($password)) {
                                    $sql .= ", password = ?";
                                    $params[] = password_hash($password, PASSWORD_DEFAULT);
                                }
                                
                                $sql .= " WHERE id = ?";
                                $params[] = $id;
                                
                                $stmt = $pdo->prepare($sql);
                                if ($stmt->execute($params)) {
                                    echo json_encode([
                                        'success' => true,
                                        'message' => 'زانیاریەکانی ئەdmین نوێکرانەوە'
                                    ]);
                                    exit;
                                }
                            } else {
                                throw new Exception('ئەم ناوی بەکارهێنەرە پێشتر بەکارهاتووە!');
                            }
                        }
                        throw new Exception('زانیاری تەواو نییە');
                    } catch (Exception $e) {
                        echo json_encode([
                            'success' => false,
                            'error' => $e->getMessage()
                        ]);
                        exit;
                    }
                } else {
                    echo json_encode([
                        'success' => false,
                        'error' => 'مافی دەستکاریت نییە'
                    ]);
                    exit;
                }
                break;
    
            // Update these cases in your switch statement
            case 'edit_youtube':
            case 'edit_instagram':
            case 'edit_tiktok':
    try {
        $id = (int)($_POST['id'] ?? 0);
        $type = str_replace('edit_', '', $action);
        $table = $type . '_posts';
        
        $title = sanitize($_POST['title'] ?? '');
        $url = sanitize($_POST['url'] ?? '');
        
        if ($id > 0 && !empty($title) && !empty($url)) {
            $url_column = ($type === 'youtube') ? 'video_url' : 'post_url';
            $sql = "UPDATE $table SET title = ?, $url_column = ?";
            $params = [$title, $url];
            
            // Handle image upload
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $image_path = uploadFile($_FILES['image'], $type);
                if ($image_path) {
                    // Delete old image
                    $stmt = $pdo->prepare("SELECT image_path FROM $table WHERE id = ?");
                    $stmt->execute([$id]);
                    $old_image = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($old_image && file_exists($old_image['image_path'])) {
                        unlink($old_image['image_path']);
                    }
                    
                    $sql .= ", image_path = ?";
                    $params[] = $image_path;
                }
            }
            
            $sql .= " WHERE id = ?";
            $params[] = $id;
            
            $stmt = $pdo->prepare($sql);
            if ($stmt->execute($params)) {
                echo json_encode([
                    'success' => true,
                    'message' => "پۆستی $type بە سەرکەوتووی نوێکرایەوە"
                ]);
                exit;
            }
        }
        
        throw new Exception('هەڵەیەک ڕوویدا لە نوێکردنەوەدا');
    } catch (Exception $e) {
        error_log($e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
        exit;
    }
    break;
            case 'edit_activity':
            case 'edit_sponsor':
                try {
                    $id = (int)($_POST['id'] ?? 0);
                    $type = str_replace('edit_', '', $action);
                    
                    if ($type === 'activity') {
                        $title = sanitize($_POST['title'] ?? '');
                        $description = sanitize($_POST['description'] ?? '');
                        $url = sanitize($_POST['url'] ?? '');
                        
                        if ($id > 0 && !empty($title)) {
                            $sql = "UPDATE activities SET title = ?, description = ?, activity_url = ?";
                            $params = [$title, $description, $url];
                            
                            // Handle image upload
                            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                                $image_path = uploadFile($_FILES['image'], 'activity');
                                if ($image_path) {
                                    // Delete old image
                                    $stmt = $pdo->prepare("SELECT image_path FROM activities WHERE id = ?");
                                    $stmt->execute([$id]);
                                    $old_image = $stmt->fetch(PDO::FETCH_ASSOC);
                                    if ($old_image && file_exists($old_image['image_path'])) {
                                        unlink($old_image['image_path']);
                                    }
                                    
                                    $sql .= ", image_path = ?";
                                    $params[] = $image_path;
                                }
                            }
                            
                            $sql .= " WHERE id = ?";
                            $params[] = $id;
                            
                            $stmt = $pdo->prepare($sql);
                            if ($stmt->execute($params)) {
                                echo json_encode([
                                    'success' => true,
                                    'message' => 'چالاکی بە سەرکەوتووی نوێکرایەوە'
                                ]);
                                exit;
                            }
                        }
                    } else if ($type === 'sponsor') {
                        $company_name = sanitize($_POST['company_name'] ?? '');
                        $website_url = sanitize($_POST['website_url'] ?? '');
                        
                        if ($id > 0 && !empty($company_name)) {
                            $sql = "UPDATE sponsors SET company_name = ?, website_url = ?";
                            $params = [$company_name, $website_url];
                            
                            // Handle logo upload
                            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                                $logo_path = uploadFile($_FILES['logo'], 'sponsor');
                                if ($logo_path) {
                                    // Delete old logo
                                    $stmt = $pdo->prepare("SELECT logo_path FROM sponsors WHERE id = ?");
                                    $stmt->execute([$id]);
                                    $old_logo = $stmt->fetch(PDO::FETCH_ASSOC);
                                    if ($old_logo && file_exists($old_logo['logo_path'])) {
                                        unlink($old_logo['logo_path']);
                                    }
                                    
                                    $sql .= ", logo_path = ?";
                                    $params[] = $logo_path;
                                }
                            }
                            
                            $sql .= " WHERE id = ?";
                            $params[] = $id;
                            
                            $stmt = $pdo->prepare($sql);
                            if ($stmt->execute($params)) {
                                echo json_encode([
                                    'success' => true,
                                    'message' => 'سپۆنسەر بە سەرکەوتووی نوێکرایەوە'
                                ]);
                                exit;
                            }
                        }
                    }
                    
                    throw new Exception('هەڵەیەک ڕوویدا لە نوێکردنەوەدا');
                } catch (Exception $e) {
                    error_log($e->getMessage());
                    echo json_encode([
                        'success' => false,
                        'error' => $e->getMessage()
                    ]);
                    exit;
                }
                break;
            case 'clear_predictions':
                $status = sanitize($_POST['status'] ?? '');
                
                if (in_array($status, ['pending', 'approved', 'rejected'])) {
                    try {
                        $stmt = $pdo->prepare("DELETE FROM guest_predictions WHERE status = ?");
                        if ($stmt->execute([$status])) {
                            echo json_encode([
                                'success' => true,
                                'message' => 'پێشبینیەکان بە سەرکەوتووی سڕانەوە'
                            ]);
                            exit;
                        }
                    } catch (PDOException $e) {
                        error_log($e->getMessage());
                        echo json_encode([
                            'success' => false,
                            'error' => 'هەڵەیەک ڕوویدا لە سڕینەوەی پێشبینیەکان'
                        ]);
                        exit;
                    }
                }
                
                echo json_encode([
                    'success' => false,
                    'error' => 'داواکارییەکە نادروستە'
                ]);
                exit;
                break;
        }
    } catch (Exception $e) {
        $response = ['success' => false, 'error' => $e->getMessage()];
    }
    
    echo json_encode($response);
    exit;
}

// Fetch data for display
$youtube_posts = $pdo->query("SELECT * FROM youtube_posts ORDER BY created_at DESC")->fetchAll();
$instagram_posts = $pdo->query("SELECT * FROM instagram_posts ORDER BY created_at DESC")->fetchAll();
$tiktok_posts = $pdo->query("SELECT * FROM tiktok_posts ORDER BY created_at DESC")->fetchAll();
$activities = $pdo->query("SELECT * FROM activities ORDER BY created_at DESC")->fetchAll();
$sponsors = $pdo->query("SELECT * FROM sponsors ORDER BY created_at DESC")->fetchAll();
$pending_predictions = $pdo->query("SELECT * FROM guest_predictions WHERE status = 'pending' ORDER BY submitted_at DESC")->fetchAll();
$approved_predictions = $pdo->query("SELECT * FROM guest_predictions WHERE status = 'approved' ORDER BY submitted_at DESC")->fetchAll();
$rejected_predictions = $pdo->query("SELECT * FROM guest_predictions WHERE status = 'rejected' ORDER BY submitted_at DESC")->fetchAll();

if (isSuperAdmin()) {
    $admins = $pdo->query("SELECT * FROM admins ORDER BY created_at DESC")->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>بەشی ئەدمین - <?= SITE_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Arabic:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin.css">

</head>
<body>
    <!-- Header -->
    <header class="admin-header">
        <div class="container">
            <div class="header-content">
                <h1 class="admin-title">بەشی ئیدارەکردن - Diwaxan</h1>
                <div class="admin-info">
                    شێرەکەی دیوەخان بەخێرهاتیت، <?= htmlspecialchars($_SESSION['admin_username']) ?>
                    <?php if (isSuperAdmin()): ?>
                        (سەرپەرشتیار)
                    <?php endif; ?>
                </div>
                <a href="logout.php" class="logout-btn">دەرچوون</a>
            </div>
        </div>
    </header>

    <div class="container">
        <!-- Messages -->
        <?php if ($success_message): ?>
            <div class="message success"><?= $success_message ?></div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="message error"><?= $error_message ?></div>
        <?php endif; ?>

        <!-- Tabs -->
        <div class="tabs">
            <button class="tab-btn active" data-tab="youtube" onclick="openTab(event, 'youtube-tab')">یوتیوب</button>
            <button class="tab-btn" data-tab="instagram" onclick="openTab(event, 'instagram-tab')">ئینستاگرام</button>
            <button class="tab-btn" data-tab="tiktok" onclick="openTab(event, 'tiktok-tab')">تیک تۆک</button>
            <button class="tab-btn" data-tab="activities" onclick="openTab(event, 'activities-tab')">چالاکیەکان</button>
            <button class="tab-btn" data-tab="sponsors" onclick="openTab(event, 'sponsors-tab')">سپۆنسەرەکان</button>
            <button class="tab-btn" data-tab="predictions" onclick="openTab(event, 'predictions-tab')">پێشبینیەکان</button>
            <?php if (isSuperAdmin()): ?>
                <button class="tab-btn" data-tab="admins" onclick="openTab(event, 'admins-tab')">ئیدارەکردنی ئەdmین</button>
            <?php endif; ?>
        </div>
<!-- YouTube Tab -->
        <div id="youtube-tab" class="tab-content active">
            <h2>یوتیوب</h2>
            
            <!-- Add YouTube Post Form -->
            <div class="form-section">
                <h3>زیادکردنی پۆستی یوتیوب</h3>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add_youtube">
                    <input type="text" name="title" placeholder="تایتڵی ڤیدیۆ" required>
                    <input type="url" name="url" placeholder="لینکی یوتیوب" required>
                    <div class="form-group">
                        <label class="form-label">وێنە</label>
                        <div class="file-input-wrapper">
                            <input type="file" name="image" accept="image/*" required>
                            <!-- ئەم div-ە لادەبەین -->
                            <!-- <div class="custom-file-input"></div> -->
                        </div>
                    </div>
                    <button type="submit">زیادکردن</button>
                </form>
            </div>
            
            <!-- YouTube Posts List -->
            <div class="posts-list">
                <h3>لیستی پۆستەکان</h3>
                <?php foreach ($youtube_posts as $post): ?>
                <div class="post-item">
                    <div class="post-image">
                        <?php if (file_exists($post['image_path'])): ?>
                            <img src="<?= $post['image_path'] ?>" alt="<?= $post['title'] ?>">
                        <?php else: ?>
                            وێنە نییە
                        <?php endif; ?>
                    </div>
                    <div class="post-details">
                        <h4><?= htmlspecialchars($post['title']) ?></h4>
                        <p>بەروار: <?= $post['created_at'] ?></p>
                        <a href="<?= $post['video_url'] ?>" target="_blank">بینینی ڤیدیۆ</a>
                    </div>
                    <div class="post-actions">
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="delete_item">
                            <input type="hidden" name="table" value="youtube_posts">
                            <input type="hidden" name="id" value="<?= $post['id'] ?>">
                            <button type="submit" onclick="return confirm('دڵنیای لە سڕینەوە؟')">سڕینەوە</button>
                        </form>
                        <button type="button" class="edit-btn" onclick='editItem(<?= $post["id"] ?>, "youtube", <?= json_encode($post) ?>)'>دەستکاری</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Instagram Tab -->
        <div id="instagram-tab" class="tab-content">
            <h2>ئینستاگرام</h2>
            
            <!-- Add Instagram Post Form -->
            <div class="form-section">
                <h3>زیادکردنی پۆستی ئینستاگرام</h3>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add_instagram">
                    <input type="text" name="title" placeholder="تایتڵی پۆست" required>
                    <input type="url" name="url" placeholder="لینکی ئینستاگرام" required>
                    <div class="form-group">
                        <label class="form-label">وێنە</label>
                        <div class="file-input-wrapper">
                            <input type="file" name="image" accept="image/*" required>
                            <!-- ئەم div-ە لادەبەین -->
                            <!-- <div class="custom-file-input"></div> -->
                        </div>
                    </div>
                    <button type="submit">زیادکردن</button>
                </form>
            </div>
            
            <!-- Instagram Posts List -->
            <div class="posts-list">
                <h3>لیستی پۆستەکان</h3>
                <?php foreach ($instagram_posts as $post): ?>
                <div class="post-item">
                    <div class="post-image">
                        <?php if (file_exists($post['image_path'])): ?>
                            <img src="<?= $post['image_path'] ?>" alt="<?= $post['title'] ?>">
                        <?php else: ?>
                            وێنە نییە
                        <?php endif; ?>
                    </div>
                    <div class="post-details">
                        <h4><?= htmlspecialchars($post['title']) ?></h4>
                        <p>بەروار: <?= $post['created_at'] ?></p>
                        <a href="<?= $post['post_url'] ?>" target="_blank">بینینی پۆست</a>
                    </div>
                    <div class="post-actions">
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="delete_item">
                            <input type="hidden" name="table" value="instagram_posts">
                            <input type="hidden" name="id" value="<?= $post['id'] ?>">
                            <button type="submit" onclick="return confirm('دڵنیای لە سڕینەوە؟')">سڕینەوە</button>
                        </form>
                        <button type="button" class="edit-btn" onclick='editItem(<?= $post["id"] ?>, "instagram", <?= json_encode($post) ?>)'>دەستکاری</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- TikTok Tab -->
        <div id="tiktok-tab" class="tab-content">
            <h2>تیک تۆک</h2>
            
            <!-- Add TikTok Post Form -->
            <div class="form-section">
                <h3>زیادکردنی پۆستی تیک تۆک</h3>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add_tiktok">
                    <input type="text" name="title" placeholder="تایتڵی ڤیدیۆ" required>
                    <input type="url" name="url" placeholder="لینکی تیک تۆک" required>
                    <div class="form-group">
                        <label class="form-label">وێنە</label>
                        <div class="file-input-wrapper">
                            <input type="file" name="image" accept="image/*" required>
                            <!-- ئەم div-ە لادەبەین -->
                            <!-- <div class="custom-file-input"></div> -->
                        </div>
                    </div>
                    <button type="submit">زیادکردن</button>
                </form>
            </div>
            
            <!-- TikTok Posts List -->
            <div class="posts-list">
                <h3>لیستی پۆستەکان</h3>
                <?php foreach ($tiktok_posts as $post): ?>
                <div class="post-item">
                    <div class="post-image">
                        <?php if (file_exists($post['image_path'])): ?>
                            <img src="<?= $post['image_path'] ?>" alt="<?= $post['title'] ?>">
                        <?php else: ?>
                            وێنە نییە
                        <?php endif; ?>
                    </div>
                    <div class="post-details">
                        <h4><?= htmlspecialchars($post['title']) ?></h4>
                        <p>بەروار: <?= $post['created_at'] ?></p>
                        <a href="<?= $post['post_url'] ?>" target="_blank">بینینی ڤیدیۆ</a>
                    </div>
                    <div class="post-actions">
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="delete_item">
                            <input type="hidden" name="table" value="tiktok_posts">
                            <input type="hidden" name="id" value="<?= $post['id'] ?>">
                            <button type="submit" onclick="return confirm('دڵنیای لە سڕینەوە؟')">سڕینەوە</button>
                        </form>
                        <button type="button" class="edit-btn" onclick='editItem(<?= $post["id"] ?>, "tiktok", <?= json_encode($post) ?>)'>دەستکاری</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Activities Tab -->
        <div id="activities-tab" class="tab-content">
            <h2>چالاکیەکان</h2>
            
            <!-- Add Activity Form -->
            <div class="form-section">
                <h3>زیادکردنی چالاکی</h3>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add_activity">
                    <input type="text" name="title" placeholder="تایتڵی چالاکی" required>
                    <textarea name="description" placeholder="وەسفی چالاکی"></textarea>
                    <input type="url" name="url" placeholder="لینکی چالاکی">
                    <div class="form-group">
                        <label class="form-label">وێنە</label>
                        <div class="file-input-wrapper">
                            <input type="file" name="image" accept="image/*" required>
                            <!-- ئەم div-ە لادەبەین -->
                            <!-- <div class="custom-file-input"></div> -->
                        </div>
                    </div>
                    <button type="submit">زیادکردن</button>
                </form>
            </div>
            
            <!-- Activities List -->
            <div class="posts-list">
                <h3>لیستی چالاکیەکان</h3>
                <?php foreach ($activities as $activity): ?>
                <div class="post-item">
                    <div class="post-image">
                        <?php if (file_exists($activity['image_path'])): ?>
                            <img src="<?= $activity['image_path'] ?>" alt="<?= $activity['title'] ?>">
                        <?php else: ?>
                            وێنە نییە
                        <?php endif; ?>
                    </div>
                    <div class="post-details">
                        <h4><?= htmlspecialchars($activity['title']) ?></h4>
                        <?php if ($activity['description']): ?>
                            <p><?= htmlspecialchars($activity['description']) ?></p>
                        <?php endif; ?>
                        <p>بەروار: <?= $activity['created_at'] ?></p>
                        <?php if ($activity['activity_url']): ?>
                            <a href="<?= $activity['activity_url'] ?>" target="_blank">زیاتر بزانە</a>
                        <?php endif; ?>
                    </div>
                    <div class="post-actions">
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="delete_item">
                            <input type="hidden" name="table" value="activities">
                            <input type="hidden" name="id" value="<?= $activity['id'] ?>">
                            <button type="submit" onclick="return confirm('دڵنیای لە سڕینەوە؟')">سڕینەوە</button>
                        </form>
                        <button type="button" class="edit-btn" onclick='editItem(<?= $activity["id"] ?>, "activity", <?= json_encode($activity) ?>)'>دەستکاری</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Sponsors Tab -->
        <div id="sponsors-tab" class="tab-content">
            <h2>سپۆنسەرەکان</h2>
            
            <!-- Add Sponsor Form -->
            <div class="form-section">
                <h3>زیادکردنی سپۆنسەر</h3>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add_sponsor">
                    <input type="text" name="company_name" placeholder="ناوی کۆمپانیا" required>
                    <input type="url" name="website_url" placeholder="وێبسایتی کۆمپانیا">
                    <div class="form-group">
                        <label class="form-label">لۆگۆ</label>
                        <div class="file-input-wrapper">
                            <input type="file" name="logo" accept="image/*" required>
                            <!-- ئەم div-ە لادەبەین -->
                            <!-- <div class="custom-file-input"></div> -->
                        </div>
                    </div>
                    <button type="submit">زیادکردن</button>
                </form>
            </div>
            
            <!-- Sponsors List -->
            <div class="posts-list">
                <h3>لیستی سپۆنسەرەکان</h3>
                <?php foreach ($sponsors as $sponsor): ?>
                <div class="post-item">
                    <div class="post-image">
                        <?php if (file_exists($sponsor['logo_path'])): ?>
                            <img src="<?= $sponsor['logo_path'] ?>" alt="<?= $sponsor['company_name'] ?>">
                        <?php else: ?>
                            لۆگۆ نییە
                        <?php endif; ?>
                    </div>
                    <div class="post-details">
                        <h4><?= htmlspecialchars($sponsor['company_name']) ?></h4>
                        <p>بەروار: <?= $sponsor['created_at'] ?></p>
                        <?php if ($sponsor['website_url']): ?>
                            <a href="<?= $sponsor['website_url'] ?>" target="_blank">سەردانی وێبسایت</a>
                        <?php endif; ?>
                    </div>
                    <div class="post-actions">
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="delete_item">
                            <input type="hidden" name="table" value="sponsors">
                            <input type="hidden" name="id" value="<?= $sponsor['id'] ?>">
                            <button type="submit" onclick="return confirm('دڵنیای لە سڕینەوە؟')">سڕینەوە</button>
                        </form>
                        <button type="button" class="edit-btn" onclick='editItem(<?= $sponsor["id"] ?>, "sponsor", <?= json_encode($sponsor) ?>)'>دەستکاری</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Predictions Tab -->
        <div id="predictions-tab" class="tab-content">
            <h2>پێشبینیەکانی میوان</h2>
            
            <!-- Tabs for different prediction statuses -->
            <div class="prediction-tabs">
                <button class="prediction-tab" data-status="pending">
                    چاوەڕوان (<?= count($pending_predictions) ?>)
                </button>
                <button class="prediction-tab" data-status="approved">
                    پەسەندکراو (<?= count($approved_predictions) ?>)
                </button>
                <button class="prediction-tab" data-status="rejected">
                    ڕەتکراوە (<?= count($rejected_predictions) ?>)
                </button>
            </div>

            <!-- Pending Predictions -->
            <div id="pending-predictions" class="prediction-section">
                <div class="predictions-header">
                    <h3>چاوەڕوانی پەسەندکردن</h3>
                    <?php if (!empty($pending_predictions)): ?>
                        <button onclick="clearAllPredictions('pending')" class="clear-all">
                            سڕینەوەی هەموو پێشبینیەکان
                        </button>
                    <?php endif; ?>
                </div>
                <?php if (empty($pending_predictions)): ?>
                    <div class="no-predictions">هیچ پێشبینیەک نییە</div>
                <?php else: ?>
                    <?php foreach ($pending_predictions as $prediction): ?>
                        <div class="prediction-item">
                            <div class="prediction-text">
                                <p><?= htmlspecialchars($prediction['prediction_text']) ?></p>
                                <small>ناردراوە لە: <?= $prediction['submitted_at'] ?></small>
                            </div>
                            <div class="prediction-actions">
                                <form method="POST" class="prediction-status-form">
    <input type="hidden" name="action" value="update_prediction">
    <input type="hidden" name="id" value="<?= $prediction['id'] ?>">
    <select name="status">
        <option value="pending" <?= $prediction['status'] === 'pending' ? 'selected' : '' ?>>چاوەڕوان</option>
        <option value="approved" <?= $prediction['status'] === 'approved' ? 'selected' : '' ?>>پەسەندکراو</option>
        <option value="rejected" <?= $prediction['status'] === 'rejected' ? 'selected' : '' ?>>ڕەتکراوە</option>
    </select>
</form>
                                <button onclick="deletePrediction(<?= $prediction['id'] ?>)">سڕینەوە</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Approved Predictions -->
            <div id="approved-predictions" class="prediction-section" style="display: none;">
                <div class="predictions-header">
                    <h3>پێشبینیە پەسەندکراوەکان</h3>
                    <?php if (!empty($approved_predictions)): ?>
                        <button onclick="clearAllPredictions('approved')" class="clear-all">
                            سڕینەوەی هەموو پێشبینیەکان
                        </button>
                    <?php endif; ?>
                </div>
                <?php if (empty($approved_predictions)): ?>
                    <div class="no-predictions">هیچ پێشبینیەک نییە</div>
                <?php else: ?>
                    <?php foreach ($approved_predictions as $prediction): ?>
                        <div class="prediction-item">
                            <div class="prediction-text">
                                <p><?= htmlspecialchars($prediction['prediction_text']) ?></p>
                                <small>ناردراوە لە: <?= $prediction['submitted_at'] ?></small>
                            </div>
                            <div class="prediction-actions">
                                <button onclick="deletePrediction(<?= $prediction['id'] ?>)">سڕینەوە</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Rejected Predictions -->
            <div id="rejected-predictions" class="prediction-section" style="display: none;">
                <div class="predictions-header">
                    <h3>پێشبینیە ڕەتکراوەکان</h3>
                    <?php if (!empty($rejected_predictions)): ?>
                        <button onclick="clearAllPredictions('rejected')" class="clear-all">
                            سڕینەوەی هەموو پێشبینیەکان
                        </button>
                    <?php endif; ?>
                </div>
                <?php if (empty($rejected_predictions)): ?>
                    <div class="no-predictions">هیچ پێشبینیەک نییە</div>
                <?php else: ?>
                    <?php foreach ($rejected_predictions as $prediction): ?>
                        <div class="prediction-item">
                            <div class="prediction-text">
                                <p><?= htmlspecialchars($prediction['prediction_text']) ?></p>
                                <small>ناردراوە لە: <?= $prediction['submitted_at'] ?></small>
                            </div>
                            <div class="prediction-actions">
                                <button onclick="deletePrediction(<?= $prediction['id'] ?>)">سڕینەوە</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Admins Tab (Super Admin Only) -->
        <?php if (isSuperAdmin()): ?>
        <div id="admins-tab" class="tab-content">
            <h2>ئیدارەکردنی ئەdmین</h2>
            
            <!-- Add Admin Form -->
            <div class="form-section">
                <h3>زیادکردنی ئەdmینی نوێ</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="add_admin">
                    <input type="text" name="username" placeholder="ناوی بەکارهێنەر" required>
                    <input type="password" name="password" placeholder="وشەی نهێنی" required>
                    <select name="role" required>
                        <option value="admin">ئەdmینی ئاسایی</option>
                        <option value="super_admin">سەرپەرشتیار</option>
                    </select>
                    <button type="submit">زیادکردن</button>
                </form>
            </div>
            
            <!-- Admins List -->
            <div class="posts-list">
                <h3>لیستی ئەdmینەکان</h3>
                <?php foreach ($admins as $admin): ?>
                <div class="admin-item">
                    <div class="admin-details">
                        <h4><?= htmlspecialchars($admin['username']) ?></h4>
                        <p>ڕۆڵ: <?= $admin['role'] === 'super_admin' ? 'سەرپەرشتیار' : 'ئەdmینی ئاسایی' ?></p>
                        <p>دروستکراوە لە: <?= $admin['created_at'] ?></p>
                    </div>
                    <div class="admin-actions">
                        <?php if ($admin['id'] !== $_SESSION['admin_id']): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="delete_admin">
                                <input type="hidden" name="id" value="<?= $admin['id'] ?>">
                                <button type="submit" onclick="return confirm('دڵنیای لە سڕینەوە؟')">سڕینەوە</button>
                            </form>
                            <button type="button" class="edit-btn" onclick='editItem(<?= $admin["id"] ?>, "admin", <?= json_encode($admin) ?>)'>دەستکاری</button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>دەستکاری کردن</h2>
            <form id="editForm">
                <input type="hidden" id="editId" name="id">
                <input type="hidden" id="editAction" name="action">
                <div id="editFields"></div>
                <button type="submit">نوێکردنەوە</button>
            </form>
        </div>
    </div>

    <script>
        function openTab(evt, tabName) {
            var i, tabcontent, tabbtns;
            
            // Hide all tab content
            tabcontent = document.getElementsByClassName("tab-content");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].classList.remove("active");
            }
            
            // Remove active class from all tab buttons
            tabbtns = document.getElementsByClassName("tab-btn");
            for (i = 0; i < tabbtns.length; i++) {
                tabbtns[i].classList.remove("active");
            }
            
            // Show selected tab and mark button as active
            document.getElementById(tabName).classList.add("active");
            evt.currentTarget.classList.add("active");
        }

        // Auto-submit forms with loading state
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.textContent = 'چاوەڕوان...';
                    submitBtn.disabled = true;
                }
            });
        });

        document.querySelectorAll('input[type="file"]').forEach(input => {
    // چێک دەکەین ئایا پێشتر custom-file-input دروستکراوە
    if (!input.parentNode.querySelector('.custom-file-input')) {
        const customInput = document.createElement('div');
        customInput.className = 'custom-file-input';
        input.parentNode.appendChild(customInput);
    }
    
    // Event listener بۆ کاتێک فایل هەڵدەبژێردرێت
    input.addEventListener('change', function() {
        const customInput = this.parentNode.querySelector('.custom-file-input');
        if (this.files.length > 0) {
            this.parentNode.classList.add('has-file');
            customInput.setAttribute('data-file-name', this.files[0].name);
        } else {
            this.parentNode.classList.remove('has-file');
            customInput.removeAttribute('data-file-name');
        }
    });
});

// Edit item function
        function editItem(id, type, data) {
            console.log('Editing:', {id, type, data}); // For debugging
            
            // Set form values
            document.getElementById('editId').value = id;
            document.getElementById('editAction').value = `edit_${type}`;

            let fieldsHTML = '';
            
            try {
                // Sanitize the data
                const safeData = JSON.parse(JSON.stringify(data));
                
                switch(type) {
                    case 'youtube':
                        fieldsHTML = `
                            <input type="text" name="title" value="${safeData.title || ''}" placeholder="تایتڵی ڤیدیۆ" required>
                            <input type="url" name="url" value="${safeData.video_url || ''}" placeholder="لینکی یوتیوب" required>
                            <div class="form-group">
                                <label>وێنە</label>
                                <div class="file-input-wrapper">
                                    <input type="file" name="image" accept="image/*">
                                    ${safeData.image_path ? `<img src="${safeData.image_path}" class="preview-image">` : ''}
                                </div>
                            </div>
                        `;
                        break;

                    case 'instagram':
                    case 'tiktok':
                        fieldsHTML = `
                            <input type="text" name="title" value="${safeData.title || ''}" placeholder="تایتڵی پۆست" required>
                            <input type="url" name="url" value="${safeData.post_url || ''}" placeholder="لینک" required>
                            <div class="form-group">
                                <label>وێنە</label>
                                <div class="file-input-wrapper">
                                    <input type="file" name="image" accept="image/*">
                                    ${safeData.image_path ? `<img src="${safeData.image_path}" class="preview-image">` : ''}
                                </div>
                            </div>
                        `;
                        break;

                    case 'activity':
                        fieldsHTML = `
                            <input type="text" name="title" value="${safeData.title || ''}" placeholder="تایتڵی چالاکی" required>
                            <textarea name="description" placeholder="وەسفی چالاکی">${safeData.description || ''}</textarea>
                            <input type="url" name="url" value="${safeData.activity_url || ''}" placeholder="لینکی چالاکی">
                            <div class="form-group">
                                <label>وێنە</label>
                                <div class="file-input-wrapper">
                                    <input type="file" name="image" accept="image/*">
                                    ${safeData.image_path ? `<img src="${safeData.image_path}" class="preview-image">` : ''}
                                </div>
                            </div>
                        `;
                        break;

                    case 'admin':
                        fieldsHTML = `
                            <input type="text" name="username" value="${safeData.username || ''}" placeholder="ناوی بەکارهێنەر" required>
                            <input type="password" name="password" placeholder="وشەی تێپەڕ نوێ">
                            <select name="role">
                                <option value="admin" ${safeData.role === 'admin' ? 'selected' : ''}>بەڕێوەبەر</option>
                                <option value="super_admin" ${safeData.role === 'super_admin' ? 'selected' : ''}>سوپەر ئەdmین</option>
                            </select>
                        `;
                        break;

                    case 'sponsor':
                        fieldsHTML = `
                            <input type="text" name="company_name" value="${safeData.company_name || ''}" placeholder="ناوی کۆمپانیا" required>
                            <input type="url" name="website_url" value="${safeData.website_url || ''}" placeholder="وێبسایتی کۆمپانیا">
                            <div class="form-group">
                                <label>لۆگۆ</label>
                                <div class="file-input-wrapper">
                                    <input type="file" name="logo" accept="image/*">
                                    ${safeData.logo_path ? `<img src="${safeData.logo_path}" class="preview-image">` : ''}
                                </div>
                            </div>
                        `;
                        break;
                }

                // Update modal content and show it
                document.getElementById('editFields').innerHTML = fieldsHTML;
                document.getElementById('editModal').style.display = 'block';

                // Initialize file input styling for new elements
                initializeFileInputs();

            } catch (error) {
                console.error('Error in editItem:', error);
                alert('هەڵەیەک ڕوویدا!');
            }
        }

        // Add this helper function
        function initializeFileInputs() {
            document.querySelectorAll('#editModal input[type="file"]').forEach(input => {
                // چێک دەکەین ئایا پێشتر custom-file-input دروستکراوە
    if (!input.parentNode.querySelector('.custom-file-input')) {
        const customInput = document.createElement('div');
        customInput.className = 'custom-file-input';
        input.parentNode.appendChild(customInput);
    }
    
    // Event listener بۆ کاتێک فایل هەڵدەبژێردرێت
    input.addEventListener('change', function() {
        const customInput = this.parentNode.querySelector('.custom-file-input');
        if (this.files.length > 0) {
            this.parentNode.classList.add('has-file');
            customInput.setAttribute('data-file-name', this.files[0].name);
        } else {
            this.parentNode.classList.remove('has-file');
            customInput.removeAttribute('data-file-name');
        }
    });
            });
        }

        // Close modal when clicking X or outside
        document.querySelector('.close').onclick = () => {
            document.getElementById('editModal').style.display = 'none';
        }

        window.onclick = (e) => {
            if (e.target == document.getElementById('editModal')) {
                document.getElementById('editModal').style.display = 'none';
            }
        }

        // Update the handleFormSubmit function
function handleFormSubmit(form) {
    const submitBtn = form.querySelector('button[type="submit"]');
    const formData = new FormData(form);
    
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = 'چاوەڕوانbە...';
    }

    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showMessage(data.message, 'success');
            if (form.id === 'editForm') {
                document.getElementById('editModal').style.display = 'none';
            }
            setTimeout(() => window.location.reload(), 1000);
        } else {
            throw new Error(data.error || 'هەڵەیەک ڕوویدا');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage(error.message || 'هەڵەیەک ڕوویدا', 'error');
    })
    .finally(() => {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = form.id === 'editForm' ? 'نوێکردنەوە' : 'زیادکردن';
        }
    });

    return false; // Prevent default form submission
}
        // Update edit form submission
        document.getElementById('editForm').onsubmit = function(e) {
            e.preventDefault();
            handleFormSubmit(this);
        };

        document.addEventListener('DOMContentLoaded', function() {
    // Handle all forms
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            handleFormSubmit(this);
        });
    });

    // Initialize file inputs
    initializeFileInputs();
});

// Add message display function if not exists
function showMessage(message, type) {
    const messageDiv = document.createElement('div');
    messageDiv.className = `message ${type}`;
    messageDiv.textContent = message;
    
    const container = document.querySelector('.container');
    container.insertBefore(messageDiv, container.firstChild);
    
    setTimeout(() => messageDiv.remove(), 3000);
}

// Prediction tab functionality
document.querySelectorAll('.prediction-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        // Remove active class from all tabs
        document.querySelectorAll('.prediction-tab').forEach(t => 
            t.classList.remove('active'));
        
        // Add active class to clicked tab
        this.classList.add('active');
        
        // Hide all sections
        document.querySelectorAll('.prediction-section').forEach(section => 
            section.style.display = 'none');
        
        // Show selected section
        const status = this.dataset.status;
        document.getElementById(`${status}-predictions`).style.display = 'block';
    });
});

// Add this function to store and retrieve active tab
function handleTabs() {
    // Store active tab in localStorage when switching tabs
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            localStorage.setItem('activeTab', this.getAttribute('data-tab'));
        });
    });

    // Restore active tab after page reload
    window.addEventListener('load', function() {
        const activeTab = localStorage.getItem('activeTab') || 'youtube';
        const tabBtn = document.querySelector(`[data-tab="${activeTab}"]`);
        if (tabBtn) {
            tabBtn.click();
        }
    });
}

// Initialize tabs
document.addEventListener('DOMContentLoaded', handleTabs);

// Update the deletePrediction function
function deletePrediction(id) {
    if (confirm('دڵنیای لە سڕینەوە؟')) {
        const formData = new FormData();
        formData.append('action', 'delete_prediction');
        formData.append('id', id);
        
        // Store current tab before deleting
        const activeTab = document.querySelector('.prediction-tab.active');
        const currentTab = activeTab ? activeTab.dataset.status : 'pending';
        localStorage.setItem('predictionTab', currentTab);
        
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then((data) => {  // Added parentheses here
            if (data.success) {
                showMessage(data.message, 'success');
                
                // Reload page after delay and restore tab
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                throw new Error(data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage(error.message || 'هەڵەیەک ڕوویدا', 'error');
        });
    }
}

// Update the clearAllPredictions function
function clearAllPredictions(status) {
    if (confirm('دڵنیای لە سڕینەوەی هەموو پێشبینیەکان؟')) {
        const formData = new FormData();
        formData.append('action', 'clear_predictions');
        formData.append('status', status);

        // Store current tab
        localStorage.setItem('predictionTab', status);
        
        // Show loading state
        const button = document.querySelector(`button[onclick="clearAllPredictions('${status}')"]`);
        if (button) {
            button.disabled = true;
            button.textContent = 'چاوەڕوانbە...';
        }

        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage(data.message, 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                throw new Error(data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage(error.message || 'هەڵەیەک ڕوویدا', 'error');
        })
        .finally(() => {
            if (button) {
                button.disabled = false;
                button.textContent = 'سڕینەوەی هەموو پێشبینیەکان';
            }
        });
    }
}

// Add this to initialize the first tab as active when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Set first prediction tab as active by default if none is active
    const activeTab = document.querySelector('.prediction-tab.active');
    if (!activeTab) {
        const firstTab = document.querySelector('.prediction-tab');
        if (firstTab) {
            firstTab.click();
        }
    }
});

// Add this after your existing JavaScript code
document.addEventListener('DOMContentLoaded', function() {
    // Handle prediction status changes
    document.querySelectorAll('.prediction-status-form select').forEach(select => {
        select.addEventListener('change', function() {
            const form = this.closest('form');
            const formData = new FormData(form);
            
            // Show loading state
            this.disabled = true;
            const originalText = this.options[this.selectedIndex].text;
            this.options[this.selectedIndex].text = 'چاوەڕوانbە...';

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage(data.message, 'success');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    throw new Error(data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage(error.message || 'هەڵەیەک ڕوویدا', 'error');
                this.value = this.dataset.originalValue;
            })
            .finally(() => {
                this.disabled = false;
                this.options[this.selectedIndex].text = originalText;
            });
        });

        // Store original value
        select.dataset.originalValue = select.value;
    });
});
    </script>
</body>
</html>