<?php
require_once 'config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Store username for message
$username = $_SESSION['admin_username'] ?? '';

// Destroy all session data
session_destroy();

// Clear session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}
?>

<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>دەرچوون - <?= SITE_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Arabic:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="login_lofout.css">

</head>
<body>
    <div class="logout-container">
        <div class="logout-card">
            <h1 class="logo">Diwaxan</h1>
            <div class="logout-message">
                <i class="logout-icon">👋</i>
                <h2>خوات لەگەڵ شێرە !</h2>
                <?php if ($username): ?>
                    <p>سوپاس <?= htmlspecialchars($username) ?> بۆ سەردانەکەت</p>
                <?php endif; ?>
                <p class="redirect-text">لە چەند چرکەیەکی تر دەگەڕێیتەوە بۆ پەڕەی سەرەکی...</p>
            </div>
            <a href="login.php" class="login-link">گەڕانەوە بۆ چوونەژوورەوە</a>
        </div>
    </div>

    <script>
        // Redirect after 3 seconds
        setTimeout(() => {
            window.location.href = 'index.php';
        }, 30000);

        // Update countdown
        let seconds = 30;
        const redirectText = document.querySelector('.redirect-text');
        
        setInterval(() => {
            seconds--;
            if (seconds > 0) {
                redirectText.textContent = `لە ${seconds} چرکەی تر دەگەڕێیتەوە بۆ پەڕەی سەرەکی...`;
            }
        }, 1000);
    </script>
</body>
</html>