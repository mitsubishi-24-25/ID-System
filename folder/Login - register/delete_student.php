<?php
session_start();
require_once "database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$success_message = "";
$error_message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && !empty($_POST['lrn'])) {
    $userId = $_SESSION['user_id'];
    $lrn = trim($_POST['lrn']);
    $advisor_id = $_SESSION['user_id'];

    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Get student data before deleting
        $selectSql = "SELECT * FROM students WHERE lrn = ? AND advisor_id = ? AND is_deleted = 0";
        $selectStmt = mysqli_prepare($conn, $selectSql);
        mysqli_stmt_bind_param($selectStmt, "si", $lrn, $advisor_id);
        mysqli_stmt_execute($selectStmt);
        $result = mysqli_stmt_get_result($selectStmt);
        
        if (mysqli_num_rows($result) > 0) {
            $student = mysqli_fetch_assoc($result);
            
            // Insert into deleted_students
            $insertSql = "INSERT INTO deleted_students 
                         (student_id, lrn, last_name, middle_initial, first_name, 
                          birthdate, parent_guardian, permanent_address, 
                          contact_number, photo, user_id, advisor_id)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $insertStmt = mysqli_prepare($conn, $insertSql);
            mysqli_stmt_bind_param(
                $insertStmt,
                "isssssssssii",
                $student['id'],
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
                throw new Exception("Failed to backup student data: " . mysqli_error($conn));
            }
            
            // Permanently delete the student from students table
            $deleteSql = "DELETE FROM students WHERE lrn = ? AND advisor_id = ?";
            $deleteStmt = mysqli_prepare($conn, $deleteSql);
            mysqli_stmt_bind_param($deleteStmt, "si", $lrn, $advisor_id);
            
            if (!mysqli_stmt_execute($deleteStmt)) {
                throw new Exception("Failed to delete student: " . mysqli_error($conn));
            }
            
            // Commit transaction
            mysqli_commit($conn);
            
            header("Location: index.php?success_message=" . urlencode("Student successfully deleted."));
            exit();
        } else {
            throw new Exception("Student not found or already deleted.");
        }
    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        header("Location: index.php?error_message=" . urlencode($e->getMessage()));
        exit();
    }
}

header("Location: index.php");
exit();
?>