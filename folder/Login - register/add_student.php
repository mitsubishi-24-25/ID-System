<?php
session_start();
if (!isset($_SESSION["user_id"])) {
   header("Location: login.php");
   exit();
}

// Display success message if set
if (isset($_GET['success_message'])) {
    echo "<div class='alert alert-success alert-dismissible fade show' role='alert'>";
    echo htmlspecialchars(urldecode($_GET['success_message']));
    echo "<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>";
    echo "</div>";
}

// Display error message if set
if (isset($_GET['error_message'])) {
    echo "<div class='alert alert-danger alert-dismissible fade show' role='alert'>";
    echo htmlspecialchars(urldecode($_GET['error_message']));
    echo "<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>";
    echo "</div>";
}

require_once "database.php";

$userId = $_SESSION['user_id'];
$section = "";
$page_title = "";

$sigSql = "SELECT section FROM users WHERE id = ?";
$sigStmt = mysqli_prepare($conn, $sigSql);
if ($sigStmt) {
    mysqli_stmt_bind_param($sigStmt, "i", $userId);
    mysqli_stmt_execute($sigStmt);
    $sigResult = mysqli_stmt_get_result($sigStmt);
    if ($sigResult && mysqli_num_rows($sigResult) > 0) {
        $rowSig = mysqli_fetch_assoc($sigResult);
        $section = $rowSig['section'];
    }
    mysqli_stmt_close($sigStmt);
}

// Include header
include 'includes/header.php';

$errors = [];
$success_message = "";

if (isset($_POST['submit'])) {
    $lrn = trim($_POST['lrn']);
    $lastName = trim($_POST['last_name']);
    $middleInitial = trim($_POST['middle_initial']);
    $firstName = trim($_POST['first_name']);
    $birthdate = trim($_POST['birthdate']);
    $parentGuardian = trim($_POST['parent_guardian']);
    $permanentAddress = trim($_POST['permanent_address']);
    $contactNumber = trim($_POST['contact_number']);

    // Validate required fields
    if (empty($lrn)) {
        $errors[] = "LRN is required.";
    }
    if (empty($lastName)) {
        $errors[] = "Last Name is required.";
    }
    if (empty($firstName)) {
        $errors[] = "First Name is required.";
    }
    if (empty($birthdate)) {
        $errors[] = "Birthdate is required.";
    }
    if (empty($parentGuardian)) {
        $errors[] = "Name of Parent/Guardian is required.";
    }
    if (empty($permanentAddress)) {
        $errors[] = "Permanent Address is required.";
    }
    if (empty($contactNumber)) {
        $errors[] = "Contact Number is required.";
    }

    if (count($errors) === 0) {
        // Check if LRN already exists
        $checkSql = "SELECT id FROM students WHERE lrn = ? AND user_id = ?";
        $checkStmt = mysqli_prepare($conn, $checkSql);
        if ($checkStmt) {
            mysqli_stmt_bind_param($checkStmt, "si", $lrn, $userId);
            mysqli_stmt_execute($checkStmt);
            $result = mysqli_stmt_get_result($checkStmt);
            
            if (mysqli_num_rows($result) > 0) {
                $errors[] = "A student with LRN $lrn already exists in your records.";
            }
            mysqli_stmt_close($checkStmt);
        }

        if (count($errors) === 0) {
            // Insert with user_id from session
            $sql = "INSERT INTO students (lrn, last_name, first_name, middle_initial, birthdate, parent_guardian, permanent_address, contact_number, photo, advisor_id) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            if ($stmt) {
                $advisor_id = $_SESSION['user_id'];
                mysqli_stmt_bind_param($stmt, "sssssssssi", $lrn, $lastName, $firstName, $middleInitial, $birthdate, $parentGuardian, $permanentAddress, $contactNumber, $photo_name, $advisor_id);
                if (mysqli_stmt_execute($stmt)) {
                    $success_message = "Student added successfully!";
                    // Clear form
                    $lrn = $lastName = $middleInitial = $firstName = $birthdate = $parentGuardian = $permanentAddress = $contactNumber = "";
                } else {
                    $errors[] = "Error adding student: " . mysqli_error($conn);
                }
                mysqli_stmt_close($stmt);
            } else {
                $errors[] = "Database error. Please try again.";
            }
        }
    }
}
?>

