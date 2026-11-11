<?php
// Start session with secure settings
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => isset($_SERVER['HTTPS']),
        'cookie_samesite' => 'Strict',
        'use_strict_mode' => true
    ]);
}

// Include database connection
require_once 'database.php';

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Redirect if already logged in
if (isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit();
}

$errors = [];
$success = false;

// Define sections array at the top
$sections = [
    "ICT 12 - A",
    "ICT 12 - B",
    "GAS 12 - A",
    "GAS 12 - B",
    "GAS 12 - C",
    "GAS 12 - D",
    "GAS 12 - E",
    "GAS 12 - F",
    "GAS 12 - G",
    "STEM 12 - A",
    "STEM 12 - B",
    "STEM 12 - C",
    "STEM 12 - D",
    "HE 12 - A",
    "HE 12 - B",
    "ABM 12 - A",
    "ABM 12 - B",
    "ABM 12 - C",
    "ICT 11 - A",
    "ICT 11 - B",
    "GAS 11 - A",
    "GAS 11 - B",
    "GAS 11 - C",
    "GAS 11 - D",
    "GAS 11 - E",
    "GAS 11 - F",
    "GAS 11 - G",
    "STEM 11 - A",
    "STEM 11 - B",
    "STEM 11 - C",
    "STEM 11 - D",
    "HE 11 - A",
    "HE 11 - B",
    "ABM 11 - A",
    "ABM 11 - B",
    "ABM 11 - C"
];

