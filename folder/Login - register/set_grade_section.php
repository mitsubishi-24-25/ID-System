<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

require_once "database.php";

$success = '';
$error = '';

// Get all students for the current advisor
$sql = "SELECT lrn, CONCAT(last_name, ', ', first_name) as student_name, grade, section 
        FROM students 
        WHERE advisor_id = ? AND is_deleted = 0 
        ORDER BY last_name, first_name";
$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    $students = mysqli_stmt_get_result($stmt);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $grade = $_POST['grade'] ?? '';
    $section = $_POST['section'] ?? '';
    
    // Basic validation
    if (empty($grade) || empty($section)) {
        $error = "Please select both grade and section.";
    } else {
        // Update all students for this advisor
        $updateSql = "UPDATE students SET 
                     grade = ?, 
                     section = ? 
                     WHERE advisor_id = ? AND is_deleted = 0";
        $updateStmt = mysqli_prepare($conn, $updateSql);
        mysqli_stmt_bind_param($updateStmt, "ssi", 
            $grade, 
            $section, 
            $_SESSION['user_id']
        );
        
        if (mysqli_stmt_execute($updateStmt)) {
            $affectedRows = mysqli_affected_rows($conn);
            if ($affectedRows > 0) {
                $success = "Successfully updated grade and section for {$affectedRows} student(s)!";
            } else {
                $error = "No students were updated. You may not have any students assigned to you.";
            }
        } else {
            $error = "Error updating students: " . mysqli_error($conn);
        }
        mysqli_stmt_close($updateStmt);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Grade & Section | My Smart ID</title>
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
            background: linear-gradient(135deg, #198754, #157347);
            color: white;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .student-list {
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1rem;
            background-color: #f8f9fa;
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
            background-color: #198754;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .btn-primary {
            background-color: #198754;
            border-color: #198754;
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
            <h2>Set Grade & Section for All Students</h2>
            <p class="mb-0">Update grade and section for all your assigned students at once.</p>
        </div>

        <div class="row">
            <div class="col-lg-4">
                <div class="card-container">
                    <h4 class="mb-3">Select Grade & Section</h4>
                    
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

                    <form method="POST" action="set_grade_section.php" id="bulkGradeSectionForm">
                        <div class="mb-3">
                            <label for="grade" class="form-label">Grade Level <span class="text-danger">*</span></label>
                            <select class="form-select" id="grade" name="grade" required>
                                <option value="" selected disabled>Select grade</option>
                                <option value="11" <?php echo (isset($_POST['grade']) && $_POST['grade'] === '11') ? 'selected' : ''; ?>>11</option>
                                <option value="12" <?php echo (isset($_POST['grade']) && $_POST['grade'] === '12') ? 'selected' : ''; ?>>12</option>
                            </select>
                            <div class="invalid-feedback">
                                Please select a grade level.
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="section" class="form-label">Section <span class="text-danger">*</span></label>
                            <select class="form-select" id="section" name="section" required>
                                <option value="" selected disabled>Select section</option>
                                <option value="ABM A" <?php echo (isset($_POST['section']) && $_POST['section'] === 'ABM A') ? 'selected' : ''; ?>>ABM A</option>
                                <option value="ABM B" <?php echo (isset($_POST['section']) && $_POST['section'] === 'ABM B') ? 'selected' : ''; ?>>ABM B</option>
                                <option value="HE A" <?php echo (isset($_POST['section']) && $_POST['section'] === 'HE A') ? 'selected' : ''; ?>>HE A</option>
                                <option value="HE B" <?php echo (isset($_POST['section']) && $_POST['section'] === 'HE B') ? 'selected' : ''; ?>>HE B</option>
                                <option value="GAS A" <?php echo (isset($_POST['section']) && $_POST['section'] === 'GAS A') ? 'selected' : ''; ?>>GAS A</option>
                                <option value="GAS B" <?php echo (isset($_POST['section']) && $_POST['section'] === 'GAS B') ? 'selected' : ''; ?>>GAS B</option>
                                <option value="STEM A" <?php echo (isset($_POST['section']) && $_POST['section'] === 'STEM A') ? 'selected' : ''; ?>>STEM A</option>
                                <option value="STEM B" <?php echo (isset($_POST['section']) && $_POST['section'] === 'STEM B') ? 'selected' : ''; ?>>STEM B</option>
                                <option value="ICT A" <?php echo (isset($_POST['section']) && $_POST['section'] === 'ICT A') ? 'selected' : ''; ?>>ICT A</option>
                                <option value="ICT B" <?php echo (isset($_POST['section']) && $_POST['section'] === 'ICT B') ? 'selected' : ''; ?>>ICT B</option>
                            </select>
                            <div class="invalid-feedback">
                                Please select a section.
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" name="update_all" class="btn btn-primary btn-lg">
                                <i class="bi bi-gear-fill me-2"></i>Update All Students
                            </button>
                            <a href="sfr_dashboard.php" class="btn btn-outline-secondary btn-lg">
                                <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card-container">
                    <h4 class="mb-3">Your Students (<?php echo (isset($students) && is_object($students)) ? mysqli_num_rows($students) : 0; ?> total)</h4>
                    
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
                                            </small>
                                        </div>
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
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const bulkGradeSectionForm = document.getElementById('bulkGradeSectionForm');
    let isConfirmedSubmit = false;

    if (bulkGradeSectionForm) {
        bulkGradeSectionForm.addEventListener('submit', function(e) {
            if (isConfirmedSubmit) {
                return;
            }

            const gradeSelect = document.getElementById('grade');
            const sectionSelect = document.getElementById('section');

            if (!gradeSelect?.value || !sectionSelect?.value) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Missing required fields',
                    text: 'Please select both grade and section.',
                    confirmButtonColor: '#198754'
                });
                return;
            }

            e.preventDefault();
            Swal.fire({
                icon: 'question',
                title: 'Confirm update',
                text: 'Update grade and section for all your students?',
                showCancelButton: true,
                confirmButtonText: 'Yes, update',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#198754',
                cancelButtonColor: '#6c757d'
            }).then((result) => {
                if (result.isConfirmed) {
                    isConfirmedSubmit = true;
                    bulkGradeSectionForm.submit();
                }
            });
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
