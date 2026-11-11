<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

require_once "database.php";

$success = '';
$error = '';

// Get list of students for the current advisor
$sql = "SELECT lrn, CONCAT(last_name, ', ', first_name) as student_name 
        FROM students 
        WHERE advisor_id = ? AND is_deleted = 0 
        ORDER BY last_name, first_name";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$students = mysqli_stmt_get_result($stmt);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_all'])) {
    $selectedStudents = $_POST['students'] ?? [];
    $transportation = $_POST['transportation'] ?? '';
    $minutes = intval($_POST['minutes'] ?? 0);
    
    // Debug: Log received data
    error_log("Received students: " . print_r($selectedStudents, true));
    error_log("Received transportation: " . $transportation);
    error_log("Received minutes: " . $minutes);
    
    // Basic validation
    if (empty($selectedStudents) || empty($transportation) || $minutes <= 0) {
        $error = "Please select at least one student and fill in all required fields with valid values.";
    } else {
        $successCount = 0;
        $errorCount = 0;
        
        // Update each selected student
        foreach ($selectedStudents as $lrn) {
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
                    $successCount++;
                } else {
                    $errorCount++;
                }
            } else {
                $errorCount++;
            }
            mysqli_stmt_close($updateStmt);
        }
        
        if ($errorCount > 0) {
            $error = "Error updating some students. Please try again.";
        } elseif ($successCount > 0) {
            $success = "Successfully updated transportation information for {$successCount} student(s)!";
        } else {
            $error = "No students were updated. The students may not exist or you don't have permission to update them.";
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add SFR Information | My Smart ID</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f5f5f0;
            font-family: 'Roboto', sans-serif;
        }
        
        .card-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 2rem;
            margin-bottom: 1.5rem;
        }
        
        .welcome-message {
            background: linear-gradient(135deg, #1e7e34, #157347);
            color: white;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .student-list {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1rem;
            background-color: #f8f9fa;
            margin-bottom: 2rem;
        }
        
        .student-item {
            padding: 0.5rem 0;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .student-item:last-child {
            border-bottom: none;
        }
        
        .student-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .student-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #1e7e34;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .btn-primary {
            background-color: #1e7e34;
            border-color: #1e7e34;
        }
        
        .btn-primary:hover {
            background-color: #157347;
            border-color: #146c43;
        }
    </style>
</head>
<body>
<?php include 'includes/header.php'; ?>
<main class="main-content" id="mainContent">
    <div class="container">
        <div class="welcome-message">
            <h2>Add SFR Information</h2>
            <p class="mb-0">Update transportation details for individual students.</p>
        </div>

        <form method="POST" action="add_sfr_info.php" id="bulkUpdateForm" class="row g-4">
            <input type="hidden" name="update_all" value="1">
            <div class="col-md-8 col-lg-8">
                <div class="card-container">
                    <h4 class="mb-3">Your Students (<?php echo isset($students) && is_object($students) ? mysqli_num_rows($students) : 0; ?> total)</h4>
                    
                    <?php if (isset($students) && is_object($students) && mysqli_num_rows($students) > 0): ?>
                        <div class="student-list">
                            <?php 
                            mysqli_data_seek($students, 0);
                            while ($student = mysqli_fetch_assoc($students)): 
                            ?>
                                <div class="student-item">
                                    <div class="student-info">
                                        <div class="student-avatar">
                                            <?php echo strtoupper(substr($student['student_name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <strong><?php echo htmlspecialchars($student['student_name']); ?></strong>
                                            <br>
                                            <small class="text-muted">
                                                LRN: <?php echo htmlspecialchars($student['lrn']); ?>
                                                <?php if (!empty($student['grade'])): ?>
                                                    | Grade: <?php echo htmlspecialchars($student['grade']); ?>
                                                <?php endif; ?>
                                                <?php if (!empty($student['section'])): ?>
                                                    | Section: <?php echo htmlspecialchars($student['section']); ?>
                                                <?php endif; ?>
                                                <?php if (!empty($student['transportation_type'])): ?>
                                                    | Transportation: <?php echo htmlspecialchars($student['transportation_type']); ?>
                                                <?php endif; ?>
                                                <?php if (!empty($student['travel_minutes'])): ?>
                                                    | Travel Time: <?php echo htmlspecialchars($student['travel_minutes']); ?> mins
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="student_<?php echo htmlspecialchars($student['lrn']); ?>" name="students[]" value="<?php echo htmlspecialchars($student['lrn']); ?>">
                                        <label class="form-check-label" for="student_<?php echo htmlspecialchars($student['lrn']); ?>">
                                            <i class="bi bi-person-check me-1"></i>
                                            Select for update
                                        </label>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="bi bi-people-fill d-block mb-3" style="font-size: 3rem; color: #6c757d;"></i>
                            <p class="text-muted">No students found assigned to you.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-md-4 col-lg-4">
                <div class="card-container">
                    <h4 class="mb-3">Update Transportation</h4>
                    
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            <?php echo $success; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label for="transportation" class="form-label">Mode of Transportation <span class="text-danger">*</span></label>
                        <select class="form-select" id="transportation" name="transportation" required>
                            <option value="" selected disabled>Select transportation</option>
                            <option value="Walking" <?php echo (isset($_POST['transportation']) && $_POST['transportation'] === 'Walking') ? 'selected' : ''; ?>>Walking</option>
                            <option value="Bus" <?php echo (isset($_POST['transportation']) && $_POST['transportation'] === 'Bus') ? 'selected' : ''; ?>>Bus</option>
                            <option value="Car" <?php echo (isset($_POST['transportation']) && $_POST['transportation'] === 'Car') ? 'selected' : ''; ?>>Car</option>
                            <option value="Bicycle" <?php echo (isset($_POST['transportation']) && $_POST['transportation'] === 'Bicycle') ? 'selected' : ''; ?>>Bicycle</option>
                            <option value="Other" <?php echo (isset($_POST['transportation']) && $_POST['transportation'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                        <div class="invalid-feedback">
                            Please select a mode of transportation.
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="minutes" class="form-label">Travel Time (minutes) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="minutes" name="minutes" 
                               min="1" max="240" 
                               value="<?php echo isset($_POST['minutes']) ? htmlspecialchars($_POST['minutes']) : ''; ?>"
                               placeholder="Enter minutes (1-240)" 
                               required>
                        <div class="form-text text-muted">
                            Enter approximate number of minutes for transportation.
                        </div>
                        <div class="invalid-feedback">
                            Please enter a valid number of minutes (1-240).
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" name="update_all" class="btn btn-primary btn-lg">
                            <i class="bi bi-save me-2"></i>Update Selected Students
                        </button>
                        <a href="sfr_dashboard.php" class="btn btn-outline-secondary btn-lg">
                            <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Custom form validation for checkbox-based student selection
    const bulkUpdateForm = document.getElementById('bulkUpdateForm');
    let isConfirmedSubmit = false;
    if (bulkUpdateForm) {
        bulkUpdateForm.addEventListener('submit', function(e) {
            if (isConfirmedSubmit) {
                return;
            }
            const selectedStudents = document.querySelectorAll('input[name="students[]"]:checked');
            const transportSelect = document.getElementById('transportation');
            const minutesInput = document.getElementById('minutes');
            
            // Validate that at least one student is selected
            if (selectedStudents.length === 0) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'No student selected',
                    text: 'Please select at least one student to update.',
                    confirmButtonColor: '#1e7e34'
                });
                return false;
            }
            
            // Validate that transportation and minutes are filled
            if (!transportSelect.value || !minutesInput.value || minutesInput.value <= 0) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Missing required fields',
                    text: 'Please fill in all required fields with valid values.',
                    confirmButtonColor: '#1e7e34'
                });
                return false;
            }
            
            // Confirm bulk update
            if (selectedStudents.length > 0) {
                e.preventDefault();
                Swal.fire({
                    icon: 'question',
                    title: 'Confirm update',
                    text: `Update transportation information for ${selectedStudents.length} selected student(s)?`,
                    showCancelButton: true,
                    confirmButtonText: 'Yes, update',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#1e7e34',
                    cancelButtonColor: '#6c757d'
                }).then((result) => {
                    if (result.isConfirmed) {
                        isConfirmedSubmit = true;
                        bulkUpdateForm.submit();
                    }
                });
            }
        });
    }
    
    // Auto-hide success/error messages after 5 seconds
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            alert.style.opacity = '0';
            setTimeout(function() {
                alert.style.display = 'none';
            }, 300);
        });
    }, 5000);
});
</script>

<?php include 'includes/footer.php'; ?>
</body>
</html>