// Process form submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["submit"])) {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token'])) {
        $errors[] = "Security token missing. Please refresh the page and try again.";
        error_log("CSRF Error: Token missing. Session token exists: " . (isset($_SESSION['csrf_token']) ? 'Yes' : 'No') . ", Post token exists: " . (isset($_POST['csrf_token']) ? 'Yes' : 'No'));
    } elseif ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = "Security token mismatch. Please refresh the page and try again.";
        error_log("CSRF Error: Token mismatch. Session: " . ($_SESSION['csrf_token'] ?? 'empty') . ", Post: " . ($_POST['csrf_token'] ?? 'empty'));
    } else {
        // Sanitize and validate input
        $fullName = trim(htmlspecialchars($_POST['fullname'] ?? ''));
        $employeeId = trim(htmlspecialchars($_POST['employee_id'] ?? ''));
        $section = isset($_POST["section"]) ? (array)$_POST["section"] : [];
        $password = $_POST["password"] ?? '';
        $passwordRepeat = $_POST["repeat_password"] ?? '';

        // Validation
        if (empty($fullName)) {
            $errors[] = "Full name is required";
        } elseif (strlen($fullName) > 100) {
            $errors[] = "Full name is too long (max 100 characters)";
        }
        
        if (empty($employeeId)) {   
            $errors[] = "Employee ID is required";
        } elseif (!preg_match('/^\d{7}$/', $employeeId)) {
            $errors[] = "Employee ID must be exactly 7 digits";
        }
        
        if (empty($section) || empty($section[0])) {
            $errors[] = "Please select a section";
        } elseif (!in_array($section[0], $sections)) {
            $errors[] = "Invalid section selected";
        } else {
            $section = $section[0]; // Get the first selected section
        }
        
        if (empty($password)) {
            $errors[] = "Password is required";
        } elseif (strlen($password) < 8) {
            $errors[] = "Password must be at least 8 characters long";
        } elseif ($password !== $passwordRepeat) {
            $errors[] = "Passwords do not match";
        }
        
        if (empty($errors)) {
            require_once "database.php";
            
            // Check if employee ID is already taken
            $checkEmployeeIdSql = "SELECT id FROM users WHERE employee_id = ? LIMIT 1";
            $stmt = mysqli_prepare($conn, $checkEmployeeIdSql);
            
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "s", $employeeId);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                
                if (mysqli_num_rows($result) > 0) {
                    $errors[] = "This employee ID is already registered.";
                }
                mysqli_stmt_close($stmt);
            }
            
            // Check if username is already taken
            $checkUserSql = "SELECT id FROM users WHERE full_name = ? LIMIT 1";
            $stmt = mysqli_prepare($conn, $checkUserSql);
            
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "s", $fullName);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                
                if (mysqli_num_rows($result) > 0) {
                    $errors[] = "This name is already taken. Please choose a different one.";
                }
                mysqli_stmt_close($stmt);
            } else {
                $errors[] = "Database error. Please try again.";
                error_log("Prepare failed: " . mysqli_error($conn));
            }
            
            // Check if section is already taken
            if (empty($errors)) {
                $checkSectionSql = "SELECT id FROM users WHERE section = ? LIMIT 1";
                $stmt = mysqli_prepare($conn, $checkSectionSql);
                
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "s", $section);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);
                    
                    if (mysqli_num_rows($result) > 0) {
                        $errors[] = "This section is already taken. Please select another one.";
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    $errors[] = "Database error. Please try again.";
                    error_log("Prepare failed: " . mysqli_error($conn));
                }
            }
        }
        
        // If no errors, proceed with registration
        if (empty($errors)) {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (employee_id, full_name, section, password) VALUES (?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "ssss", $employeeId, $fullName, $section, $passwordHash);
                
                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    $_SESSION['registration_success'] = true;
                    header("Location: Login.php");
                    exit();
                } else {
                    $errors[] = "Registration failed. Please try again.";
                    error_log("Database insert error: " . mysqli_error($conn));
                }
                mysqli_stmt_close($stmt);
            } else {
                $errors[] = "Database error. Please try again.";
                error_log("Prepare failed: " . mysqli_error($conn));
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
    <title>Register | My Smart ID</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2E7D32;
            --primary-dark: #1B5E20;
            --primary-light: #4CAF50;
            --primary-lighter: #66BB6A;
            --primary-pale: #E8F5E9;
            --white: #FFFFFF;
            --gray-50: #FAFAFA;
            --gray-100: #F5F5F5;
            --gray-200: #EEEEEE;
            --gray-300: #E0E0E0;
            --gray-400: #BDBDBD;
            --gray-500: #9E9E9E;
            --gray-600: #757575;
            --gray-700: #616161;
            --gray-800: #424242;
            --gray-900: #212121;
            --error: #D32F2F;
            --error-light: #FFEBEE;
            --success: #388E3C;
            --success-light: #E8F5E9;
            --warning: #F57F17;
            --warning-light: #FFF8E1;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, #1B5E20 0%, #2E7D32 30%, #43A047 60%, #66BB6A 100%);
            padding: 30px;
            overflow: hidden;
            position: relative;
        }

        /* Animated background circles */
        .bg-circle {
            position: absolute;
            border-radius: 50%;
            opacity: 0.08;
            background: white;
            animation: float 20s infinite ease-in-out;
        }

        .bg-circle:nth-child(1) {
            width: 400px;
            height: 400px;
            top: -100px;
            left: -100px;
            animation-delay: 0s;
        }

        .bg-circle:nth-child(2) {
            width: 300px;
            height: 300px;
            bottom: -80px;
            right: -80px;
            animation-delay: -5s;
        }

        .bg-circle:nth-child(3) {
            width: 200px;
            height: 200px;
            top: 50%;
            right: 10%;
            animation-delay: -10s;
        }

        .bg-circle:nth-child(4) {
            width: 150px;
            height: 150px;
            bottom: 20%;
            left: 15%;
            animation-delay: -15s;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0) scale(1); }
            25% { transform: translateY(-30px) scale(1.05); }
            50% { transform: translateY(0) scale(1); }
            75% { transform: translateY(30px) scale(0.95); }
        }

        .register-card {
            width: 100%;
            max-width: 650px;
            background: white;
            border-radius: 25px;
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            position: relative;
        }

        /* Header */
        .register-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            padding: 3rem 3rem 2rem;
            text-align: center;
            color: white;
        }

        .register-header .logo-sm {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: white;
            margin: 0 auto 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }

        .register-header .logo-sm img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .register-header h2 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .register-header p {
            font-size: 1rem;
            opacity: 0.85;
        }

        /* Progress bar */
        .step-progress {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 1.25rem 2rem 0.75rem;
            gap: 0;
            background: white;
        }

        .step-dot {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--gray-200);
            color: var(--gray-500);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: 700;
            transition: all 0.4s ease;
            flex-shrink: 0;
            position: relative;
        }

        .step-dot.active {
            background: var(--primary);
            color: white;
            box-shadow: 0 3px 12px rgba(46, 125, 50, 0.4);
            transform: scale(1.1);
        }

        .step-dot.completed {
            background: var(--primary-light);
            color: white;
        }

        .step-line {
            height: 3px;
            width: 30px;
            background: var(--gray-200);
            transition: background 0.4s ease;
            flex-shrink: 0;
        }

        .step-line.completed {
            background: var(--primary-light);
        }

        /* Form body */
        .register-body {
            padding: 2.5rem 3.5rem 3rem;
        }

        /* Steps */
        .form-step {
            display: none;
            animation: stepFadeIn 0.35s ease-out;
        }

        .form-step.active {
            display: block;
        }

        @keyframes stepFadeIn {
            from { opacity: 0; transform: translateX(15px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .step-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 0.35rem;
        }

        .step-desc {
            font-size: 0.95rem;
            color: var(--gray-500);
            margin-bottom: 1.25rem;
        }

        /* Form elements */
        .form-label {
            display: block;
            font-size: 1rem;
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 0.5rem;
        }

        .form-control, .form-select {
            width: 100%;
            padding: 1rem 1.25rem;
            font-size: 1.1rem;
            font-family: 'Poppins', sans-serif;
            color: var(--gray-800);
            background: var(--gray-50);
            border: 2px solid var(--gray-200);
            border-radius: 12px;
            transition: all 0.25s ease;
            outline: none;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            background: white;
            box-shadow: 0 0 0 4px rgba(46, 125, 50, 0.12);
        }

        .form-control.is-valid {
            border-color: var(--success);
            background: var(--success-light);
        }

        .form-control.is-invalid, .form-select.is-invalid {
            border-color: var(--error);
            background: var(--error-light);
        }

        .form-control::placeholder {
            color: var(--gray-400);
        }

        /* Feedback messages */
        .field-feedback {
            font-size: 0.8rem;
            margin-top: 0.4rem;
            display: flex;
            align-items: center;
            gap: 0.35rem;
            min-height: 1.4rem;
        }

        .field-feedback.success { color: var(--success); }
        .field-feedback.error { color: var(--error); }
        .field-feedback.checking { color: var(--gray-500); }

        .field-feedback .spinner-sm {
            width: 14px;
            height: 14px;
            border: 2px solid var(--gray-300);
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
            display: inline-block;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Password strength */
        .strength-bar-container {
            height: 6px;
            background: var(--gray-200);
            border-radius: 3px;
            margin-top: 0.75rem;
            overflow: hidden;
        }

        .strength-bar {
            height: 100%;
            border-radius: 3px;
            transition: width 0.3s ease, background 0.3s ease;
            width: 0%;
        }

        .strength-label {
            font-size: 0.8rem;
            margin-top: 0.35rem;
            font-weight: 600;
        }

        .strength-label.weak { color: var(--error); }
        .strength-label.medium { color: var(--warning); }
        .strength-label.strong { color: var(--success); }

        .password-requirements {
            margin-top: 0.75rem;
            padding: 0.75rem 1rem;
            background: var(--gray-50);
            border-radius: 10px;
            font-size: 0.78rem;
        }

        .password-requirements .req {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.15rem 0;
            color: var(--gray-500);
            transition: color 0.2s ease;
        }

        .password-requirements .req.met {
            color: var(--success);
        }

        .password-requirements .req i {
            font-size: 0.85rem;
        }

        /* Password toggle */
        .password-wrapper {
            position: relative;
        }

        .password-wrapper .form-control {
            padding-right: 3rem;
        }

        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--gray-500);
            cursor: pointer;
            padding: 4px;
            font-size: 1.1rem;
            transition: color 0.2s ease;
        }

        .password-toggle:hover {
            color: var(--gray-800);
        }

        /* Buttons */
        .step-buttons {
            display: flex;
            gap: 0.75rem;
            margin-top: 1.5rem;
        }

        .btn-step {
            flex: 1;
            padding: 0.85rem 1.5rem;
            font-size: 1rem;
            font-weight: 600;
            font-family: 'Poppins', sans-serif;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.25s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-next {
            background: var(--primary);
            color: white;
            box-shadow: 0 4px 12px rgba(46, 125, 50, 0.25);
        }

        .btn-next:hover:not(:disabled) {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(46, 125, 50, 0.3);
        }

        .btn-next:disabled {
            background: var(--gray-300);
            color: var(--gray-500);
            cursor: not-allowed;
            box-shadow: none;
        }

        .btn-back {
            background: var(--gray-100);
            color: var(--gray-700);
            flex: 0 0 auto;
            padding: 0.85rem 1.25rem;
        }

        .btn-back:hover {
            background: var(--gray-200);
        }

        .btn-submit {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(46, 125, 50, 0.3);
            width: 100%;
            padding: 1rem 1.5rem;
            font-size: 1.1rem;
            font-weight: 700;
            font-family: 'Poppins', sans-serif;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.25s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 1.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

            .btn-submit:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 25px rgba(46, 125, 50, 0.35);
            }

            /* Server error alert */
        .server-error {
            background: var(--error-light);
            border-left: 4px solid var(--error);
            color: var(--error);
            padding: 0.85rem 1.15rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-size: 0.85rem;
            animation: stepFadeIn 0.3s ease;
        }

        /* Footer link */
        .register-footer {
            text-align: center;
            padding: 0 2.5rem 1.75rem;
            font-size: 0.9rem;
            color: var(--gray-600);
        }

        .footer-links {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .back-home-link {
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
        }

        .back-home-link:hover {
            color: var(--primary-dark);
            transform: translateY(-1px);
        }

        .separator {
            color: var(--gray-400);
            font-weight: 400;
        }

        .register-footer a:not(.back-home-link) {
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .register-footer a:not(.back-home-link):hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        /* Review step */
        .review-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--gray-100);
        }

        .review-item:last-child { border-bottom: none; }

        .review-label {
            font-size: 0.85rem;
            color: var(--gray-500);
            font-weight: 500;
        }

        .review-value {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--gray-900);
        }

        /* Responsive */
        @media (max-width: 576px) {
            .register-card { margin: 0.5rem; border-radius: 16px; }
            .register-header { padding: 1.5rem 1.5rem 1.25rem; }
            .register-body { padding: 1.25rem 1.5rem 1.5rem; }
            .register-footer { padding: 0 1.5rem 1.5rem; }
            .step-progress { padding: 1rem 1.5rem 0.5rem; }
        }
    </style>
</head>
<body>
    <!-- Animated background circles -->
    <div class="bg-circle"></div>
    <div class="bg-circle"></div>
    <div class="bg-circle"></div>
    <div class="bg-circle"></div>
    
    <div class="register-card">
        <!-- Header -->
        <div class="register-header">
            <div class="logo-sm">
                <img src="logo.png" alt="Logo">
            </div>
            <h2>Create Your Account</h2>
            <p>Set up your advisor account in a few easy steps</p>
        </div>

        <!-- Step Progress -->
        <div class="step-progress" id="stepProgress">
            <div class="step-dot active" data-step="1">1</div>
            <div class="step-line"></div>
            <div class="step-dot" data-step="2">2</div>
            <div class="step-line"></div>
            <div class="step-dot" data-step="3">3</div>
            <div class="step-line"></div>
            <div class="step-dot" data-step="4">4</div>
            <div class="step-line"></div>
            <div class="step-dot" data-step="5">5</div>
            <div class="step-line"></div>
            <div class="step-dot" data-step="6">6</div>
        </div>

        <!-- Form Body -->
        <div class="register-body">
            <?php if (!empty($errors)): ?>
                <div class="server-error">
                    <?php foreach ($errors as $error): ?>
                        <div><i class="bi bi-exclamation-circle me-1"></i><?php echo htmlspecialchars($error); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form action="registration.php" method="post" id="registrationForm" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                <!-- Step 1: Employee ID -->
                <div class="form-step active" data-step="1">
                    <h3 class="step-title">Employee ID</h3>
                    <p class="step-desc">Enter your 7-digit employee identification number</p>
                    <div>
                        <label for="employee_id" class="form-label">Employee ID</label>
                        <input type="text" class="form-control" id="employee_id" name="employee_id"
                               pattern="\d{7}" maxlength="7"
                               oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 7)"
                               placeholder="e.g. 1234567"
                               value="<?php echo htmlspecialchars($employeeId ?? ''); ?>">
                        <div class="field-feedback" id="employeeIdFeedback"></div>
                    </div>
                    <div class="step-buttons">
                        <button type="button" class="btn-step btn-next" id="btnNext1" disabled onclick="nextStep(2)">
                            Next <i class="bi bi-arrow-right"></i>
                        </button>
                    </div>
                </div>

                <!-- Step 2: Full Name -->
                <div class="form-step" data-step="2">
                    <h3 class="step-title">Full Name</h3>
                    <p class="step-desc">Enter your complete name as it appears on official records</p>
                    <div>
                        <label for="fullname" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="fullname" name="fullname"
                               placeholder="e.g. Juan Dela Cruz"
                               value="<?php echo htmlspecialchars($fullName ?? ''); ?>">
                        <div class="field-feedback" id="fullnameFeedback"></div>
                    </div>
                    <div class="step-buttons">
                        <button type="button" class="btn-step btn-back" onclick="prevStep(1)">
                            <i class="bi bi-arrow-left"></i>
                        </button>
                        <button type="button" class="btn-step btn-next" id="btnNext2" disabled onclick="nextStep(3)">
                            Next <i class="bi bi-arrow-right"></i>
                        </button>
                    </div>
                </div>

                <!-- Step 3: Section -->
                <div class="form-step" data-step="3">
                    <h3 class="step-title">Section</h3>
                    <p class="step-desc">Select the section you are assigned to advise</p>
                    <div>
                        <label for="section" class="form-label">Section</label>
                        <select class="form-select" id="section" name="section[]">
                            <option value="" selected disabled>-- Select your section --</option>
                            <?php foreach ($sections as $sec): ?>
                                <option value="<?php echo htmlspecialchars($sec); ?>"><?php echo htmlspecialchars($sec); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="field-feedback" id="sectionFeedback"></div>
                    </div>
                    <div class="step-buttons">
                        <button type="button" class="btn-step btn-back" onclick="prevStep(2)">
                            <i class="bi bi-arrow-left"></i>
                        </button>
                        <button type="button" class="btn-step btn-next" id="btnNext3" disabled onclick="nextStep(4)">
                            Next <i class="bi bi-arrow-right"></i>
                        </button>
                    </div>
                </div>

                <!-- Step 4: Password -->
                <div class="form-step" data-step="4">
                    <h3 class="step-title">Create Password</h3>
                    <p class="step-desc">Choose a strong password to secure your account</p>
                    <div>
                        <label for="password" class="form-label">Password</label>
                        <div class="password-wrapper">
                            <input type="password" class="form-control" id="password" name="password"
                                   placeholder="Create a strong password">
                            <button type="button" class="password-toggle" id="togglePassword">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="strength-bar-container">
                            <div class="strength-bar" id="strengthBar"></div>
                        </div>
                        <div class="strength-label" id="strengthLabel"></div>
                        <div class="password-requirements">
                            <div class="req" id="reqLength"><i class="bi bi-circle"></i> At least 8 characters</div>
                            <div class="req" id="reqUpper"><i class="bi bi-circle"></i> Uppercase letter (A-Z)</div>
                            <div class="req" id="reqLower"><i class="bi bi-circle"></i> Lowercase letter (a-z)</div>
                            <div class="req" id="reqNumber"><i class="bi bi-circle"></i> Number (0-9)</div>
                            <div class="req" id="reqSpecial"><i class="bi bi-circle"></i> Special character (!@#$%^&*)</div>
                        </div>
                    </div>
                    <div class="step-buttons">
                        <button type="button" class="btn-step btn-back" onclick="prevStep(3)">
                            <i class="bi bi-arrow-left"></i>
                        </button>
                        <button type="button" class="btn-step btn-next" id="btnNext4" disabled onclick="nextStep(5)">
                            Next <i class="bi bi-arrow-right"></i>
                        </button>
                    </div>
                </div>

                <!-- Step 5: Confirm Password -->
                <div class="form-step" data-step="5">
                    <h3 class="step-title">Confirm Password</h3>
                    <p class="step-desc">Re-enter your password to confirm</p>
                    <div>
                        <label for="repeat_password" class="form-label">Confirm Password</label>
                        <div class="password-wrapper">
                            <input type="password" class="form-control" id="repeat_password" name="repeat_password"
                                   placeholder="Re-enter your password">
                            <button type="button" class="password-toggle" id="toggleConfirmPassword">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="field-feedback" id="confirmPasswordFeedback"></div>
                    </div>
                    <div class="step-buttons">
                        <button type="button" class="btn-step btn-back" onclick="prevStep(4)">
                            <i class="bi bi-arrow-left"></i>
                        </button>
                        <button type="button" class="btn-step btn-next" id="btnNext5" disabled onclick="nextStep(6)">
                            Next <i class="bi bi-arrow-right"></i>
                        </button>
                    </div>
                </div>

                <!-- Step 6: Review & Submit -->
                <div class="form-step" data-step="6">
                    <h3 class="step-title">Review & Create Account</h3>
                    <p class="step-desc">Please verify your information before submitting</p>
                    <div style="background: var(--gray-50); border-radius: 12px; padding: 1rem 1.25rem; margin-bottom: 0.5rem;">
                        <div class="review-item">
                            <span class="review-label">Employee ID</span>
                            <span class="review-value" id="reviewEmployeeId"></span>
                        </div>
                        <div class="review-item">
                            <span class="review-label">Full Name</span>
                            <span class="review-value" id="reviewFullName"></span>
                        </div>
                        <div class="review-item">
                            <span class="review-label">Section</span>
                            <span class="review-value" id="reviewSection"></span>
                        </div>
                        <div class="review-item">
                            <span class="review-label">Password</span>
                            <span class="review-value" style="color: var(--success);"><i class="bi bi-check-circle-fill me-1"></i>Strong</span>
                        </div>
                    </div>
                    <div class="step-buttons" style="flex-direction: column; gap: 0.5rem;">
                        <button type="submit" name="submit" class="btn-submit">
                            <i class="bi bi-person-plus-fill"></i> Create Account
                        </button>
                        <button type="button" class="btn-step btn-back" style="width: 100%; flex: unset;" onclick="prevStep(5)">
                            <i class="bi bi-arrow-left me-1"></i> Go Back & Edit
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Footer -->
        <div class="register-footer">
            <div class="footer-links">
                <a href="homepage.php" class="back-home-link">
                    <i class="bi bi-house-door me-1"></i> Back to Homepage
                </a>
                <span class="separator">|</span>
                <a href="Login.php">Sign In</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        let currentStep = 1;
        let checkTimeout = null;
        let passwordIsStrong = false;

        const employeeIdInput = document.getElementById('employee_id');
        const fullnameInput = document.getElementById('fullname');
        const sectionSelect = document.getElementById('section');
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('repeat_password');

        // ===== STEP NAVIGATION =====
        window.nextStep = function(step) {
            showStep(step);
        };

        window.prevStep = function(step) {
            showStep(step);
        };

        function showStep(step) {
            document.querySelectorAll('.form-step').forEach(s => s.classList.remove('active'));
            document.querySelector(`.form-step[data-step="${step}"]`).classList.add('active');

            // Update progress dots
            document.querySelectorAll('.step-dot').forEach(dot => {
                const dotStep = parseInt(dot.dataset.step);
                dot.classList.remove('active', 'completed');
                if (dotStep === step) dot.classList.add('active');
                else if (dotStep < step) dot.classList.add('completed');
            });

            // Update progress lines
            const lines = document.querySelectorAll('.step-line');
            lines.forEach((line, i) => {
                line.classList.toggle('completed', i < step - 1);
            });

            currentStep = step;

            // Populate review on step 6
            if (step === 6) {
                document.getElementById('reviewEmployeeId').textContent = employeeIdInput.value;
                document.getElementById('reviewFullName').textContent = fullnameInput.value;
                document.getElementById('reviewSection').textContent = sectionSelect.value;
            }
        }

        // ===== AJAX CHECK FUNCTION =====
        function checkAvailability(field, value, feedbackEl, btnEl) {
            feedbackEl.innerHTML = '<span class="spinner-sm"></span> Checking...';
            feedbackEl.className = 'field-feedback checking';
            btnEl.disabled = true;

            fetch('check_availability.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `field=${encodeURIComponent(field)}&value=${encodeURIComponent(value)}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.available) {
                    feedbackEl.innerHTML = '<i class="bi bi-check-circle-fill"></i> ' + data.message;
                    feedbackEl.className = 'field-feedback success';
                    btnEl.disabled = false;
                } else {
                    feedbackEl.innerHTML = '<i class="bi bi-x-circle-fill"></i> ' + data.message;
                    feedbackEl.className = 'field-feedback error';
                    btnEl.disabled = true;
                }
            })
            .catch(() => {
                feedbackEl.innerHTML = '<i class="bi bi-exclamation-circle"></i> Could not verify. Try again.';
                feedbackEl.className = 'field-feedback error';
                btnEl.disabled = true;
            });
        }

        // ===== STEP 1: Employee ID =====
        employeeIdInput.addEventListener('input', function() {
            clearTimeout(checkTimeout);
            const val = this.value.trim();
            const feedback = document.getElementById('employeeIdFeedback');
            const btn = document.getElementById('btnNext1');

            if (val.length < 7) {
                feedback.innerHTML = val.length > 0 ? `<i class="bi bi-info-circle"></i> ${7 - val.length} more digit(s) needed` : '';
                feedback.className = 'field-feedback checking';
                btn.disabled = true;
                this.classList.remove('is-valid', 'is-invalid');
                return;
            }

            if (!/^\d{7}$/.test(val)) {
                feedback.innerHTML = '<i class="bi bi-x-circle-fill"></i> Must be exactly 7 digits';
                feedback.className = 'field-feedback error';
                btn.disabled = true;
                this.classList.add('is-invalid');
                this.classList.remove('is-valid');
                return;
            }

            this.classList.remove('is-invalid');
            checkTimeout = setTimeout(() => checkAvailability('employee_id', val, feedback, btn), 400);
        });

        // ===== STEP 2: Full Name =====
        fullnameInput.addEventListener('input', function() {
            clearTimeout(checkTimeout);
            const val = this.value.trim();
            const feedback = document.getElementById('fullnameFeedback');
            const btn = document.getElementById('btnNext2');

            if (val.length < 2) {
                feedback.innerHTML = val.length > 0 ? '<i class="bi bi-info-circle"></i> Name is too short' : '';
                feedback.className = 'field-feedback checking';
                btn.disabled = true;
                this.classList.remove('is-valid', 'is-invalid');
                return;
            }

            this.classList.remove('is-invalid');
            checkTimeout = setTimeout(() => checkAvailability('full_name', val, feedback, btn), 500);
        });

        // ===== STEP 3: Section =====
        sectionSelect.addEventListener('change', function() {
            const val = this.value;
            const feedback = document.getElementById('sectionFeedback');
            const btn = document.getElementById('btnNext3');

            if (!val) {
                feedback.innerHTML = '';
                btn.disabled = true;
                return;
            }

            checkAvailability('section', val, feedback, btn);
        });

        // ===== STEP 4: Password =====
        passwordInput.addEventListener('input', function() {
            const val = this.value;
            const bar = document.getElementById('strengthBar');
            const label = document.getElementById('strengthLabel');
            const btn = document.getElementById('btnNext4');

            const hasLength = val.length >= 8;
            const hasUpper = /[A-Z]/.test(val);
            const hasLower = /[a-z]/.test(val);
            const hasNumber = /[0-9]/.test(val);
            const hasSpecial = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?~`]/.test(val);

            // Update requirement indicators
            updateReq('reqLength', hasLength);
            updateReq('reqUpper', hasUpper);
            updateReq('reqLower', hasLower);
            updateReq('reqNumber', hasNumber);
            updateReq('reqSpecial', hasSpecial);

            const metCount = [hasLength, hasUpper, hasLower, hasNumber, hasSpecial].filter(Boolean).length;
            passwordIsStrong = metCount === 5;

            if (val.length === 0) {
                bar.style.width = '0%';
                bar.style.background = '';
                label.textContent = '';
                label.className = 'strength-label';
                btn.disabled = true;
                return;
            }

            if (metCount <= 2) {
                bar.style.width = '33%';
                bar.style.background = '#D32F2F';
                label.textContent = 'Weak';
                label.className = 'strength-label weak';
            } else if (metCount <= 4) {
                bar.style.width = '66%';
                bar.style.background = '#F57F17';
                label.textContent = 'Medium';
                label.className = 'strength-label medium';
            } else {
                bar.style.width = '100%';
                bar.style.background = '#388E3C';
                label.textContent = 'Strong';
                label.className = 'strength-label strong';
            }

            btn.disabled = !passwordIsStrong;
        });

        function updateReq(id, met) {
            const el = document.getElementById(id);
            if (met) {
                el.classList.add('met');
                el.querySelector('i').className = 'bi bi-check-circle-fill';
            } else {
                el.classList.remove('met');
                el.querySelector('i').className = 'bi bi-circle';
            }
        }

        // ===== STEP 5: Confirm Password =====
        confirmPasswordInput.addEventListener('input', function() {
            const val = this.value;
            const feedback = document.getElementById('confirmPasswordFeedback');
            const btn = document.getElementById('btnNext5');

            if (val.length === 0) {
                feedback.innerHTML = '';
                btn.disabled = true;
                this.classList.remove('is-valid', 'is-invalid');
                return;
            }

            if (val === passwordInput.value) {
                feedback.innerHTML = '<i class="bi bi-check-circle-fill"></i> Passwords match';
                feedback.className = 'field-feedback success';
                this.classList.add('is-valid');
                this.classList.remove('is-invalid');
                btn.disabled = false;
            } else {
                feedback.innerHTML = '<i class="bi bi-x-circle-fill"></i> Passwords do not match';
                feedback.className = 'field-feedback error';
                this.classList.add('is-invalid');
                this.classList.remove('is-valid');
                btn.disabled = true;
            }
        });

        // ===== PASSWORD TOGGLES =====
        document.getElementById('togglePassword').addEventListener('click', function() {
            const type = passwordInput.type === 'password' ? 'text' : 'password';
            passwordInput.type = type;
            this.querySelector('i').className = type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
        });

        document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
            const type = confirmPasswordInput.type === 'password' ? 'text' : 'password';
            confirmPasswordInput.type = type;
            this.querySelector('i').className = type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
        });
    });
    </script>
</body>
</html>