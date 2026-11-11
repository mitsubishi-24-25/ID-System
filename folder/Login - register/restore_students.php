<?php
session_start();
require_once "database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$advisor_id = $userId; // Set advisor_id to be the same as user_id

// Get messages from session or URL parameters
$success_message = $_SESSION['success_message'] ?? $_GET['success_message'] ?? "";
$info_message = $_GET['info_message'] ?? "";
$error_message = $_SESSION['error_message'] ?? $_GET['error_message'] ?? "";

// Clear the messages from session
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);

// Handle restore action
if ($_SERVER["REQUEST_METHOD"] === "POST" && !empty($_POST['restore_id'])) {
    $restoreId = (int)$_POST['restore_id'];
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Get deleted student data
        $selectSql = "SELECT * FROM deleted_students WHERE id = ? AND advisor_id = ?";
        $selectStmt = mysqli_prepare($conn, $selectSql);
        mysqli_stmt_bind_param($selectStmt, "ii", $restoreId, $advisor_id);
        mysqli_stmt_execute($selectStmt);
        $result = mysqli_stmt_get_result($selectStmt);
        
        if (mysqli_num_rows($result) > 0) {
            $student = mysqli_fetch_assoc($result);
            
            // Check if student with this LRN already exists and is not deleted
            $checkSql = "SELECT id FROM students WHERE lrn = ? AND advisor_id = ? AND is_deleted = 0";
            $checkStmt = mysqli_prepare($conn, $checkSql);
            mysqli_stmt_bind_param($checkStmt, "si", $student['lrn'], $advisor_id);
            mysqli_stmt_execute($checkStmt);
            $checkResult = mysqli_stmt_get_result($checkStmt);
            
            if (mysqli_num_rows($checkResult) > 0) {
                // Update existing student record
                $updateSql = "UPDATE students SET 
                    last_name = ?,
                    middle_initial = ?,
                    first_name = ?,
                    birthdate = ?,
                    parent_guardian = ?,
                    permanent_address = ?,
                    contact_number = ?,
                    photo = ?,
                    is_deleted = 0,
                    advisor_id = ?
                    WHERE lrn = ? AND advisor_id = ?";
                
                $updateStmt = mysqli_prepare($conn, $updateSql);
                mysqli_stmt_bind_param(
                    $updateStmt,
                    "sssssssssii",
                    $student['last_name'],
                    $student['middle_initial'],
                    $student['first_name'],
                    $student['birthdate'],
                    $student['parent_guardian'],
                    $student['permanent_address'],
                    $student['contact_number'],
                    $student['photo'],
                    $advisor_id,
                    $student['lrn'],
                    $advisor_id
                );
                
                if (!mysqli_stmt_execute($updateStmt)) {
                    throw new Exception("Failed to update student: " . mysqli_error($conn));
                }
            } else {
                // Insert as new student
                $insertSql = "INSERT INTO students (
                    lrn, last_name, middle_initial, first_name, 
                    birthdate, parent_guardian, permanent_address, 
                    contact_number, photo, user_id, advisor_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $insertStmt = mysqli_prepare($conn, $insertSql);
                mysqli_stmt_bind_param(
                    $insertStmt,
                    "sssssssssii",
                    $student['lrn'],
                    $student['last_name'],
                    $student['middle_initial'],
                    $student['first_name'],
                    $student['birthdate'],
                    $student['parent_guardian'],
                    $student['permanent_address'],
                    $student['contact_number'],
                    $student['photo'],
                    $userId,
                    $advisor_id
                );
                
                if (!mysqli_stmt_execute($insertStmt)) {
                    throw new Exception("Failed to restore student: " . mysqli_error($conn));
                }
            }
            
            // Delete from deleted_students
            $deleteSql = "DELETE FROM deleted_students WHERE id = ? AND advisor_id = ?";
            $deleteStmt = mysqli_prepare($conn, $deleteSql);
            mysqli_stmt_bind_param($deleteStmt, "ii", $restoreId, $advisor_id);
            
            if (!mysqli_stmt_execute($deleteStmt)) {
                throw new Exception("Failed to clean up deleted records: " . mysqli_error($conn));
            }
            
            // Commit transaction
            mysqli_commit($conn);
            
            $_SESSION['success_message'] = "Student successfully restored!";
        } else {
            throw new Exception("Student not found or you don't have permission to restore this student.");
        }
    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        $_SESSION['error_message'] = $e->getMessage();
    }
}

