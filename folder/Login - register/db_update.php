<?php
require_once "database.php";


$checkColumnQuery = "SHOW COLUMNS FROM users LIKE 'signature_path'";
$result = mysqli_query($conn, $checkColumnQuery);

if (mysqli_num_rows($result) === 0) {
    $alterQuery = "ALTER TABLE users ADD COLUMN signature_path VARCHAR(255) NULL";
    if (mysqli_query($conn, $alterQuery)) {
        echo "signature_path column added successfully.";
    } else {
        echo "Error adding signature_path column: " . mysqli_error($conn);
    }
} else {
    echo "signature_path column already exists.";
}
?>
