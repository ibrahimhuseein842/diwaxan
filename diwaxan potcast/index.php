<?php 
require_once 'config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Handle guest prediction submission
if ($_POST['action'] ?? '' === 'submit_prediction') {
    $prediction = sanitize($_POST['prediction'] ?? '');
    if (!empty($prediction)) {
        $stmt = $pdo->prepare("INSERT INTO guest_predictions (prediction_text) VALUES (?)");
        if ($stmt->execute([$prediction])) {
            $_SESSION['success_message'] = "پێشبینیەکەت بە سەرکەوتووی نێردرا!";
            // Redirect to prevent form resubmission
            header('Location: ' . $_SERVER['PHP_SELF'] . '#prediction');
            exit;
        }
    }
}

// Get message from session and clear it
$success_message = $_SESSION['success_message'] ?? null;
unset($_SESSION['success_message']);

// Fetch latest content
try {
    // Fetch latest content with status checking
    $youtube_posts = $pdo->query("SELECT * FROM youtube_posts WHERE status = 'active' ORDER BY created_at DESC LIMIT 3")->fetchAll();
    $instagram_posts = $pdo->query("SELECT * FROM instagram_posts WHERE status = 'active' ORDER BY created_at DESC LIMIT 3")->fetchAll();
    $tiktok_posts = $pdo->query("SELECT * FROM tiktok_posts WHERE status = 'active' ORDER BY created_at DESC LIMIT 3")->fetchAll();
    $activities = $pdo->query("SELECT * FROM activities WHERE status = 'active' ORDER BY created_at DESC")->fetchAll();
    $sponsors = $pdo->query("SELECT * FROM sponsors WHERE status = 'active' ORDER BY created_at DESC")->fetchAll();
} catch (PDOException $e) {
    // If status column doesn't exist, fetch without status
    error_log("Database error: " . $e->getMessage());
    $youtube_posts = $pdo->query("SELECT * FROM youtube_posts ORDER BY created_at DESC LIMIT 3")->fetchAll();
    $instagram_posts = $pdo->query("SELECT * FROM instagram_posts ORDER BY created_at DESC LIMIT 3")->fetchAll();
    $tiktok_posts = $pdo->query("SELECT * FROM tiktok_posts ORDER BY created_at DESC LIMIT 3")->fetchAll();
    $activities = $pdo->query("SELECT * FROM activities ORDER BY created_at DESC")->fetchAll();
    $sponsors = $pdo->query("SELECT * FROM sponsors ORDER BY created_at DESC")->fetchAll();
}