<style>
    .form-container {
        max-width: 800px;
        margin: 2rem auto;
        background: white;
        border-radius: 10px;
        box-shadow: 0 0 20px rgba(0,0,0,0.1);
        overflow: hidden;
    }
    .form-header {
        background-color: #198754;
        color: white;
        padding: 1.5rem 2rem;
    }
    .form-header h2 {
        margin: 0;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .form-header h2 i {
        font-size: 1.5rem;
    }
    .form-body {
        padding: 2rem;
    }
    .form-section {
        margin-bottom: 2rem;
    }
    .form-section h5 {
        color: #198754;
        border-bottom: 2px solid #e9ecef;
        padding-bottom: 0.5rem;
        margin-bottom: 1.5rem;
    }
    .form-label {
        font-weight: 500;
        color: #495057;
        margin-bottom: 0.5rem;
    }
    .form-control, .form-select {
        border-radius: 6px;
        padding: 0.6rem 0.75rem;
        border: 1px solid #ced4da;
        transition: all 0.3s;
    }
    .form-control:focus, .form-select:focus {
        border-color: #198754;
        box-shadow: 0 0 0 0.25rem rgba(25, 135, 84, 0.25);
    }
    .btn-submit {
        background-color: #198754;
        border: none;
        padding: 0.6rem 2rem;
        font-weight: 500;
        transition: all 0.3s;
    }
    .btn-submit:hover {
        background-color: #157347;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .btn-cancel {
        background-color: #6c757d;
        border: none;
        padding: 0.6rem 2rem;
        font-weight: 500;
        transition: all 0.3s;
    }
    .btn-cancel:hover {
        background-color: #5a6268;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .input-group-text {
        background-color: #f8f9fa;
        border: 1px solid #ced4da;
        color: #198754;
    }
    .form-text {
        font-size: 0.85rem;
        color: #6c757d;
    }
</style>

<div class="container">
    <div class="form-container">
        <div class="form-header">
            <h2><i class="bi bi-person-plus-fill"></i> Add New Student</h2>
            <p class="mb-0 mt-2">Please fill in all required fields</p>
        </div>
        
        <div class="form-body">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <h5><i class="bi bi-exclamation-triangle-fill me-2"></i>Please fix the following errors:</h5>
                    <ul class="mb-0 mt-2">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="alert alert-success d-flex align-items-center">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <div><?php echo htmlspecialchars($success_message); ?></div>
                </div>
            <?php endif; ?>

            <form action="add_student.php" method="post" class="needs-validation" novalidate>
                <div class="form-section">
                    <h5><i class="bi bi-person-badge me-2"></i>Basic Information</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="lrn" class="form-label">Learner Reference Number (LRN)</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-123"></i></span>
                                <input type="text" class="form-control" id="lrn" name="lrn" required 
                                       placeholder="Enter 12-digit LRN" pattern="\d{12}"
                                       value="<?php echo htmlspecialchars($lrn ?? ''); ?>">
                            </div>
                            <div class="form-text">12-digit number found on the student's report card</div>
                        </div>
                        <div class="col-md-6">
                            <label for="birthdate" class="form-label">Birthdate</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-calendar3"></i></span>
                                <input type="date" class="form-control" id="birthdate" name="birthdate" required 
                                       value="<?php echo htmlspecialchars($birthdate ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h5><i class="bi bi-person-lines-fill me-2"></i>Name</h5>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text" class="form-control" id="last_name" name="last_name" required 
                                       placeholder="Dela Cruz" value="<?php echo htmlspecialchars($lastName ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person-fill"></i></span>
                                <input type="text" class="form-control" id="first_name" name="first_name" required 
                                       placeholder="Juan" value="<?php echo htmlspecialchars($firstName ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label for="middle_initial" class="form-label">Middle Initial</label>
                            <div class="input-group">
                                <span class="input-group-text">M.I.</span>
                                <input type="text" class="form-control text-uppercase" id="middle_initial" name="middle_initial" 
                                       maxlength="1" placeholder="S" 
                                       value="<?php echo htmlspecialchars($middleInitial ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h5><i class="bi bi-house-door-fill me-2"></i>Contact Information</h5>
                    <div class="mb-3">
                        <label for="permanent_address" class="form-label">Permanent Address <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
                            <textarea class="form-control" id="permanent_address" name="permanent_address" rows="2" 
                                     placeholder="House No., Street, Barangay, City/Municipality, Province" required><?php 
                                echo htmlspecialchars($permanentAddress ?? ''); 
                            ?></textarea>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="parent_guardian" class="form-label">Parent/Guardian Name <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-people-fill"></i></span>
                                <input type="text" class="form-control" id="parent_guardian" name="parent_guardian" required 
                                       placeholder="Full name of parent or guardian" 
                                       value="<?php echo htmlspecialchars($parentGuardian ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="contact_number" class="form-label">Contact Number <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-telephone-fill"></i></span>
                                <input type="tel" class="form-control" id="contact_number" name="contact_number" required 
                                       pattern="[0-9]{11}" placeholder="09123456789" 
                                       value="<?php echo htmlspecialchars($contactNumber ?? ''); ?>">
                            </div>
                            <div class="form-text">Format: 09XXXXXXXXX (11 digits)</div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="index.php" class="btn btn-cancel text-white">
                        <i class="bi bi-arrow-left-circle me-1"></i> Back to Dashboard
                    </a>
                    <button type="submit" name="submit" class="btn btn-submit text-white">
                        <i class="bi bi-save me-1"></i> Save Student
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Form validation
(function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms)
        .forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }
                form.classList.add('was-validated')
            }, false)
        })
})()

// Auto-format LRN input
document.getElementById('lrn')?.addEventListener('input', function(e) {
    this.value = this.value.replace(/\D/g, '').slice(0, 12);
});

// Auto-format contact number
document.getElementById('contact_number')?.addEventListener('input', function(e) {
    this.value = this.value.replace(/\D/g, '').slice(0, 11);
});

// Auto-capitalize names
document.getElementById('last_name')?.addEventListener('input', function(e) {
    this.value = this.value.replace(/\b\w/g, l => l.toUpperCase());
});
document.getElementById('first_name')?.addEventListener('input', function(e) {
    this.value = this.value.replace(/\b\w/g, l => l.toUpperCase());
});
document.getElementById('parent_guardian')?.addEventListener('input', function(e) {
    this.value = this.value.replace(/\b\w/g, l => l.toUpperCase());
});

// Auto-uppercase middle initial
document.getElementById('middle_initial')?.addEventListener('input', function(e) {
    this.value = this.value.toUpperCase();
});
</script>

<?php include 'includes/footer.php'; ?>