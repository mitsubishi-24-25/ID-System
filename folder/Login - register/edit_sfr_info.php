<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

require_once "database.php";

$success = '';
$error = '';
$lrn = $_GET['lrn'] ?? '';

// Get student data
$student = null;
if (!empty($lrn)) {
    $sql = "SELECT lrn, last_name, first_name, middle_initial, transportation_type, travel_minutes 
            FROM students 
            WHERE lrn = ? AND advisor_id = ? AND is_deleted = 0";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "si", $lrn, $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $student = mysqli_fetch_assoc($result);
    } else {
        $error = "Student not found or you don't have permission to edit this student.";
    }
    mysqli_stmt_close($stmt);
} else {
    $error = "No student LRN provided.";
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $student) {
    $transportation = $_POST['transportation'] ?? '';
    $minutes = intval($_POST['minutes'] ?? 0);
    
    // Basic validation
    if (empty($transportation) || $minutes <= 0) {
        $error = "Please fill in all required fields with valid values.";
    } else {
        // Update the student's transportation info
        $updateSql = "UPDATE students SET 
                     transportation_type = ?, 
                     travel_minutes = ? 
                     WHERE lrn = ? AND advisor_id = ?";
        $updateStmt = mysqli_prepare($conn, $updateSql);
        mysqli_stmt_bind_param($updateStmt, "sisi", 
            $transportation, 
            $minutes, 
            $lrn, 
            $_SESSION['user_id']
        );
        
        if (mysqli_stmt_execute($updateStmt)) {
            if (mysqli_affected_rows($conn) > 0) {
                $success = "SFR information has been updated successfully!";
            } else {
                $error = "No changes were made.";
            }
        } else {
            $error = "Error updating information: " . mysqli_error($conn);
        }
        
        mysqli_stmt_close($updateStmt);
        
        // Refresh student data after update
        $sql = "SELECT lrn, last_name, first_name, middle_initial, transportation_type, travel_minutes 
                FROM students 
                WHERE lrn = ? AND advisor_id = ? AND is_deleted = 0";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "si", $lrn, $_SESSION['user_id']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($result && mysqli_num_rows($result) > 0) {
            $student = mysqli_fetch_assoc($result);
        }
        mysqli_stmt_close($stmt);
    }
}

// Include header
include 'includes/header.php';
?>

<div class="container py-5">
    <div class="card mx-auto" style="max-width: 800px;">
        <div class="card-header d-flex align-items-center" style="background-color: #1e7e34; color: white; border-bottom: none; border-radius: 0.375rem 0.375rem 0 0;">
            <i class="bi bi-pencil-square me-3" style="font-size: 1.5rem; color: white;"></i>
            <h2 class="mb-0" style="color: white; font-weight: 600;">Edit SFR Information</h2>
        </div>
        <div class="card-body p-4">
            <?php if ($student): ?>
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <i class="bi bi-person-fill me-2"></i>
                    Editing: <strong><?php echo htmlspecialchars($student['last_name'] . ', ' . $student['first_name'] . ' ' . $student['middle_initial'] . '.'); ?></strong> (LRN: <?php echo htmlspecialchars($student['lrn']); ?>)
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if ($student): ?>
                <form class="needs-validation" novalidate action="edit_sfr_info.php?lrn=<?php echo urlencode($lrn); ?>" method="POST">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="transportation" class="form-label">Mode of Transportation <span class="text-danger">*</span></label>
                                <select class="form-select" id="transportation" name="transportation" required>
                                    <option value="" selected disabled>Select transportation</option>
                                    <option value="Walking" <?php echo (isset($student['transportation_type']) && $student['transportation_type'] === 'Walking') ? 'selected' : ''; ?>>Walking</option>
                                    <option value="Bus" <?php echo (isset($student['transportation_type']) && $student['transportation_type'] === 'Bus') ? 'selected' : ''; ?>>Bus</option>
                                    <option value="Car" <?php echo (isset($student['transportation_type']) && $student['transportation_type'] === 'Car') ? 'selected' : ''; ?>>Car</option>
                                    <option value="Bicycle" <?php echo (isset($student['transportation_type']) && $student['transportation_type'] === 'Bicycle') ? 'selected' : ''; ?>>Bicycle</option>
                                    <option value="Other" <?php echo (isset($student['transportation_type']) && $student['transportation_type'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                                </select>
                                <div class="invalid-feedback">
                                    Please select a mode of transportation.
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="minutes" class="form-label">Travel Time (minutes) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="minutes" name="minutes" 
                                       min="1" max="240" 
                                       value="<?php echo isset($student['travel_minutes']) ? htmlspecialchars($student['travel_minutes']) : ''; ?>"
                                       placeholder="Enter minutes (1-240)" 
                                       required>
                                <div class="form-text">
                                    Enter the approximate number of minutes for the transportation.
                                </div>
                                <div class="invalid-feedback">
                                    Please enter a valid number of minutes (1-240).
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between mt-4 pt-3 border-top">
                        <a href="sfr_dashboard.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
                        </a>
                        <button type="submit" class="btn" style="background-color: #1e7e34; color: white; border: 1px solid #1c7430;">
                            <i class="bi bi-save me-2"></i>Update Information
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Form validation
(function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
            }
            form.classList.add('was-validated')
        }, false)
    })
})()

// Close alerts after 5 seconds
setTimeout(() => {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        const bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
    });
}, 5000);
</script>

<?php include 'includes/footer.php'; ?>
