<?php
// SQL update statement
$sql = "UPDATE admins SET username = ?, password = ? WHERE id = ?";

// This is just the SQL statement. In a real application, you would use
// prepared statements and proper error handling when executing this query.

// Example usage:
// $stmt = $conn->prepare($sql);
// $stmt->bind_param("ssi", $new_username, $new_hashed_password, $user_id);
/*CREATE TABLE colors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT,
    color_name VARCHAR(50),
    color_image_path VARCHAR(255),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);*/
// $stmt->execute();
?>