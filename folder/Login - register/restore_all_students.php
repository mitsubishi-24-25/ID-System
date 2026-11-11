<?php
session_start();
require_once "database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$advisor_id = $userId; // Set advisor_id to be the same as user_id

// Start transaction
mysqli_begin_transaction($conn);

try {
    // Get all deleted students for the current advisor
    $selectSql = "SELECT * FROM deleted_students WHERE advisor_id = ?";
    $selectStmt = mysqli_prepare($conn, $selectSql);
    mysqli_stmt_bind_param($selectStmt, "i", $advisor_id);
    mysqli_stmt_execute($selectStmt);
    $result = mysqli_stmt_get_result($selectStmt);
    
    $restoredCount = 0;
    
    while ($student = mysqli_fetch_assoc($result)) {
        // Check if student with this LRN already exists and is not deleted
        $checkSql = "SELECT id FROM students WHERE lrn = ? AND advisor_id = ? AND is_deleted = 0";
        $checkStmt = mysqli_prepare($conn, $checkSql);
        mysqli_stmt_bind_param($checkStmt, "si", $student['lrn'], $advisor_id);
        mysqli_stmt_execute($checkStmt);
        $checkResult = mysqli_stmt_get_result($checkStmt);
        
        if (mysqli_num_rows($checkResult) > 0) {
            // Update existing student record
            $updateSql = "UPDATE students SET 
                last_name = ?,
                middle_initial = ?,
                first_name = ?,
                birthdate = ?,
                parent_guardian = ?,
                permanent_address = ?,
                contact_number = ?,
                photo = ?,
                is_deleted = 0,
                advisor_id = ?
                WHERE lrn = ? AND advisor_id = ?";
            
            $updateStmt = mysqli_prepare($conn, $updateSql);
            mysqli_stmt_bind_param(
                $updateStmt,
                "sssssssssii",
                $student['last_name'],
                $student['middle_initial'],
                $student['first_name'],
                $student['birthdate'],
                $student['parent_guardian'],
                $student['permanent_address'],
                $student['contact_number'],
                $student['photo'],
                $advisor_id,
                $student['lrn'],
                $advisor_id
            );
            
            if (!mysqli_stmt_execute($updateStmt)) {
                throw new Exception("Failed to update student with LRN: " . $student['lrn'] . ". Error: " . mysqli_error($conn));
            }
        } else {
            // Insert as new student
            $insertSql = "INSERT INTO students (
                lrn, last_name, middle_initial, first_name, 
                birthdate, parent_guardian, permanent_address, 
                contact_number, photo, user_id, advisor_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $insertStmt = mysqli_prepare($conn, $insertSql);
            mysqli_stmt_bind_param(
                $insertStmt,
                "sssssssssii",
                $student['lrn'],
                $student['last_name'],
                $student['middle_initial'],
                $student['first_name'],
                $student['birthdate'],
                $student['parent_guardian'],
                $student['permanent_address'],
                $student['contact_number'],
                $student['photo'],
                $userId,
                $advisor_id
            );
            
            if (!mysqli_stmt_execute($insertStmt)) {
                throw new Exception("Failed to restore student with LRN: " . $student['lrn'] . ". Error: " . mysqli_error($conn));
            }
        }
        
        // Delete from deleted_students
        $deleteSql = "DELETE FROM deleted_students WHERE id = ? AND advisor_id = ?";
        $deleteStmt = mysqli_prepare($conn, $deleteSql);
        mysqli_stmt_bind_param($deleteStmt, "ii", $student['id'], $advisor_id);
        
        if (!mysqli_stmt_execute($deleteStmt)) {
            throw new Exception("Failed to clean up deleted record for LRN: " . $student['lrn'] . ". Error: " . mysqli_error($conn));
        }
        
        $restoredCount++;
    }
    
    // Commit transaction
    mysqli_commit($conn);
    
    // Set success message in session
    $_SESSION['success_message'] = "Successfully restored $restoredCount students!";
    
} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($conn);
    $_SESSION['error_message'] = $e->getMessage();
}

// Redirect back to the restore students page
header("Location: restore_students.php");
exit();
?>
