-- Drop database if exists
DROP DATABASE IF EXISTS pj1_db;

-- Create database
CREATE DATABASE IF NOT EXISTS pj1_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

-- Use database
USE pj1_db;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    fullname VARCHAR(100),
    avatar VARCHAR(255),
    role ENUM('admin', 'moderator', 'user') DEFAULT 'user',
    status ENUM('active', 'inactive', 'banned') DEFAULT 'active',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    parent_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create news table
CREATE TABLE IF NOT EXISTS news (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    content TEXT NOT NULL,
    excerpt TEXT,
    image VARCHAR(255),
    category_id INT,
    author_id INT,
    status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    view_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    published_at TIMESTAMP NULL,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create comments table
CREATE TABLE IF NOT EXISTS comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    content TEXT NOT NULL,
    news_id INT NOT NULL,
    user_id INT NOT NULL,
    parent_id INT DEFAULT NULL,
    status ENUM('pending', 'approved', 'spam') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (news_id) REFERENCES news(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES comments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create tags table
CREATE TABLE IF NOT EXISTS tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    slug VARCHAR(50) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create news_tags table (pivot table)
CREATE TABLE IF NOT EXISTS news_tags (
    news_id INT NOT NULL,
    tag_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (news_id, tag_id),
    FOREIGN KEY (news_id) REFERENCES news(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create settings table
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(50) NOT NULL UNIQUE,
    setting_value TEXT,
    setting_group VARCHAR(50) DEFAULT 'general',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample data

-- Insert users
INSERT INTO users (username, email, password, fullname, role, status) VALUES
('admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin', 'active'),
('moderator', 'mod@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Moderator', 'moderator', 'active'),
('user1', 'user1@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User One', 'user', 'active'),
('user2', 'user2@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'User Two', 'user', 'active');

-- Insert categories
INSERT INTO categories (name, slug, description, parent_id) VALUES
('Tin tức', 'tin-tuc', 'Tin tức mới nhất', NULL),
('Thể thao', 'the-thao', 'Tin tức thể thao', 1),
('Công nghệ', 'cong-nghe', 'Tin tức công nghệ', 1),
('Giải trí', 'giai-tri', 'Tin tức giải trí', 1),
('Du lịch', 'du-lich', 'Tin tức du lịch', NULL),
('Ẩm thực', 'am-thuc', 'Tin tức ẩm thực', NULL);

-- Insert news
INSERT INTO news (title, slug, content, excerpt, category_id, author_id, status, view_count, published_at) VALUES
('Tin tức mới nhất về công nghệ', 'tin-tuc-moi-nhat-ve-cong-nghe', 'Nội dung chi tiết về tin tức công nghệ...', 'Tóm tắt tin tức công nghệ mới nhất', 3, 1, 'published', 100, NOW()),
('Bóng đá Việt Nam vô địch', 'bong-da-viet-nam-vo-dich', 'Nội dung chi tiết về bóng đá...', 'Tóm tắt tin tức bóng đá', 2, 2, 'published', 200, NOW()),
('Top 10 điểm du lịch hấp dẫn', 'top-10-diem-du-lich-hap-dan', 'Nội dung chi tiết về du lịch...', 'Tóm tắt tin tức du lịch', 5, 1, 'published', 150, NOW());

-- Insert comments
INSERT INTO comments (content, news_id, user_id, status) VALUES
('Bài viết rất hay!', 1, 3, 'approved'),
('Cảm ơn bạn đã chia sẻ', 1, 4, 'approved'),
('Thông tin rất hữu ích', 2, 3, 'approved'),
('Tôi rất thích bài viết này', 3, 4, 'pending');

-- Insert tags
INSERT INTO tags (name, slug) VALUES
('Công nghệ', 'cong-nghe'),
('Bóng đá', 'bong-da'),
('Du lịch', 'du-lich'),
('Việt Nam', 'viet-nam'),
('Thể thao', 'the-thao');

-- Insert news_tags
INSERT INTO news_tags (news_id, tag_id) VALUES
(1, 1),
(2, 2),
(2, 4),
(2, 5),
(3, 3),
(3, 4);

-- Insert settings
INSERT INTO settings (setting_key, setting_value, setting_group) VALUES
('site_name', 'PJ1 News', 'general'),
('site_description', 'Trang tin tức tổng hợp', 'general'),
('site_keywords', 'tin tức, tin mới, tin hot', 'general'),
('posts_per_page', '10', 'reading'),
('comments_per_page', '20', 'discussion'),
('allow_comments', '1', 'discussion'),
('maintenance_mode', '0', 'general'),
('admin_email', 'admin@example.com', 'general'); 