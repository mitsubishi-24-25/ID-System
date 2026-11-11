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

// Check for successful registration
if (isset($_SESSION['registration_success']) && $_SESSION['registration_success']) {
    $registrationSuccess = true;
    unset($_SESSION['registration_success']); // Clear the flag
} else {
    $registrationSuccess = false;
}

if (isset($_POST["login"])) {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token'])) {
        $errors[] = "Security token missing. Please refresh the page and try again.";
        error_log("CSRF Error: Token missing. Session token exists: " . (isset($_SESSION['csrf_token']) ? 'Yes' : 'No') . ", Post token exists: " . (isset($_POST['csrf_token']) ? 'Yes' : 'No'));
    } elseif ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = "Security token mismatch. Please refresh the page and try again.";
        error_log("CSRF Error: Token mismatch. Session: " . ($_SESSION['csrf_token'] ?? 'empty') . ", Post: " . ($_POST['csrf_token'] ?? 'empty'));
    } else {
        $employee_id = trim(htmlspecialchars($_POST['employee_id'] ?? ''));
        $password = $_POST["password"] ?? '';
        
        if (empty($employee_id) || empty($password)) {
            $errors[] = "Please fill in all fields";
        } elseif (!preg_match('/^\d{7}$/', $employee_id)) {
            $errors[] = "Employee ID must be exactly 7 digits";
        } else {
            $sql = "SELECT * FROM users WHERE employee_id = ? LIMIT 1"; 
            $stmt = mysqli_stmt_init($conn);
            
            if (mysqli_stmt_prepare($stmt, $sql)) {
                mysqli_stmt_bind_param($stmt, "s", $employee_id);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $user = mysqli_fetch_array($result, MYSQLI_ASSOC);
                
                if ($user) {
                    if (password_verify($password, $user["password"])) {
                        // Regenerate session ID to prevent session fixation
                        session_regenerate_id(true);
                        
                        $_SESSION["user_id"] = $user["id"]; 
                        $_SESSION["full_name"] = $user["full_name"];
                        
                        // Clear any existing error messages
                        unset($_SESSION['error']);
                        
                        // Set success message for the next page load
                        $_SESSION['login_success'] = true;
                        
                        header("Location: index.php");
                        exit(); 
                    } else {
                        $errors[] = "Incorrect password";
                    }
                } else {
                    $errors[] = "Employee ID not found";
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
    <title>Login | My Smart ID</title>
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

        .login-card {
            width: 100%;
            max-width: 580px;
            background: white;
            border-radius: 25px;
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }

        /* Header */
        .login-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            padding: 3.5rem 3.5rem 2.5rem;
            text-align: center;
            color: white;
        }

        .login-header .logo-sm {
            width: 130px;
            height: 130px;
            border-radius: 50%;
            background: white;
            margin: 0 auto 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
            overflow: hidden;
        }

        .login-header .logo-sm img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .login-header h2 {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .login-header p {
            font-size: 1.1rem;
            opacity: 0.85;
        }

        /* Body */
        .login-body {
            padding: 2.5rem 3.5rem 2.5rem;
        }

        /* Step indicators */
        .step-indicators {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-bottom: 1.75rem;
        }

        .step-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: var(--gray-200);
            transition: all 0.3s ease;
        }

        .step-indicator.active {
            background: var(--primary);
            width: 30px;
            border-radius: 5px;
        }

        .step-indicator.completed {
            background: var(--primary-light);
        }

        /* Steps */
        .login-step {
            display: none;
            animation: stepFadeIn 0.35s ease-out;
        }

        .login-step.active {
            display: block;
        }

        @keyframes stepFadeIn {
            from { opacity: 0; transform: translateX(15px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .step-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 0.25rem;
        }

        .step-desc {
            font-size: 0.9rem;
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

        .form-control {
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

        .form-control:focus {
            border-color: var(--primary);
            background: white;
            box-shadow: 0 0 0 4px rgba(46, 125, 50, 0.12);
        }

        .form-control::placeholder {
            color: var(--gray-400);
        }

        /* Password wrapper */
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

        /* Error alert */
        .login-error {
            background: var(--error-light);
            border-left: 4px solid var(--error);
            color: var(--error);
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-bottom: 1.25rem;
            font-size: 0.85rem;
            animation: stepFadeIn 0.3s ease;
        }

        /* Employee ID display */
        .id-display {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            background: var(--primary-pale);
            border-radius: 10px;
            margin-bottom: 1.25rem;
        }

        .id-display .id-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            flex-shrink: 0;
        }

        .id-display .id-text {
            font-size: 0.85rem;
            color: var(--gray-700);
        }

        .id-display .id-value {
            font-weight: 700;
            color: var(--primary-dark);
            font-size: 0.95rem;
        }

        .id-display .id-change {
            margin-left: auto;
            background: none;
            border: none;
            color: var(--primary);
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            padding: 4px 8px;
            border-radius: 6px;
            transition: background 0.2s ease;
        }

        .id-display .id-change:hover {
            background: rgba(46, 125, 50, 0.1);
        }

        /* Buttons */
        .btn-login {
            width: 100%;
            padding: 0.9rem 1.5rem;
            font-size: 1.05rem;
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
        }

        .btn-next-login {
            background: var(--primary);
            color: white;
            box-shadow: 0 4px 12px rgba(46, 125, 50, 0.25);
        }

        .btn-next-login:hover:not(:disabled) {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(46, 125, 50, 0.3);
        }

        .btn-next-login:disabled {
            background: var(--gray-300);
            color: var(--gray-500);
            cursor: not-allowed;
            box-shadow: none;
        }

        .btn-signin {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(46, 125, 50, 0.3);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-signin:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(46, 125, 50, 0.35);
        }

        /* Footer */
        .login-footer {
            text-align: center;
            padding: 0 2.5rem 2rem;
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

        .login-footer a:not(.back-home-link) {
            color: var(--primary);
            font-weight: 600;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .login-footer a:not(.back-home-link):hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        /* Responsive */
        @media (max-width: 576px) {
            .login-card { margin: 0.5rem; border-radius: 16px; }
            .login-header { padding: 2rem 1.5rem 1.5rem; }
            .login-body { padding: 1.5rem 1.5rem 1.25rem; }
            .login-footer { padding: 0 1.5rem 1.5rem; }
        }
    </style>
</head>
<body>
    <!-- Animated background circles -->
    <div class="bg-circle"></div>
    <div class="bg-circle"></div>
    <div class="bg-circle"></div>
    <div class="bg-circle"></div>
    
    <div class="login-card">
        <!-- Header -->
        <div class="login-header">
            <div class="logo-sm">
                <img src="logo.png" alt="Logo">
            </div>
            <h2>Welcome Back!</h2>
            <p>Sign in to access your account</p>
        </div>

        <!-- Body -->
        <div class="login-body">
            <!-- Step indicators -->
            <div class="step-indicators">
                <div class="step-indicator active" id="indicator1"></div>
                <div class="step-indicator" id="indicator2"></div>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="login-error">
                    <?php foreach ($errors as $error): ?>
                        <div><i class="bi bi-exclamation-circle me-1"></i><?php echo htmlspecialchars($error); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form action="Login.php" method="post" id="loginForm">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                <!-- Step 1: Employee ID -->
                <div class="login-step active" id="loginStep1">
                    <h3 class="step-title">Employee ID</h3>
                    <p class="step-desc">Enter your 7-digit employee identification number</p>
                    <div>
                        <label for="employee_id" class="form-label">Employee ID</label>
                        <input type="text" class="form-control" id="employee_id" name="employee_id"
                               pattern="\d{7}" maxlength="7"
                               oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 7)"
                               placeholder="e.g. 1234567"
                               required>
                    </div>
                    <button type="button" class="btn-login btn-next-login" id="btnToStep2" disabled>
                        Next <i class="bi bi-arrow-right"></i>
                    </button>
                </div>

                <!-- Step 2: Password -->
                <div class="login-step" id="loginStep2">
                    <h3 class="step-title">Password</h3>
                    <p class="step-desc">Enter your account password</p>

                    <div class="id-display" id="idDisplay">
                        <div class="id-icon"><i class="bi bi-person-badge"></i></div>
                        <div>
                            <div class="id-text">Employee ID</div>
                            <div class="id-value" id="displayEmployeeId"></div>
                        </div>
                        <button type="button" class="id-change" id="btnBackToStep1">Change</button>
                    </div>

                    <div>
                        <label for="password" class="form-label">Password</label>
                        <div class="password-wrapper">
                            <input type="password" class="form-control" id="password" name="password"
                                   placeholder="Enter your password"
                                   required autocomplete="current-password">
                            <button type="button" class="password-toggle" id="togglePassword">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                    <button type="submit" name="login" class="btn-login btn-signin">
                        <i class="bi bi-box-arrow-in-right"></i> Sign In
                    </button>
                </div>
            </form>
        </div>

        <!-- Footer -->
        <div class="login-footer">
            <div class="footer-links">
                <a href="homepage.php" class="back-home-link">
                    <i class="bi bi-house-door me-1"></i> Back to Homepage
                </a>
                <span class="separator">|</span>
                <a href="registration.php">Create an account</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    <?php if ($registrationSuccess): ?>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            title: 'Registration Successful!',
            text: 'Your account has been created successfully. Please log in with your credentials.',
            icon: 'success',
            confirmButtonColor: '#2E7D32',
            confirmButtonText: 'Continue',
            allowOutsideClick: false
        });
    });
    <?php endif; ?>

    document.addEventListener('DOMContentLoaded', function() {
        const employeeIdInput = document.getElementById('employee_id');
        const passwordInput = document.getElementById('password');
        const btnToStep2 = document.getElementById('btnToStep2');
        const btnBackToStep1 = document.getElementById('btnBackToStep1');
        const step1 = document.getElementById('loginStep1');
        const step2 = document.getElementById('loginStep2');
        const indicator1 = document.getElementById('indicator1');
        const indicator2 = document.getElementById('indicator2');
        const displayEmployeeId = document.getElementById('displayEmployeeId');

        // Enable/disable Next button based on Employee ID input
        employeeIdInput.addEventListener('input', function() {
            btnToStep2.disabled = !/^\d{7}$/.test(this.value.trim());
        });

        // Allow Enter key to proceed to step 2
        employeeIdInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !btnToStep2.disabled) {
                e.preventDefault();
                goToStep2();
            }
        });

        // Go to Step 2
        btnToStep2.addEventListener('click', goToStep2);

        function goToStep2() {
            displayEmployeeId.textContent = employeeIdInput.value;
            step1.classList.remove('active');
            step2.classList.add('active');
            indicator1.classList.remove('active');
            indicator1.classList.add('completed');
            indicator2.classList.add('active');
            setTimeout(() => passwordInput.focus(), 100);
        }

        // Back to Step 1
        btnBackToStep1.addEventListener('click', function() {
            step2.classList.remove('active');
            step1.classList.add('active');
            indicator2.classList.remove('active');
            indicator1.classList.remove('completed');
            indicator1.classList.add('active');
            setTimeout(() => employeeIdInput.focus(), 100);
        });

        // Password toggle
        document.getElementById('togglePassword').addEventListener('click', function() {
            const type = passwordInput.type === 'password' ? 'text' : 'password';
            passwordInput.type = type;
            this.querySelector('i').className = type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
        });

        // If there are server errors, show step 2 if employee_id was filled
        <?php if (!empty($errors) && !empty($_POST['employee_id'])): ?>
        goToStep2();
        employeeIdInput.value = '<?php echo htmlspecialchars($_POST['employee_id'] ?? ''); ?>';
        displayEmployeeId.textContent = '<?php echo htmlspecialchars($_POST['employee_id'] ?? ''); ?>';
        <?php endif; ?>
    });
    </script>
</body>
</html>
