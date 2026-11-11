<?php
session_start();

// Debug: Show session and POST data
// echo '<pre>Session: '; print_r($_SESSION); echo '</pre>';
// echo '<pre>POST: '; print_r($_POST); echo '</pre>';

// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    // Debug: Log the redirection
    error_log("Redirecting to login.php - Session user_id not set. Session data: " . print_r($_SESSION, true));
    header("Location: login.php");
    exit();
}

$success_message = "";
$error_message = "";

require_once "database.php";

// Get current user information
$user_id = $_SESSION["user_id"];
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["update_profile"])) {
        // Update full name
        $fullName = trim($_POST["fullname"]);
        
        if (empty($fullName)) {
            $error_message = "Full name is required";
        } else {
            // Check if name already exists (excluding current user)
            $check_sql = "SELECT id FROM users WHERE full_name = ? AND id != ?";
            $stmt = mysqli_prepare($conn, $check_sql);
            mysqli_stmt_bind_param($stmt, "si", $fullName, $user_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if (mysqli_num_rows($result) > 0) {
                $error_message = "Name is already taken";
            } else {
                $sql = "UPDATE users SET full_name = ? WHERE id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "si", $fullName, $user_id);
                
                if (mysqli_stmt_execute($stmt)) {
                    $success_message = "Profile updated successfully";
                    // Update session
                    $_SESSION["full_name"] = $fullName;
                    // Refresh user data
                    $user['full_name'] = $fullName;
                } else {
                    $error_message = "Error updating profile: " . mysqli_error($conn);
                }
            }
        }
    } 
    elseif (isset($_POST["change_password"])) {
        // Change password
        $currentPassword = $_POST["current_password"];
        $newPassword = $_POST["new_password"];
        $confirmPassword = $_POST["confirm_password"];
        
        // Verify current password
        if (!password_verify($currentPassword, $user["password"])) {
            $error_message = "Current password is incorrect";
        } 
        elseif (strlen($newPassword) < 8) {
            $error_message = "New password must be at least 8 characters long";
        } 
        elseif ($newPassword !== $confirmPassword) {
            $error_message = "New passwords do not match";
        } 
        else {
            // Update password
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET password = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "si", $passwordHash, $user_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $success_message = "Password updated successfully";
            } else {
                $error_message = "Error updating password: " . mysqli_error($conn);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .settings-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 30px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        .settings-section {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
        }
        .settings-section h3 {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #dee2e6;
        }
    </style>
</head>
<body>
    <?php include('includes/header.php'); ?>
    
    <div class="container settings-container">
        <h2 class="mb-4">Account Settings</h2>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <div class="settings-section">
            <h3>Update Profile</h3>
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="fullname" class="form-label">Full Name</label>
                    <input type="text" class="form-control" id="fullname" name="fullname" 
                           value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                </div>
                <button type="submit" name="update_profile" class="btn btn-primary">Update Name</button>
            </form>
        </div>
        
        <div class="settings-section">
            <h3>Change Password</h3>
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="current_password" class="form-label">Current Password</label>
                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                </div>
                <div class="mb-3">
                    <label for="new_password" class="form-label">New Password</label>
                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                    <div class="form-text">Password must be at least 8 characters long.</div>
                </div>
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>
                <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
            </form>
        </div>
    </div>
    
    <?php include('includes/footer.php'); ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
