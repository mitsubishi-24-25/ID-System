<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

require_once "database.php";

$userId = $_SESSION['user_id'];


$sigSql = "SELECT signature_path FROM users WHERE id = ?";
$sigStmt = mysqli_prepare($conn, $sigSql);
mysqli_stmt_bind_param($sigStmt, "i", $userId);
mysqli_stmt_execute($sigStmt);
$sigResult = mysqli_stmt_get_result($sigStmt);

$signaturePath = null;
if ($sigResult && mysqli_num_rows($sigResult) > 0) {
    $row = mysqli_fetch_assoc($sigResult);
    $signaturePath = $row['signature_path'];
}
mysqli_stmt_close($sigStmt);

if ($signaturePath) {
    
    if (file_exists($signaturePath)) {
        unlink($signaturePath);
    }

    
    $delStmt = mysqli_prepare($conn, "UPDATE users SET signature_path = NULL WHERE id = ?");
    mysqli_stmt_bind_param($delStmt, "i", $userId);
    if (mysqli_stmt_execute($delStmt)) {
        mysqli_stmt_close($delStmt);
        $successMessage = "Signature deleted successfully.";
        header("Location: index.php?success_message=" . urlencode($successMessage));
        exit();
    } else {
        mysqli_stmt_close($delStmt);
        $errorMessage = "Failed to update database.";
        header("Location: index.php?success_message=" . urlencode($errorMessage));
        exit();
    }
} else {
    $errorMessage = "No signature to delete.";
    header("Location: index.php?success_message=" . urlencode($errorMessage));
    exit();
}
?>
