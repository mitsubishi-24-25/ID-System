<?php
session_start();
require_once 'database.php';

// CSRF Token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $response = ['success' => false, 'message' => 'Invalid CSRF token'];
    } else {
        // Get and sanitize form data
        $employee_id = trim($_POST['employee_id']);
        $full_name = trim($_POST['full_name']);
        $section = trim($_POST['section']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        // Server-side validation
        $errors = [];
        
        if (empty($employee_id)) {
            $errors[] = 'Employee ID is required';
        } elseif (!preg_match('/^[A-Za-z0-9]{4,20}$/', $employee_id)) {
            $errors[] = 'Employee ID must be 4-20 alphanumeric characters';
        }
        
        if (empty($full_name)) {
            $errors[] = 'Full Name is required';
        } elseif (!preg_match('/^[A-Za-z\s]{2,50}$/', $full_name)) {
            $errors[] = 'Full Name must be 2-50 letters and spaces only';
        }
        
        if (empty($section)) {
            $errors[] = 'Section is required';
        }
        
        if (empty($password)) {
            $errors[] = 'Password is required';
        } elseif (strlen($password) < 8 || !preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])/', $password)) {
            $errors[] = 'Password must be at least 8 characters with uppercase, lowercase, number, and special character';
        }
        
        if ($password !== $confirm_password) {
            $errors[] = 'Passwords do not match';
        }

        if (empty($errors)) {
            // Check if employee ID already exists
            $check_query = "SELECT id FROM users WHERE employee_id = ?";
            $result = executeQuery($check_query, [$employee_id], 's');
            if ($result->num_rows > 0) {
                $errors[] = 'Employee ID already exists';
            }
            
            // Check if full name already exists
            $check_query = "SELECT id FROM users WHERE full_name = ?";
            $result = executeQuery($check_query, [$full_name], 's');
            if ($result->num_rows > 0) {
                $errors[] = 'Full Name already exists';
            }
            
            // Check if section is already taken
            $check_query = "SELECT id FROM users WHERE section = ?";
            $result = executeQuery($check_query, [$section], 's');
            if ($result->num_rows > 0) {
                $errors[] = 'Section already taken';
            }
        }

        if (empty($errors)) {
            // Hash password and insert user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $insert_query = "INSERT INTO users (employee_id, full_name, section, password) VALUES (?, ?, ?, ?)";
            $result = executeQuery($insert_query, [$employee_id, $full_name, $section, $hashed_password], 'ssss');
            
            if ($result) {
                $response = ['success' => true, 'message' => 'Registration successful! Redirecting to login...'];
            } else {
                $response = ['success' => false, 'message' => 'Registration failed. Please try again.'];
            }
        } else {
            $response = ['success' => false, 'message' => implode('<br>', $errors)];
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Handle AJAX validation requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && in_array($_POST['action'], ['check_employee_id', 'check_full_name', 'check_section'])) {
    $response = ['success' => true, 'available' => true];
    
    if ($_POST['action'] === 'check_employee_id') {
        $employee_id = trim($_POST['employee_id']);
        if (!empty($employee_id)) {
            $query = "SELECT id FROM users WHERE employee_id = ?";
            $result = executeQuery($query, [$employee_id], 's');
            $response['available'] = $result->num_rows === 0;
        }
    } elseif ($_POST['action'] === 'check_full_name') {
        $full_name = trim($_POST['full_name']);
        if (!empty($full_name)) {
            $query = "SELECT id FROM users WHERE full_name = ?";
            $result = executeQuery($query, [$full_name], 's');
            $response['available'] = $result->num_rows === 0;
        }
    } elseif ($_POST['action'] === 'check_section') {
        $section = trim($_POST['section']);
        if (!empty($section)) {
            $query = "SELECT id FROM users WHERE section = ?";
            $result = executeQuery($query, [$section], 's');
            $response['available'] = $result->num_rows === 0;
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - Smart ID System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary-green: #28a745;
            --light-green: #d4edda;
            --dark-green: #1e7e34;
            --hover-green: #218838;
            --text-color: #333;
            --light-text-color: #f8f9fa;
            --bg-light: #f0f4f8;
            --card-bg: #ffffff;
            --border-color: #e0e0e0;
            --shadow-light: rgba(0, 0, 0, 0.1);
            --shadow-medium: rgba(0, 0, 0, 0.15);
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, var(--bg-light) 0%, var(--light-green) 100%);
            color: var(--text-color);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 0;
        }

        .registration-container {
            background-color: var(--card-bg);
            border-radius: 20px;
            box-shadow: 0 15px 35px var(--shadow-medium);
            overflow: hidden;
            max-width: 500px;
            width: 100%;
            animation: slideIn 0.5s ease-out;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .form-header {
            background: linear-gradient(135deg, var(--primary-green), var(--dark-green));
            color: var(--light-text-color);
            padding: 30px;
            text-align: center;
        }

        .form-header h2 {
            margin: 0;
            font-weight: 700;
            font-size: 1.8rem;
        }

        .form-header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
        }

        .form-body {
            padding: 40px 30px;
        }

        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 40px;
            position: relative;
        }

        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--border-color);
            color: var(--text-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            position: relative;
            z-index: 2;
            transition: all 0.3s ease;
        }

        .step.active {
            background-color: var(--primary-green);
            color: var(--light-text-color);
            transform: scale(1.1);
        }

        .step.completed {
            background-color: var(--dark-green);
            color: var(--light-text-color);
        }

        .step::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 100%;
            width: 60px;
            height: 2px;
            background-color: var(--border-color);
            transform: translateY(-50%);
            z-index: -1;
        }

        .step:last-child::before {
            display: none;
        }

        .step.completed::before {
            background-color: var(--dark-green);
        }

        .form-step {
            display: none;
            animation: fadeIn 0.3s ease-in;
        }

        .form-step.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateX(20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            font-weight: 600;
            color: var(--dark-green);
            margin-bottom: 8px;
            display: block;
        }

        .form-control {
            border: 2px solid var(--border-color);
            border-radius: 10px;
            padding: 12px 15px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background-color: var(--bg-light);
        }

        .form-control:focus {
            border-color: var(--primary-green);
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
            background-color: var(--card-bg);
        }

        .form-control.is-valid {
            border-color: #28a745;
            background-color: #f0fff4;
        }

        .form-control.is-invalid {
            border-color: #dc3545;
            background-color: #fff5f5;
        }

        .validation-feedback {
            margin-top: 5px;
            font-size: 0.875rem;
            display: none;
        }

        .validation-feedback.valid {
            color: #28a745;
            display: block;
        }

        .validation-feedback.invalid {
            color: #dc3545;
            display: block;
        }

        .password-strength {
            margin-top: 10px;
        }

        .strength-bar {
            height: 5px;
            border-radius: 3px;
            background-color: var(--border-color);
            overflow: hidden;
            margin-bottom: 5px;
        }

        .strength-fill {
            height: 100%;
            width: 0;
            transition: all 0.3s ease;
            border-radius: 3px;
        }

        .strength-fill.weak {
            width: 33%;
            background-color: #dc3545;
        }

        .strength-fill.medium {
            width: 66%;
            background-color: #ffc107;
        }

        .strength-fill.strong {
            width: 100%;
            background-color: #28a745;
        }

        .strength-text {
            font-size: 0.875rem;
            font-weight: 600;
        }

        .strength-text.weak {
            color: #dc3545;
        }

        .strength-text.medium {
            color: #ffc107;
        }

        .strength-text.strong {
            color: #28a745;
        }

        .btn-nav {
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .btn-prev {
            background-color: var(--border-color);
            color: var(--text-color);
        }

        .btn-prev:hover {
            background-color: #d0d0d0;
            transform: translateY(-2px);
        }

        .btn-next {
            background-color: var(--primary-green);
            color: var(--light-text-color);
        }

        .btn-next:hover {
            background-color: var(--hover-green);
            transform: translateY(-2px);
        }

        .btn-next:disabled {
            background-color: var(--border-color);
            color: #999;
            cursor: not-allowed;
            transform: none;
        }

        .btn-submit {
            background-color: var(--primary-green);
            color: var(--light-text-color);
            width: 100%;
            padding: 15px;
            font-size: 1.1rem;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
        }

        .btn-submit:hover {
            background-color: var(--hover-green);
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
        }

        .navigation-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
            gap: 15px;
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: var(--primary-green);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .back-link a:hover {
            color: var(--dark-green);
            text-decoration: underline;
        }

        /* Responsive adjustments */
        @media (max-width: 576px) {
            .form-body {
                padding: 30px 20px;
            }
            
            .step::before {
                width: 40px;
            }
            
            .navigation-buttons {
                flex-direction: column;
            }
            
            .btn-nav {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="registration-container">
        <div class="form-header">
            <h2>Create Account</h2>
            <p>Join Smart ID System</p>
        </div>
        
        <div class="form-body">
            <div class="step-indicator">
                <div class="step active" data-step="1">1</div>
                <div class="step" data-step="2">2</div>
                <div class="step" data-step="3">3</div>
                <div class="step" data-step="4">4</div>
                <div class="step" data-step="5">5</div>
                <div class="step" data-step="6">6</div>
            </div>

            <form id="registrationForm">
                <!-- Step 1: Employee ID -->
                <div class="form-step active" data-step="1">
                    <div class="form-group">
                        <label for="employee_id">Employee ID</label>
                        <input type="text" class="form-control" id="employee_id" name="employee_id" 
                               placeholder="Enter your employee ID" required>
                        <div class="validation-feedback" id="employee_id_feedback"></div>
                    </div>
                </div>

                <!-- Step 2: Full Name -->
                <div class="form-step" data-step="2">
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" 
                               placeholder="Enter your full name" required>
                        <div class="validation-feedback" id="full_name_feedback"></div>
                    </div>
                </div>

                <!-- Step 3: Section -->
                <div class="form-step" data-step="3">
                    <div class="form-group">
                        <label for="section">Section</label>
                        <select class="form-control" id="section" name="section" required>
                            <option value="">Select your section</option>
                            <option value="HR">Human Resources</option>
                            <option value="IT">Information Technology</option>
                            <option value="FIN">Finance</option>
                            <option value="MKT">Marketing</option>
                            <option value="OPS">Operations</option>
                            <option value="ADM">Administration</option>
                        </select>
                        <div class="validation-feedback" id="section_feedback"></div>
                    </div>
                </div>

                <!-- Step 4: Password -->
                <div class="form-step" data-step="4">
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password" name="password" 
                                   placeholder="Create a strong password" required>
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="password-strength">
                            <div class="strength-bar">
                                <div class="strength-fill" id="strengthFill"></div>
                            </div>
                            <div class="strength-text" id="strengthText">Password strength</div>
                        </div>
                        <small class="text-muted">
                            Must be at least 8 characters with uppercase, lowercase, number, and special character
                        </small>
                    </div>
                </div>

                <!-- Step 5: Confirm Password -->
                <div class="form-step" data-step="5">
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                   placeholder="Confirm your password" required>
                            <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="validation-feedback" id="confirm_password_feedback"></div>
                    </div>
                </div>

                <!-- Step 6: Review & Submit -->
                <div class="form-step" data-step="6">
                    <h4 class="mb-4">Review Your Information</h4>
                    <div class="mb-3">
                        <strong>Employee ID:</strong> <span id="review_employee_id"></span>
                    </div>
                    <div class="mb-3">
                        <strong>Full Name:</strong> <span id="review_full_name"></span>
                    </div>
                    <div class="mb-3">
                        <strong>Section:</strong> <span id="review_section"></span>
                    </div>
                    <div class="mb-4">
                        <strong>Password:</strong> ••••••••
                    </div>
                    <button type="submit" class="btn btn-submit">Create Account</button>
                </div>

                <div class="navigation-buttons">
                    <button type="button" class="btn-nav btn-prev" id="prevBtn" style="display: none;">
                        <i class="bi bi-arrow-left"></i> Previous
                    </button>
                    <button type="button" class="btn-nav btn-next" id="nextBtn">
                        Next <i class="bi bi-arrow-right"></i>
                    </button>
                </div>
            </form>

            <div class="back-link">
                <a href="home.php">← Back to Home</a>
            </div>
        </div>
    </div>

    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

    <script>
        // Form state management
        let currentStep = 1;
        const totalSteps = 6;
        const formData = {};

        // DOM elements
        const form = document.getElementById('registrationForm');
        const steps = document.querySelectorAll('.step');
        const formSteps = document.querySelectorAll('.form-step');
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        const csrfToken = document.querySelector('input[name="csrf_token"]').value;

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            updateStepIndicator();
            updateNavigationButtons();
        });

        // Step navigation
        function showStep(step) {
            formSteps.forEach(fs => fs.classList.remove('active'));
            document.querySelector(`.form-step[data-step="${step}"]`).classList.add('active');
            
            if (step === 6) {
                populateReviewStep();
            }
            
            updateStepIndicator();
            updateNavigationButtons();
        }

        function nextStep() {
            if (validateCurrentStep()) {
                if (currentStep < totalSteps) {
                    currentStep++;
                    showStep(currentStep);
                }
            }
        }

        function prevStep() {
            if (currentStep > 1) {
                currentStep--;
                showStep(currentStep);
            }
        }

        function updateStepIndicator() {
            steps.forEach((step, index) => {
                const stepNum = index + 1;
                step.classList.remove('active', 'completed');
                
                if (stepNum === currentStep) {
                    step.classList.add('active');
                } else if (stepNum < currentStep) {
                    step.classList.add('completed');
                }
            });
        }

        function updateNavigationButtons() {
            prevBtn.style.display = currentStep === 1 ? 'none' : 'block';
            
            if (currentStep === totalSteps) {
                nextBtn.style.display = 'none';
            } else {
                nextBtn.style.display = 'block';
                nextBtn.disabled = false;
                
                // Special handling for password step
                if (currentStep === 4) {
                    const password = document.getElementById('password').value;
                    const strength = checkPasswordStrength(password);
                    nextBtn.disabled = strength !== 'strong';
                }
            }
        }

        // Validation functions
        function validateCurrentStep() {
            let isValid = true;
            
            switch(currentStep) {
                case 1:
                    const employeeId = document.getElementById('employee_id');
                    if (!employeeId.value.trim()) {
                        showFieldError('employee_id', 'Employee ID is required');
                        isValid = false;
                    } else if (!/^[A-Za-z0-9]{4,20}$/.test(employeeId.value)) {
                        showFieldError('employee_id', 'Must be 4-20 alphanumeric characters');
                        isValid = false;
                    } else {
                        formData.employee_id = employeeId.value.trim();
                    }
                    break;
                    
                case 2:
                    const fullName = document.getElementById('full_name');
                    if (!fullName.value.trim()) {
                        showFieldError('full_name', 'Full Name is required');
                        isValid = false;
                    } else if (!/^[A-Za-z\s]{2,50}$/.test(fullName.value)) {
                        showFieldError('full_name', 'Must be 2-50 letters and spaces only');
                        isValid = false;
                    } else {
                        formData.full_name = fullName.value.trim();
                    }
                    break;
                    
                case 3:
                    const section = document.getElementById('section');
                    if (!section.value) {
                        showFieldError('section', 'Please select a section');
                        isValid = false;
                    } else {
                        formData.section = section.value;
                    }
                    break;
                    
                case 4:
                    const password = document.getElementById('password');
                    const strength = checkPasswordStrength(password.value);
                    if (strength !== 'strong') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Weak Password',
                            text: 'Please create a strong password with uppercase, lowercase, number, and special character'
                        });
                        isValid = false;
                    } else {
                        formData.password = password.value;
                    }
                    break;
                    
                case 5:
                    const confirmPassword = document.getElementById('confirm_password');
                    if (confirmPassword.value !== formData.password) {
                        showFieldError('confirm_password', 'Passwords do not match');
                        isValid = false;
                    }
                    break;
            }
            
            return isValid;
        }

        function showFieldError(fieldId, message) {
            const field = document.getElementById(fieldId);
            const feedback = document.getElementById(fieldId + '_feedback');
            
            field.classList.add('is-invalid');
            field.classList.remove('is-valid');
            feedback.textContent = message;
            feedback.classList.add('invalid');
            feedback.classList.remove('valid');
        }

        function showFieldSuccess(fieldId, message) {
            const field = document.getElementById(fieldId);
            const feedback = document.getElementById(fieldId + '_feedback');
            
            field.classList.add('is-valid');
            field.classList.remove('is-invalid');
            feedback.textContent = message;
            feedback.classList.add('valid');
            feedback.classList.remove('invalid');
        }

        function clearFieldValidation(fieldId) {
            const field = document.getElementById(fieldId);
            const feedback = document.getElementById(fieldId + '_feedback');
            
            field.classList.remove('is-valid', 'is-invalid');
            feedback.classList.remove('valid', 'invalid');
            feedback.textContent = '';
        }

        // Password strength checker
        function checkPasswordStrength(password) {
            let strength = 0;
            
            if (password.length >= 8) strength++;
            if (password.match(/[a-z]/)) strength++;
            if (password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;
            
            if (strength < 3) return 'weak';
            if (strength < 5) return 'medium';
            return 'strong';
        }

        function updatePasswordStrength(password) {
            const strength = checkPasswordStrength(password);
            const strengthFill = document.getElementById('strengthFill');
            const strengthText = document.getElementById('strengthText');
            
            strengthFill.className = 'strength-fill';
            strengthText.className = 'strength-text';
            
            if (password.length === 0) {
                strengthFill.style.width = '0';
                strengthText.textContent = 'Password strength';
            } else {
                strengthFill.classList.add(strength);
                strengthText.classList.add(strength);
                strengthText.textContent = strength.charAt(0).toUpperCase() + strength.slice(1);
                
                if (strength === 'strong') {
                    showFieldSuccess('password', 'Strong password');
                } else if (strength === 'medium') {
                    showFieldError('password', 'Add a special character for stronger password');
                } else {
                    showFieldError('password', 'Password is too weak');
                }
            }
            
            // Update next button state
            if (currentStep === 4) {
                nextBtn.disabled = strength !== 'strong';
            }
        }

        // AJAX validation functions
        async function checkEmployeeAvailability(value, field) {
            if (!value) return;
            
            try {
                const response = await fetch('registration_new.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=check_employee_id&employee_id=${encodeURIComponent(value)}&csrf_token=${csrfToken}`
                });
                
                const result = await response.json();
                
                if (result.available) {
                    showFieldSuccess(field, 'Employee ID is available');
                } else {
                    showFieldError(field, 'Employee ID already exists');
                }
            } catch (error) {
                console.error('Error checking employee ID:', error);
            }
        }

        async function checkNameAvailability(value, field) {
            if (!value) return;
            
            try {
                const response = await fetch('registration_new.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=check_full_name&full_name=${encodeURIComponent(value)}&csrf_token=${csrfToken}`
                });
                
                const result = await response.json();
                
                if (result.available) {
                    showFieldSuccess(field, 'Name is available');
                } else {
                    showFieldError(field, 'Full Name already exists');
                }
            } catch (error) {
                console.error('Error checking full name:', error);
            }
        }

        async function checkSectionAvailability(value, field) {
            if (!value) return;
            
            try {
                const response = await fetch('registration_new.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=check_section&section=${encodeURIComponent(value)}&csrf_token=${csrfToken}`
                });
                
                const result = await response.json();
                
                if (result.available) {
                    showFieldSuccess(field, 'Section is available');
                } else {
                    showFieldError(field, 'Section already taken');
                }
            } catch (error) {
                console.error('Error checking section:', error);
            }
        }

        // Review step population
        function populateReviewStep() {
            document.getElementById('review_employee_id').textContent = formData.employee_id;
            document.getElementById('review_full_name').textContent = formData.full_name;
            document.getElementById('review_section').textContent = formData.section;
        }

        // Event listeners
        prevBtn.addEventListener('click', prevStep);
        nextBtn.addEventListener('click', nextStep);

        // Field validation listeners
        document.getElementById('employee_id').addEventListener('blur', function() {
            const value = this.value.trim();
            if (value && /^[A-Za-z0-9]{4,20}$/.test(value)) {
                checkEmployeeAvailability(value, 'employee_id');
            } else {
                clearFieldValidation('employee_id');
            }
        });

        document.getElementById('full_name').addEventListener('blur', function() {
            const value = this.value.trim();
            if (value && /^[A-Za-z\s]{2,50}$/.test(value)) {
                checkNameAvailability(value, 'full_name');
            } else {
                clearFieldValidation('full_name');
            }
        });

        document.getElementById('section').addEventListener('change', function() {
            const value = this.value;
            if (value) {
                checkSectionAvailability(value, 'section');
            } else {
                clearFieldValidation('section');
            }
        });

        document.getElementById('password').addEventListener('input', function() {
            updatePasswordStrength(this.value);
        });

        document.getElementById('confirm_password').addEventListener('input', function() {
            if (this.value === formData.password) {
                showFieldSuccess('confirm_password', 'Passwords match');
            } else {
                showFieldError('confirm_password', 'Passwords do not match');
            }
        });

        // Password visibility toggles
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordField = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                passwordField.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        });

        document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
            const confirmPasswordField = document.getElementById('confirm_password');
            const icon = this.querySelector('i');
            
            if (confirmPasswordField.type === 'password') {
                confirmPasswordField.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                confirmPasswordField.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        });

        // Form submission
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitData = new FormData();
            submitData.append('action', 'register');
            submitData.append('csrf_token', csrfToken);
            submitData.append('employee_id', formData.employee_id);
            submitData.append('full_name', formData.full_name);
            submitData.append('section', formData.section);
            submitData.append('password', formData.password);
            submitData.append('confirm_password', formData.password);
            
            try {
                const response = await fetch('registration_new.php', {
                    method: 'POST',
                    body: submitData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Registration Successful!',
                        text: result.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = 'login.php';
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Registration Failed',
                        html: result.message
                    });
                }
            } catch (error) {
                console.error('Registration error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An unexpected error occurred. Please try again.'
                });
            }
        });
    </script>
</body>
</html>
