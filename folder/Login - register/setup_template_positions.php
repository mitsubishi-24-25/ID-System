<?php
require_once "database.php";

// Create table for storing ID template positions
$sql = "CREATE TABLE IF NOT EXISTS id_template_positions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    template_type ENUM('front', 'back') NOT NULL,
    field_name VARCHAR(50) NOT NULL,
    x_position DECIMAL(10,2) NOT NULL DEFAULT 0,
    y_position DECIMAL(10,2) NOT NULL DEFAULT 0,
    font_family VARCHAR(50) DEFAULT 'Arial',
    font_size INT DEFAULT 12,
    font_weight ENUM('normal', 'bold', 'lighter') DEFAULT 'normal',
    text_color VARCHAR(7) DEFAULT '#000000',
    text_align ENUM('left', 'center', 'right') DEFAULT 'left',
    max_width INT DEFAULT NULL,
    rotation DECIMAL(5,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_template_field (user_id, template_type, field_name),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

if (mysqli_query($conn, $sql)) {
    echo "Table 'id_template_positions' created successfully or already exists.";
} else {
    echo "Error creating table: " . mysqli_error($conn);
}

mysqli_close($conn);
?>
