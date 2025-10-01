-- Create database and least-privilege user (adjust as needed)
CREATE DATABASE IF NOT EXISTS auth_demo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'auth_user'@'localhost' IDENTIFIED BY 'change_this_password';
GRANT ALL PRIVILEGES ON auth_demo.* TO 'auth_user'@'localhost';
FLUSH PRIVILEGES;
-- Table is auto-created by php/config.php on first request, but you can run it manually too:
CREATE TABLE IF NOT EXISTS auth_demo.users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  age INT NULL,
  dob DATE NULL,
  contact VARCHAR(50) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;