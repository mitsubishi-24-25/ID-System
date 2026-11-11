<?php
require_once "database.php";

// Check if deleted_students table exists
$check_table = "SHOW TABLES LIKE 'deleted_students'";
$result = mysqli_query($conn, $check_table);

if (mysqli_num_rows($result) > 0) {
    echo "The 'deleted_students' table exists.\n";
    
    // Show table structure
    $structure = mysqli_query($conn, "DESCRIBE deleted_students");
    echo "\nTable structure for 'deleted_students':\n";
    echo str_pad("Field", 30) . str_pad("Type", 20) . str_pad("Null", 10) . str_pad("Key", 10) . str_pad("Default", 15) . "Extra\n";
    echo str_repeat("-", 85) . "\n";
    
    while ($row = mysqli_fetch_assoc($structure)) {
        echo str_pad($row['Field'], 30) . 
             str_pad($row['Type'], 20) . 
             str_pad($row['Null'], 10) . 
             str_pad($row['Key'], 10) . 
             str_pad(($row['Default'] ?? 'NULL'), 15) . 
             $row['Extra'] . "\n";
    }
} else {
    echo "The 'deleted_students' table does not exist.\n";
    
    // Show all tables for reference
    echo "\nAvailable tables in the database:\n";
    $tables = mysqli_query($conn, "SHOW TABLES");
    while ($row = mysqli_fetch_row($tables)) {
        echo "- " . $row[0] . "\n";
    }
}

// Close connection
mysqli_close($conn);
?>
