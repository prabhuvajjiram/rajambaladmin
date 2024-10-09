-- First, make sure you're using the correct database
USE rajambal_Rajambal;

-- Create the admins table if it doesn't exist
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert a new admin user with a hashed password
-- Replace 'your_username' with the desired username
-- Replace 'your_password' with the actual password you want to use
INSERT INTO admins (username, password) VALUES (
    'your_username',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
);

-- The password hash above is for the password 'password'. 
-- YOU MUST CHANGE THIS to a secure password of your choice!