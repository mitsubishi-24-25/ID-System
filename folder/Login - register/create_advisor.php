<?php
session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
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
    <title>Create Advisor Account</title>
    <link rel="stylesheet" href="https:
    <link rel="stylesheet" href="style.css"> <!-- You can reuse style.css -->
</head>
<body>
    <div class="container">
        <a href="index.php" class="btn btn-primary mb-3">Back to Admin Dashboard</a>
        <h2>Create New Advisor Account</h2>
        <?php
        if (isset($_POST["create_advisor"])) {
           $fullName = $_POST["fullname"];
           $email = $_POST["email"];
           $password = $_POST["password"];
           $passwordRepeat = $_POST["repeat_password"];
           $role = "advisor"; 
           
           $passwordHash = password_hash($password, PASSWORD_DEFAULT);

           $errors = array();
           
           if (empty($fullName) OR empty($email) OR empty($password) OR empty($passwordRepeat)) {
            array_push($errors,"All fields are required");
           }
           if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            array_push($errors, "Email is not valid");
           }
           if (strlen($password)<8) {
            array_push($errors,"Password must be at least 8 characters long");
           }
           if ($password!==$passwordRepeat) {
            array_push($errors,"Password does not match");
           }
           
           require_once "database.php";
           $sql = "SELECT * FROM users WHERE email = ?";
           $stmt = mysqli_stmt_init($conn);
           if (mysqli_stmt_prepare($stmt,$sql)) {
               mysqli_stmt_bind_param($stmt, "s", $email);
               mysqli_stmt_execute($stmt);
               mysqli_stmt_store_result($stmt);
               $rowCount = mysqli_stmt_num_rows($stmt);
               if ($rowCount>0) {
                array_push($errors,"Email already exists!");
               }
           } else {
               die("SQL statement failed to prepare.");
           }
           
           if (count($errors)>0) {
            foreach ($errors as  $error) {
                echo "<div class='alert alert-danger'>$error</div>";
            }
           }else{
            
            $sql = "INSERT INTO users (full_name, email, password, role) VALUES ( ?, ?, ?, ? )";
            $stmt = mysqli_stmt_init($conn);
            $prepareStmt = mysqli_stmt_prepare($stmt,$sql);
            if ($prepareStmt) {
                mysqli_stmt_bind_param($stmt,"ssss",$fullName, $email, $passwordHash, $role);
                mysqli_stmt_execute($stmt);
                echo "<div class='alert alert-success'>Advisor account created successfully.</div>";
            }else{
                die("Something went wrong");
            }
           }
        }
        ?>
        <form action="create_advisor.php" method="post">
            <div class="form-group">
                <input type="text" class="form-control" name="fullname" placeholder="Full Name:" required>
            </div>
            <div class="form-group">
                <input type="email" class="form-control" name="email" placeholder="Email:" required>
            </div>
            <div class="form-group">
                <input type="password" class="form-control" name="password" placeholder="Password:" required>
            </div>
            <div class="form-group">
                <input type="password" class="form-control" name="repeat_password" placeholder="Repeat Password:" required>
            </div>
            <div class="form-btn">
                <input type="submit" class="btn btn-primary" value="Create Advisor" name="create_advisor">
            </div>
        </form>
    </div>
</body>
</html>