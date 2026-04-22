-- Akademik Takip Veritabanı Şeması
-- Çalıştır: mysql -u root -p < akademik_takip.sql

CREATE DATABASE IF NOT EXISTS akademik_takip CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci;
USE akademik_takip;

-- Kullanıcılar tablosu
CREATE TABLE IF NOT EXISTS users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    email       VARCHAR(150) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    avatar      VARCHAR(255) DEFAULT NULL,
    theme       VARCHAR(20)  DEFAULT 'light',
    remember_token VARCHAR(64) DEFAULT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Projeler tablosu
CREATE TABLE IF NOT EXISTS projects (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    title       VARCHAR(200) NOT NULL,
    description TEXT,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Görevler tablosu
CREATE TABLE IF NOT EXISTS tasks (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    project_id  INT DEFAULT NULL,
    title       VARCHAR(200) NOT NULL,
    description TEXT,
    due_date    DATETIME DEFAULT NULL,
    status      ENUM('beklemede','devam_ediyor','tamamlandi') DEFAULT 'beklemede',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Dosyalar tablosu
CREATE TABLE IF NOT EXISTS files (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    project_id  INT DEFAULT NULL,
    filename    VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_size   BIGINT DEFAULT 0,
    mime_type   VARCHAR(100) DEFAULT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Örnek kullanıcı (şifre: 123456)
INSERT IGNORE INTO users (name, email, password) VALUES
('Test Kullanıcı', 'test@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
