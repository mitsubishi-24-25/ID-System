<?php
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "advisor") {
   header("Location: login.php");
   exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https:
    <link rel="preconnect" href="https:
    <link href="https:
    <link rel="stylesheet" href="https:
    <link rel="stylesheet" href="style2.css">
    
    <title>Advisor Homepage</title>
</head>
<body>
  
    <div class="hamburger" data-bs-toggle="offcanvas" data-bs-target="
        <span></span>
        <span></span>
        <span></span>
    </div>

   <img src="logo.png" alt="My Smart ID Logo" class="dashboard-logo">
   
    <div class="container">
        <h1>Welcome Advisor <?php echo htmlspecialchars($_SESSION["full_name"]); ?> to My Smart ID</h1>
    </div>

   
    <div class="offcanvas offcanvas-start" tabindex="-1" id="dashboardOffcanvas">
        <div class="offcanvas-body">
            <div class="d-grid gap-2">
                <a href="
                <a href="
                <a href="
                <a href="download_id.php" class="btn btn-secondary">Download ID</a>
                <hr>
                <a href="logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>
    </div>

   
    <script src="https:
</body>
</html>