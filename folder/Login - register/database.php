<?php

// Database configuration
$hostName = "localhost";
$dbUser = "root";
$dbPassword = "";
$dbName = "login_register";

// Create connection with error handling
try {
    $conn = mysqli_connect($hostName, $dbUser, $dbPassword, $dbName);
    
    if (!$conn) {
        throw new Exception("Connection failed: " . mysqli_connect_error());
    }
    
    // Set charset to ensure proper character encoding
    if (!mysqli_set_charset($conn, "utf8mb4")) {
        throw new Exception("Error loading character set utf8mb4: " . mysqli_error($conn));
    }
    
} catch (Exception $e) {
    // Log the error (in a production environment, log to a file)
    error_log("Database connection error: " . $e->getMessage());
    
    // Display a user-friendly error message
    die("We're experiencing technical difficulties. Please try again later.");
}

// Function to safely execute prepared statements
function executeQuery($conn, $sql, $params = [], $types = '') {
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . mysqli_error($conn));
    }
    
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Execute failed: " . mysqli_stmt_error($stmt));
    }
    
    return $stmt;
}
?>