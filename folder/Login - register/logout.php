<?php
session_start();

// Handle logout confirmation
if (isset($_POST['confirm_logout']) && $_POST['confirm_logout'] === 'yes') {
    session_unset(); 
    session_destroy(); 
    header("Location: homepage.php");
    exit(); 
}

// If not confirmed, show confirmation page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Logout</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #198754 0%, #20c997 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .confirmation-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1), 0 5px 15px rgba(0, 0, 0, 0.07);
            padding: 40px;
            max-width: 450px;
            width: 100%;
            text-align: center;
        }
        
        .icon-container {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
        }
        
        .icon-container i {
            font-size: 2.5rem;
            color: white;
        }
        
        .title {
            color: #333;
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .message {
            color: #666;
            font-size: 1rem;
            margin-bottom: 30px;
            line-height: 1.5;
        }
        
        .btn-group {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn-logout {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            border: none;
            padding: 12px 30px;
            font-size: 1rem;
            font-weight: 500;
            border-radius: 8px;
            color: white;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-logout:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 107, 107, 0.3);
        }
        
        .btn-cancel {
            background: #6c757d;
            border: none;
            padding: 12px 30px;
            font-size: 1rem;
            font-weight: 500;
            border-radius: 8px;
            color: white;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-cancel:hover {
            background: #5c636a;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(108, 117, 125, 0.3);
        }
        
        .user-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            border-left: 4px solid #198754;
        }
        
        .user-name {
            font-weight: 600;
            color: #198754;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="confirmation-container">
        <div class="icon-container">
            <i class="bi bi-box-arrow-right"></i>
        </div>
        
        <h1 class="title">Confirm Logout</h1>
        
        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="user-info">
                <div class="user-name">
                    <?php 
                    // Get user's full name from database
                    $user_id = $_SESSION['user_id'];
                    require_once 'database.php';
                    $user_sql = "SELECT full_name FROM users WHERE id = ?";
                    $user_stmt = mysqli_prepare($conn, $user_sql);
                    if ($user_stmt) {
                        mysqli_stmt_bind_param($user_stmt, "i", $user_id);
                        mysqli_stmt_execute($user_stmt);
                        $user_result = mysqli_stmt_get_result($user_stmt);
                        if ($user_row = mysqli_fetch_assoc($user_result)) {
                            echo htmlspecialchars($user_row['full_name'] ?? 'User');
                        } else {
                            echo 'User';
                        }
                        mysqli_stmt_close($user_stmt);
                    } else {
                        echo 'User';
                    }
                    ?>
                </div>
                <div>User ID: 
                    <?php 
                        // Show the employee ID used to login (from session)
                        echo htmlspecialchars($_SESSION['user_id']);
                    ?>
                </div>
            </div>
        <?php endif; ?>
        
        <p class="message">
            Are you sure you want to logout?<br>
            This will end your current session and you'll need to login again to access the system.
        </p>
        
        <form method="post" class="btn-group">
            <button type="submit" name="confirm_logout" value="yes" class="btn-logout">
                <i class="bi bi-check-circle"></i>
                Yes, Logout
            </button>
            <a href="javascript:history.back()" class="btn-cancel">
                <i class="bi bi-x-circle"></i>
                Cancel
            </a>
        </form>
    </div>

    <script>
        // Prevent back button from resubmitting form
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
        
        // Add keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                document.querySelector('[name="confirm_logout"]').click();
            } else if (e.key === 'Escape') {
                history.back();
            }
        });
        
        // Auto-focus on logout button for accessibility
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelector('[name="confirm_logout"]').focus();
        });
    </script>
</body>
</html>