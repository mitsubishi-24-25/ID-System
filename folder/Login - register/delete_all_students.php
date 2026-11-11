<?php
session_start();
require_once "database.php";

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

// Check if the request is a POST request
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $userId = $_SESSION['user_id'];
    $advisor_id = $userId;

    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Get all students for the current advisor
        $selectSql = "SELECT * FROM students WHERE advisor_id = ? AND is_deleted = 0";
        $selectStmt = mysqli_prepare($conn, $selectSql);
        
        if (!$selectStmt) {
            throw new Exception("Database error: " . mysqli_error($conn));
        }
        
        mysqli_stmt_bind_param($selectStmt, "i", $advisor_id);
        
        if (!mysqli_stmt_execute($selectStmt)) {
            throw new Exception("Failed to fetch students: " . mysqli_stmt_error($selectStmt));
        }
        
        $result = mysqli_stmt_get_result($selectStmt);
        $deletedCount = 0;
        $errors = [];
        
        // Backup and delete each student
        while ($student = mysqli_fetch_assoc($result)) {
            try {
                // Insert into deleted_students
                $insertSql = "INSERT INTO deleted_students 
                             (student_id, lrn, last_name, middle_initial, first_name, 
                              birthdate, parent_guardian, permanent_address, 
                              contact_number, photo, user_id, advisor_id)
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $insertStmt = mysqli_prepare($conn, $insertSql);
                if (!$insertStmt) {
                    throw new Exception("Prepare failed: " . mysqli_error($conn));
                }
                
                $bindResult = mysqli_stmt_bind_param(
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
                
                if (!$bindResult) {
                    throw new Exception("Bind param failed: " . mysqli_stmt_error($insertStmt));
                }
                
                if (!mysqli_stmt_execute($insertStmt)) {
                    throw new Exception("Failed to backup student data for LRN: " . $student['lrn'] . ". Error: " . mysqli_stmt_error($insertStmt));
                }
                
                // Delete the student
                $deleteSql = "DELETE FROM students WHERE id = ? AND advisor_id = ?";
                $deleteStmt = mysqli_prepare($conn, $deleteSql);
                
                if (!$deleteStmt) {
                    throw new Exception("Prepare failed: " . mysqli_error($conn));
                }
                
                if (!mysqli_stmt_bind_param($deleteStmt, "ii", $student['id'], $advisor_id)) {
                    throw new Exception("Bind param failed: " . mysqli_stmt_error($deleteStmt));
                }
                
                if (!mysqli_stmt_execute($deleteStmt)) {
                    throw new Exception("Failed to delete student with LRN: " . $student['lrn'] . ". Error: " . mysqli_stmt_error($deleteStmt));
                }
                
                $deletedCount++;
                
            } catch (Exception $e) {
                // Log individual student errors but continue with others
                $errors[] = $e->getMessage();
                continue;
            }
        }
        
        if (!empty($errors) && $deletedCount === 0) {
            // If we have errors and no successful deletes, throw an exception
            throw new Exception("Failed to delete any students. Errors: " . implode(" ", $errors));
        }
        
        // Commit transaction if all operations were successful
        if (!mysqli_commit($conn)) {
            throw new Exception("Commit failed: " . mysqli_error($conn));
        }
        
        $response = [
            'success' => true,
            'message' => "Successfully deleted $deletedCount students.",
            'deletedCount' => $deletedCount
        ];
        
        if (!empty($errors)) {
            $response['warnings'] = $errors;
            $response['message'] .= " Some students could not be deleted.";
        }
        
        echo json_encode($response);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        http_response_code(400); // Bad Request
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    
} else {
    // If not a POST request, return error
    http_response_code(405); // Method Not Allowed
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed. Use POST.'
    ]);
}

// Close database connection
if (isset($conn)) {
    mysqli_close($conn);
}
?>
