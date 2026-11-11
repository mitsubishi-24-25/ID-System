<?php
session_start();
require_once "database.php";

// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

// Check if the request is a POST request
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $userId = $_SESSION['user_id'];
    $advisor_id = $userId; // Assuming the current user is the advisor

    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Count how many records will be deleted (for the success message)
        $countSql = "SELECT COUNT(*) as count FROM deleted_students WHERE advisor_id = ?";
        $countStmt = mysqli_prepare($conn, $countSql);
        mysqli_stmt_bind_param($countStmt, "i", $advisor_id);
        mysqli_stmt_execute($countStmt);
        $countResult = mysqli_stmt_get_result($countStmt);
        $countRow = mysqli_fetch_assoc($countResult);
        $deletedCount = $countRow['count'];

        // Permanently delete all students from deleted_students table
        $deleteSql = "DELETE FROM deleted_students WHERE advisor_id = ?";
        $deleteStmt = mysqli_prepare($conn, $deleteSql);
        mysqli_stmt_bind_param($deleteStmt, "i", $advisor_id);
        
        if (!mysqli_stmt_execute($deleteStmt)) {
            throw new Exception("Failed to delete students: " . mysqli_error($conn));
        }
        
        // Commit transaction if all operations were successful
        mysqli_commit($conn);
        
        // Redirect with success message
        if ($deletedCount > 0) {
            header("Location: restore_students.php?success_message=" . urlencode("Successfully permanently deleted $deletedCount students. This action cannot be undone."));
        } else {
            header("Location: restore_students.php?info_message=" . urlencode("No students found in the recycle bin to delete."));
        }
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        header("Location: restore_students.php?error_message=" . urlencode($e->getMessage()));
        exit();
    }
} else {
    // If not a POST request, redirect to restore_students.php
    header("Location: restore_students.php");
    exit();
}
?>
