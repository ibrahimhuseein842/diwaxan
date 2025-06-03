<?php
require_once 'config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('admin.php');
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (!empty($username) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();
        
        // دیبەگ کردنی زیاتر
        echo "<pre>";
        echo "Database connection status: ";
        var_dump($pdo->getAttribute(PDO::ATTR_CONNECTION_STATUS));
        echo "\nUsername entered: " . $username;
        echo "\nPassword entered: " . $password;
        echo "\nAdmin data from database: ";
        var_dump($admin);
        if ($admin) {
            echo "\nStored hashed password: " . $admin['password'];
            echo "\nPassword verification result: ";
            var_dump(password_verify($password, $admin['password']));
        }
        echo "</pre>";
        
        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_role'] = $admin['role'];
            redirect('admin.php');
        } else {
            $error_message = 'ناوی بەکارهێنەر یان وشەی نهێنی هەڵەیە!';
        }
    } else {
        $error_message = 'تکایە هەموو خانەکان پڕبکەرەوە!';
    }
}
?>

<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>چوونەژوورەوە - <?= SITE_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Arabic:wght@300;400;500;600;700&display=swap" rel="stylesheet">
     <link rel="stylesheet" href="style.css">
          <link rel="stylesheet" href="admin.css">
          <link rel="stylesheet" href="login_lofout.css">



</head>
<body>
    <div class="login-container">
        <h1 class="logo">Diwaxan</h1>
        <p class="subtitle">بەشی ئەدمین</p>

        <?php if ($error_message): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <?= $error_message ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" class="login-form" autocomplete="off">
            <div class="form-group">
                <label for="username" class="form-label">ناوی بەکارهێنەر</label>
                <input type="text" id="username" name="username" class="form-input" 
                       placeholder="ناوی بەکارهێنەر" required 
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label for="password" class="form-label">وشەی نهێنی</label>
                <input type="password" id="password" name="password" class="form-input" 
                       placeholder="وشەی نهێنی" required>
            </div>
            
            <button type="submit" class="login-btn">
                <span class="btn-text">چوونەژوورەوە</span>
            </button>
        </form>
        
        <a href="index.php" class="back-link">گەڕانەوە بۆ وێبسایت</a>
        
    </div>

    <script>
        document.getElementById('username').focus();
        
        document.querySelector('form').addEventListener('submit', function(e) {
            const btn = document.querySelector('.login-btn');
            const btnText = btn.querySelector('.btn-text');
            btnText.textContent = 'چاوەڕوانبە...';
            btn.disabled = true;
        });
    </script>
</body>
</html>