// Fetch only approved predictions
$approved_predictions = $pdo->query("
    SELECT * FROM guest_predictions 
    WHERE status = 'approved' 
    ORDER BY submitted_at DESC
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Arabic:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <h1 class="logo">The Diwaxan Potcast</h1>
            <p class="tagline">دیوەخان پۆتکاست بەخێرهاتنی شێرەکانی دەکات  </p>
        </div>
    </header>

    <!-- Navigation -->
  
    <nav class="nav">
    <div class="container">
        <!-- Add hamburger menu button -->
        <button class="menu-toggle">
            <span class="bar"></span>
            <span class="bar"></span>
            <span class="bar"></span>
        </button>
        
        <div class="nav-menu">
            <a href="#youtube" class="nav-item">یوتیوب</a>
            <a href="#instagram" class="nav-item">ئینستاگرام</a>
            <a href="#tiktok" class="nav-item">تیک تۆک</a>
            <a href="#prediction" class="nav-item">پێشنیاری میوان</a>
            <a href="#activities" class="nav-item">چالاکیەکان</a>
            <a href="#sponsors" class="nav-item">سپۆنسەرەکان</a>
        </div>
    </div>

</nav>

    <!-- YouTube Section -->
    <section id="youtube" class="section">
        <div class="container">
            <h2 class="section-title">نوێترین پۆتکاستەکانی یوتیوب</h2>
            <div class="cards-grid">
                <?php foreach ($youtube_posts as $post): ?>
                <div class="card">
                    <div class="card-image">
                        <?php if (file_exists($post['image_path'])): ?>
                            <img src="<?= $post['image_path'] ?>" alt="<?= $post['title'] ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            وێنەی یوتیوب
                        <?php endif; ?>
                    </div>
                    <div class="card-content">
                        <h3 class="card-title"><?= htmlspecialchars($post['title']) ?></h3>
                        <a href="<?= $post['video_url'] ?>" target="_blank" class="card-link">بینینی ڤیدیۆ</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Instagram Section -->
    <section id="instagram" class="section">
        <div class="container">
            <h2 class="section-title">نوێترین ڕیلەکانی ئینستاگرام</h2>
            <div class="cards-grid">
                <?php foreach ($instagram_posts as $post): ?>
                <div class="card">
                    <div class="card-image">
                        <?php if (file_exists($post['image_path'])): ?>
                            <img src="<?= $post['image_path'] ?>" alt="<?= $post['title'] ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            وێنەی ئینستاگرام
                        <?php endif; ?>
                    </div>
                    <div class="card-content">
                        <h3 class="card-title"><?= htmlspecialchars($post['title']) ?></h3>
                        <a href="<?= $post['post_url'] ?>" target="_blank" class="card-link">بینینی پۆست</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- TikTok Section -->
    <section id="tiktok" class="section">
        <div class="container">
            <h2 class="section-title">نوێترین ڤیدیۆکانی تیک تۆک</h2>
            <div class="cards-grid">
                <?php foreach ($tiktok_posts as $post): ?>
                <div class="card">
                    <div class="card-image">
                        <?php if (file_exists($post['image_path'])): ?>
                            <img src="<?= $post['image_path'] ?>" alt="<?= $post['title'] ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            وێنەی تیک تۆک
                        <?php endif; ?>
                    </div>
                    <div class="card-content">
                        <h3 class="card-title"><?= htmlspecialchars($post['title']) ?></h3>
                        <a href="<?= $post['post_url'] ?>" target="_blank" class="card-link">بینینی ڤیدیۆ</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Guest Prediction Section -->
    <section id="prediction" class="section">
        <div class="container">
            <div class="prediction-form">
                <h2 class="form-title">پێشنیاری میوانی داهاتوو بکە </h2>
                <p class="form-description">پێشنیارەکەت بنوسە کە کێ میوان دەبێت لە ئەڵقەی داهاتوودا!</p>
                
                <?php if ($success_message): ?>
                    <div class="success-message"><?= $success_message ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <input type="hidden" name="action" value="submit_prediction">
                    <textarea name="prediction" class="prediction-textarea" placeholder="پێشنیارەکەت لێرە بنوسە..." required></textarea>
                    <button type="submit" class="submit-btn">ناردنی پێشنیار</button>
                </form>
            </div>
        </div>
    </section>

    <!-- Predictions Section -->
    <section id="predictions" class="section">
        <div class="container">
            <h2 class="section-title">پێشنیارەکانی میوانان</h2>
            <?php if (empty($approved_predictions)): ?>
                <p class="no-predictions">هیچ پێشنیارێک نییە</p>
            <?php else: ?>
                <div class="predictions-grid">
                    <?php foreach ($approved_predictions as $prediction): ?>
                        <div class="prediction-card">
                            <p class="prediction-text"><?= htmlspecialchars($prediction['prediction_text']) ?></p>
                            <small class="prediction-date">ناردراوە لە: <?= $prediction['submitted_at'] ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Activities Section -->
    <section id="activities" class="section">
        <div class="container">
            <h2 class="section-title">چالاکیەکانمان</h2>
            <div class="cards-grid">
                <?php foreach ($activities as $activity): ?>
                <div class="card">
                    <div class="card-image">
                        <?php if (file_exists($activity['image_path'])): ?>
                            <img src="<?= $activity['image_path'] ?>" alt="<?= $activity['title'] ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            وێنەی چالاکی
                        <?php endif; ?>
                    </div>
                    <div class="card-content">
                        <h3 class="card-title"><?= htmlspecialchars($activity['title']) ?></h3>
                        <?php if ($activity['description']): ?>
                            <p class="card-description"><?= htmlspecialchars($activity['description']) ?></p>
                        <?php endif; ?>
                        <?php if ($activity['activity_url']): ?>
                            <a href="<?= $activity['activity_url'] ?>" target="_blank" class="card-link">زیاتر بزانە</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Sponsors Section -->
    <section id="sponsors" class="section">
        <div class="container">
            <h2 class="section-title">سپۆنسەرەکانمان</h2>
            <?php if (empty($sponsors)): ?>
                <p class="no-sponsors">هیچ سپۆنسەرێک نییە</p>
            <?php else: ?>
                <div class="sponsors-container">
                    <div class="sponsors-track">
                        <?php 
                        // Optimize number of repeats based on sponsor count
                        $repeat_count = max(12, ceil(12 / count($sponsors)));
                        
                        for($i = 0; $i < $repeat_count; $i++):
                            foreach ($sponsors as $sponsor): 
                                // Validate sponsor data
                                $logo_path = isset($sponsor['logo_path']) && file_exists($sponsor['logo_path']) 
                                    ? $sponsor['logo_path'] 
                                    : 'images/default-sponsor.png'; // Add a default image
                                
                                $company_name = isset($sponsor['company_name']) 
                                    ? htmlspecialchars($sponsor['company_name']) 
                                    : 'سپۆنسەر';
                                
                                $website_url = isset($sponsor['website_url']) && filter_var($sponsor['website_url'], FILTER_VALIDATE_URL) 
                                    ? htmlspecialchars($sponsor['website_url']) 
                                    : false;
                        ?>
                            <div class="sponsor-card" data-aos="fade-up">
                                <?php if ($website_url): ?>
                                    <a href="<?= $website_url ?>" target="_blank" rel="noopener noreferrer" class="sponsor-link">
                                <?php endif; ?>
                                    <div class="sponsor-logo">
                                        <img src="<?= $logo_path ?>" 
                                             alt="<?= $company_name ?>" 
                                             loading="lazy"
                                             onerror="this.src='images/default-sponsor.png'">
                                    </div>
                                    <h4 class="sponsor-name"><?= $company_name ?></h4>
                                <?php if ($website_url): ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php 
                            endforeach;
                        endfor; 
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>دیوەخان پۆتکاست</p>
            <div class="social-links">
                <a href="https://www.youtube.com/@thediwaxanpodcast" class="social-link">یوتیوب</a>
                <a href="https://instagram.com/diwaxanpodcast" class="social-link">ئینستاگرام</a>
                <a href="https://www.tiktok.com/@diwaxanpodcast" class="social-link">تیک تۆک</a>
            </div>
        </div>
    </footer>

    <script>
// Smooth scroll for navigation links
document.querySelectorAll('.nav-item').forEach(link => {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        const targetId = this.getAttribute('href').substring(1);
        const targetElement = document.getElementById(targetId);
        
        if (targetElement) {
            targetElement.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });

            // Add active class to clicked link
            document.querySelectorAll('.nav-item').forEach(item => {
                item.classList.remove('active');
            });
            this.classList.add('active');
        }
    });
});