// Get deleted students for the current advisor
$sql = "SELECT * FROM deleted_students WHERE advisor_id = ? ORDER BY deleted_at DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $advisor_id);
mysqli_stmt_execute($stmt);
$deletedStudents = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restore Deleted Students</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
    :root {
        --primary-green: #198754;
        --light-green: #e8f5e9;
        --dark-green: #0d6e3f;
        --card-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
    }
    
    body {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        min-height: 100vh;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    
    .card {
        border: none;
        border-radius: 12px;
        box-shadow: var(--card-shadow);
        overflow: hidden;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        background: white;
    }
    
    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
    }
    
    .card-header {
        background: linear-gradient(135deg, var(--primary-green) 0%, var(--dark-green) 100%);
        color: white;
        border: none;
        padding: 1.25rem 2rem;
        position: relative;
        overflow: hidden;
    }
    
    .card-header::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: rgba(255, 255, 255, 0.2);
    }
    
    .table {
        margin-bottom: 0;
        border-collapse: separate;
        border-spacing: 0;
    }
    
    .table thead th {
        background: linear-gradient(to bottom, #f8f9fa, #e9ecef);
        color: #495057;
        font-weight: 600;
        border: none;
        padding: 1rem 1.5rem;
        text-transform: uppercase;
        font-size: 0.8rem;
        letter-spacing: 0.5px;
        border-bottom: 2px solid #dee2e6;
    }
    
    .table tbody tr {
        transition: all 0.2s ease;
    }
    
    .table tbody tr:hover {
        background-color: rgba(25, 135, 84, 0.05);
        transform: translateX(4px);
    }
    
    .table tbody td {
        padding: 1.25rem 1.5rem;
        vertical-align: middle;
        border-top: 1px solid #edf2f7;
    }
    
    .table tbody tr:nth-child(odd) {
        background-color: rgba(0, 0, 0, 0.01);
    }
    
    .btn-restore {
        background: var(--primary-green) !important;
        color: white !important;
        border: 1px solid var(--primary-green) !important;
        border-radius: 6px;
        padding: 0.5rem 1.25rem;
        font-weight: 500;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
    }

    .btn-restore:hover {
        background: var(--dark-green) !important;
        color: white !important;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1) !important;
        border-color: var(--dark-green) !important;
    }

    .btn-restore i {
        margin-right: 6px;
        color: white !important;
    }

    .back-btn {
        background: white;
        color: var(--primary-green);
        border: 1px solid rgba(255, 255, 255, 0.3);
        border-radius: 6px;
        padding: 0.5rem 1.25rem;
        font-weight: 500;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
    }
    
    .back-btn:hover {
        background: white;
        color: var(--dark-green);
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    .empty-state {
        padding: 3rem 2rem;
        text-align: center;
        background: white;
        border-radius: 8px;
        border: 1px dashed #dee2e6;
        margin: 1.5rem 0;
    }
    
    .empty-state i {
        font-size: 3.5rem;
        color: #e9ecef;
        margin-bottom: 1.5rem;
        display: block;
    }
    
    .empty-state h4 {
        color: #495057;
        margin-bottom: 0.75rem;
        font-weight: 600;
    }
    
    .empty-state p {
        color: #6c757d;
        margin-bottom: 1.5rem;
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        .card {
            border-radius: 0;
            margin: 0 -1rem;
            box-shadow: none;
        }
        
        .table-responsive {
            border-radius: 0;
        }
        
        .table thead th {
            padding: 0.75rem 1rem;
        }
        
        .table tbody td {
            padding: 1rem;
        }
    }
</style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container py-5">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2 class="h5 mb-0 text-white">Restore Deleted Students</h2>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#restoreAllModal" <?php echo mysqli_num_rows($deletedStudents) === 0 ? 'disabled' : ''; ?>>
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Restore All
                    </button>
                    <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteAllModal" <?php echo mysqli_num_rows($deletedStudents) === 0 ? 'disabled' : ''; ?>>
                        <i class="bi bi-trash me-1"></i> Delete All
                    </button>
                    <a href="index.php" class="btn btn-light btn-sm back-btn" style="font-weight: 500; letter-spacing: 0.5px;">
                        <i class="bi bi-arrow-left-circle me-1"></i> Back to Dashboard
                    </a>
                </div>
            </div>
            <div class="card-body p-4">
                <?php if (!empty($success_message)): ?>
                    <div class='alert alert-success alert-dismissible fade show' role='alert'>
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <?php echo htmlspecialchars(urldecode($success_message)); ?>
                        <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error_message)): ?>
                    <div class='alert alert-danger alert-dismissible fade show' role='alert'>
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <?php echo htmlspecialchars(urldecode($error_message)); ?>
                        <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($info_message)): ?>
                    <div class='alert alert-info alert-dismissible fade show' role='alert'>
                        <i class="bi bi-info-circle-fill me-2"></i>
                        <?php echo htmlspecialchars(urldecode($info_message)); ?>
                        <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                    </div>
                <?php endif; ?>

                <?php if (mysqli_num_rows($deletedStudents) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>LRN</th>
                                    <th>Student Name</th>
                                    <th>Parent/Guardian</th>
                                    <th>Deleted On</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($student = mysqli_fetch_assoc($deletedStudents)): ?>
                                    <tr>
                                        <td class="align-middle"><?php echo htmlspecialchars($student['lrn']); ?></td>
                                        <td class="align-middle">
                                            <?php 
                                                echo htmlspecialchars($student['last_name']) . ', ' . 
                                                     htmlspecialchars($student['first_name']) . 
                                                     (!empty($student['middle_initial']) ? ' ' . htmlspecialchars($student['middle_initial']) . '.' : '');
                                            ?>
                                        </td>
                                        <td class="align-middle"><?php echo htmlspecialchars($student['parent_guardian']); ?></td>
                                        <td class="align-middle"><?php echo date('M d, Y h:i A', strtotime($student['deleted_at'])); ?></td>
                                        <td class="align-middle">
                                            <button type="button" 
                                                class="btn btn-restore btn-sm restore-btn" 
                                                data-id="<?php echo $student['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>">
                                                <i class="bi bi-arrow-counterclockwise me-1"></i> Restore
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="bi bi-trash"></i>
                        <h4>No Deleted Students Found</h4>
                        <p class="text-muted">There are no students in the recycle bin.</p>
                        
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Restore All Confirmation Modal -->
    <div class="modal fade" id="restoreAllModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title">
                        <i class="bi bi-arrow-counterclockwise me-2"></i>Confirm Restore All
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <strong>Please confirm this action</strong>
                    </div>
                    <p>You are about to restore all students from the recycle bin. This will:</p>
                    <ul>
                        <li>Move all students back to your active students list</li>
                        <li>Restore all student information including LRN, Full Name, and other details</li>
                    </ul>
                    <p class="mb-0">Are you sure you want to proceed?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i> Cancel
                    </button>
                    <form method="post" action="restore_all_students.php" class="d-inline">
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Restore All Students
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete All Confirmation Modal -->
    <div class="modal fade" id="deleteAllModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>Permanently Delete All Students
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-octagon-fill me-2"></i>
                        <strong>Warning: This action cannot be undone!</strong>
                    </div>
                    <p>You are about to permanently delete all students in the recycle bin. This will remove:</p>
                    <ul>
                        <li>All student records from the deleted students list</li>
                        <li>All associated data including LRN, Full Name, Picture and other related information</li>
                    </ul>
                    <p class="mb-0">This action is irreversible. Are you absolutely sure you want to continue?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i> Cancel
                    </button>
                    <form method="post" action="hard_delete_all_students.php" class="d-inline">
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-trash-fill me-1"></i> Permanently Delete All
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Restore Confirmation Modal -->
    <div class="modal fade" id="restoreConfirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-arrow-counterclockwise me-2"></i>Confirm Restore
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to restore <strong id="studentToRestore">this student</strong>?</p>
                    <p class="text-muted mb-0">
                        <i class="bi bi-info-circle-fill me-1"></i> 
                        This will move the student back to your active students list.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form id="restoreForm" method="post" class="d-inline">
                        <input type="hidden" name="restore_id" id="restoreId" value="">
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Restore Student
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const restoreModal = new bootstrap.Modal(document.getElementById('restoreConfirmModal'));
        const restoreButtons = document.querySelectorAll('.restore-btn');
        const restoreIdInput = document.getElementById('restoreId');
        const studentNameSpan = document.getElementById('studentToRestore');
        
        restoreButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const studentId = this.getAttribute('data-id');
                const studentName = this.getAttribute('data-name');
                
                restoreIdInput.value = studentId;
                studentNameSpan.textContent = studentName;
                restoreModal.show();
            });
        });
    });
    </script>
</body>
</html>