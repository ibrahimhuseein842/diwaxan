-- Create database
CREATE DATABASE IF NOT EXISTS diwaxan_podcast CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE diwaxan_podcast;

-- Admin users table
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('super_admin', 'admin') DEFAULT 'admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- YouTube posts table
CREATE TABLE IF NOT EXISTS youtube_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    video_url TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'inactive') DEFAULT 'active'
);

-- Instagram posts table
CREATE TABLE IF NOT EXISTS instagram_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    post_url VARCHAR(255) NOT NULL,
    image_path VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TikTok posts table
CREATE TABLE IF NOT EXISTS tiktok_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    post_url VARCHAR(255) NOT NULL,
    image_path VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Guest predictions table
CREATE TABLE IF NOT EXISTS `guest_predictions` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `guest_name` varchar(255) NOT NULL,
    `prediction_text` text NOT NULL,
    `status` enum('pending','approved','rejected') DEFAULT 'pending',
    `submitted_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Activities table
CREATE TABLE IF NOT EXISTS activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    activity_url VARCHAR(255) DEFAULT NULL,
    image_path VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sponsors table
CREATE TABLE IF NOT EXISTS sponsors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(255) NOT NULL,
    website_url VARCHAR(255) DEFAULT NULL,
    logo_path VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default super admin (password: admin123)
INSERT INTO admins (username, password, role) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin');

-- Sample data for testing
INSERT INTO youtube_posts (title, image_path, video_url) VALUES 
('پۆدکاستی دیوەخان - ئەڵقەی یەکەم', 'uploads/youtube/sample1.jpg', 'https://youtube.com/watch?v=sample1'),
('گفتوگۆ لەگەڵ کەسایەتیەکی بەناوبانگ', 'uploads/youtube/sample2.jpg', 'https://youtube.com/watch?v=sample2'),
('بابەتی گرنگ لە کۆمەڵگەدا', 'uploads/youtube/sample3.jpg', 'https://youtube.com/watch?v=sample3');

INSERT INTO instagram_posts (title, image_path, post_url) VALUES 
('پۆستی نوێ لە ئینستاگرام', 'uploads/instagram/sample1.jpg', 'https://instagram.com/p/sample1'),
('وێنەی جوانی پۆدکاست', 'uploads/instagram/sample2.jpg', 'https://instagram.com/p/sample2'),
('بەشداریکردن لە چالاکی', 'uploads/instagram/sample3.jpg', 'https://instagram.com/p/sample3');

INSERT INTO tiktok_posts (title, image_path, post_url) VALUES 
('ڤیدیۆی قەشەنگ لە تیک تۆک', 'uploads/tiktok/sample1.jpg', 'https://tiktok.com/@diwaxan/video/1'),
('مێتۆدی نوێی پۆدکاست', 'uploads/tiktok/sample2.jpg', 'https://tiktok.com/@diwaxan/video/2'),
('گەیشتن بە ئامانجەکان', 'uploads/tiktok/sample3.jpg', 'https://tiktok.com/@diwaxan/video/3');

INSERT INTO activities (title, description, image_path, activity_url) VALUES 
('کۆنفرانسی تەکنەلۆجیا', 'بەشداریکردن لە کۆنفرانسی گەورەی تەکنەلۆجیا لە هەولێر', 'uploads/activity/conf1.jpg', 'https://example.com/conference'),
('وۆرکشۆپی پۆدکاستینگ', 'فێرکردنی کەسانی نوێ لە هونەری پۆدکاستینگ', 'uploads/activity/workshop1.jpg', 'https://example.com/workshop');

INSERT INTO sponsors (company_name, logo_path, website_url) VALUES 
('کۆمپانیای تەکنەلۆجیا', 'uploads/sponsor/tech_company.jpg', 'https://techcompany.com'),
('بانکی کوردستان', 'uploads/sponsor/kurdistan_bank.jpg', 'https://kurdistanbank.com'),
('زانکۆی هەولێر', 'uploads/sponsor/erbil_uni.jpg', 'https://epu.edu.iq');

-- Update instagram_posts table
ALTER TABLE instagram_posts 
ADD COLUMN IF NOT EXISTS status ENUM('active', 'inactive') DEFAULT 'active';

-- Update youtube_posts table
ALTER TABLE youtube_posts
ADD COLUMN IF NOT EXISTS status ENUM('active', 'inactive') DEFAULT 'active';

-- Update tiktok_posts table
ALTER TABLE tiktok_posts
ADD COLUMN IF NOT EXISTS status ENUM('active', 'inactive') DEFAULT 'active';

-- Update activities table
ALTER TABLE activities
ADD COLUMN IF NOT EXISTS status ENUM('active', 'inactive') DEFAULT 'active';

-- Update sponsors table
ALTER TABLE sponsors
ADD COLUMN IF NOT EXISTS status ENUM('active', 'inactive') DEFAULT 'active';

-- Update existing records to have 'active' status
UPDATE instagram_posts SET status = 'active' WHERE status IS NULL;
UPDATE youtube_posts SET status = 'active' WHERE status IS NULL;
UPDATE tiktok_posts SET status = 'active' WHERE status IS NULL;
UPDATE activities SET status = 'active' WHERE status IS NULL;
UPDATE sponsors SET status = 'active' WHERE status IS NULL;