// Highlight active section on scroll
window.addEventListener('scroll', function() {
    const sections = document.querySelectorAll('section');
    const navItems = document.querySelectorAll('.nav-item');
    
    let current = '';
    
    sections.forEach(section => {
        const sectionTop = section.offsetTop;
        const sectionHeight = section.clientHeight;
        
        if (pageYOffset >= sectionTop - 200) {
            current = section.getAttribute('id');
        }
    });
    
    navItems.forEach(item => {
        item.classList.remove('active');
        if (item.getAttribute('href').substring(1) === current) {
            item.classList.add('active');
        }
    });
});

document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.querySelector('.menu-toggle');
    const navMenu = document.querySelector('.nav-menu');
    
    // Toggle menu when clicking hamburger
    menuToggle.addEventListener('click', function() {
        menuToggle.classList.toggle('active');
        navMenu.classList.toggle('active');
    });

    // Close menu when clicking nav items
    document.querySelectorAll('.nav-item').forEach(item => {
        item.addEventListener('click', function() {
            menuToggle.classList.remove('active');
            navMenu.classList.remove('active');
        });
    });

    // Close menu when clicking outside
    document.addEventListener('click', function(e) {
        if (!navMenu.contains(e.target) && !menuToggle.contains(e.target)) {
            menuToggle.classList.remove('active');
            navMenu.classList.remove('active');
        }
    });
});
</script>
</body>
</html>
