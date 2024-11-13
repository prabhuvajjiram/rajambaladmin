-- Drop the existing colors table if it exists
DROP TABLE IF EXISTS colors;

-- Create the colors table with proper foreign key constraint
CREATE TABLE colors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    color_name VARCHAR(50) NOT NULL,
    color_image_path VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);
