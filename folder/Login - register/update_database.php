<?php
require_once 'database.php';

// Add advisor_id column to students table if it doesn't exist
$sql = "ALTER TABLE students ADD COLUMN IF NOT EXISTS advisor_id INT NOT NULL DEFAULT 0 AFTER id";
if (mysqli_query($conn, $sql)) {
    echo "Added advisor_id column to students table.<br>";
} else {
    die("Error updating students table: " . mysqli_error($conn));
}

// Add advisor_id column to deleted_students table if it doesn't exist
$sql = "ALTER TABLE deleted_students ADD COLUMN IF NOT EXISTS advisor_id INT NOT NULL DEFAULT 0 AFTER user_id";
if (mysqli_query($conn, $sql)) {
    echo "Added advisor_id column to deleted_students table.<br>";
} else {
    die("Error updating deleted_students table: " . mysqli_error($conn));
}

// Update existing records in students table to have a default advisor_id
$update_sql = "UPDATE students SET advisor_id = user_id WHERE advisor_id = 0";
if (mysqli_query($conn, $update_sql)) {
    $affected = mysqli_affected_rows($conn);
    echo "Updated $affected student records with advisor_id.<br>";
} else {
    echo "Warning: Could not update student records: " . mysqli_error($conn) . "<br>";
}

// Update existing records in deleted_students table to have a default advisor_id
$update_sql = "UPDATE deleted_students SET advisor_id = user_id WHERE advisor_id = 0";
if (mysqli_query($conn, $update_sql)) {
    $affected = mysqli_affected_rows($conn);
    echo "Updated $affected deleted student records with advisor_id.<br>";
} else {
    echo "Warning: Could not update deleted student records: " . mysqli_error($conn) . "<br>";
}

echo "<strong>Database update complete.</strong> All advisor_id columns have been added and existing records have been updated.";

// Display current table structures for verification
echo "<h3>Current Table Structures:</h3>";
$tables = ['students', 'deleted_students'];
foreach ($tables as $table) {
    $result = mysqli_query($conn, "SHOW COLUMNS FROM $table");
    if ($result) {
        echo "<h4>Structure of $table table:</h4>";
        echo "<pre>";
        while ($row = mysqli_fetch_assoc($result)) {
            echo $row['Field'] . " " . $row['Type'] . "<br>";
        }
        echo "</pre>";
    }
}
?>
