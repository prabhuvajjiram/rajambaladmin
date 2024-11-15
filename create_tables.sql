-- First, make sure you're using the correct database
USE rajambal_Rajambal;

-- Create the admins table if it doesn't exist
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS colors (
 id int(11) NOT NULL AUTO_INCREMENT,
 product_id int(11) DEFAULT NULL,
 color_name varchar(50) DEFAULT NULL,
 color_image_path varchar(255) DEFAULT NULL,
 PRIMARY KEY (id),
 KEY product_id (product_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS products (
 id int(11) NOT NULL AUTO_INCREMENT,
 title varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
 price decimal(10,2) NOT NULL,
 description mediumtext COLLATE utf8mb4_unicode_ci,
 image_path varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
 created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS product_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Create feedback table
CREATE TABLE IF NOT EXISTS feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    comment TEXT NOT NULL,
    status ENUM('active', 'inactive', 'pending') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add indexes for better performance
CREATE INDEX idx_feedback_status ON feedback(status);
CREATE INDEX idx_feedback_created_at ON feedback(created_at);

-- Insert a new admin user with a hashed password
-- Replace 'your_username' with the desired username
-- Replace 'your_password' with the actual password you want to use
INSERT INTO admins (username, password) VALUES (
    'admin',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
);

-- The password hash above is for the password 'password'. 
-- YOU MUST CHANGE THIS to a secure password of your choice!