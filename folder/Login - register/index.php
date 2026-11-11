<?php
session_start();
if (!isset($_SESSION["user_id"])) {
   header("Location: login.php");
   exit();
}

// Check for successful login
$loginSuccess = false;
if (isset($_SESSION['login_success']) && $_SESSION['login_success']) {
    $loginSuccess = true;
    unset($_SESSION['login_success']); // Clear the flag
}

// Add CSP header that allows necessary resources
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; img-src 'self' data:; font-src 'self' https://cdn.jsdelivr.net;");

require_once "database.php";

$userId = $_SESSION['user_id'];
$section = "";
$signaturePath = "";
$page_title = "";

// Get user's section and signature path
$sigSql = "SELECT section, signature_path FROM users WHERE id = ?";
$sigStmt = mysqli_prepare($conn, $sigSql);
if ($sigStmt) {
    mysqli_stmt_bind_param($sigStmt, "i", $userId);
    mysqli_stmt_execute($sigStmt);
    $sigResult = mysqli_stmt_get_result($sigStmt);
    if ($sigResult && mysqli_num_rows($sigResult) > 0) {
        $rowSig = mysqli_fetch_assoc($sigResult);
        $section = $rowSig['section'];
        $signaturePath = $rowSig['signature_path'];
    }
    mysqli_stmt_close($sigStmt);
}

// Get active students (not deleted) for the current advisor
$sql = "SELECT * FROM students WHERE advisor_id = ? AND is_deleted = 0 ORDER BY last_name, first_name";
$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    die("Error preparing statement: " . mysqli_error($conn));
}
$advisor_id = $_SESSION['user_id'];
if (!mysqli_stmt_bind_param($stmt, "i", $advisor_id)) {
    die("Error binding parameters: " . mysqli_stmt_error($stmt));
}
if (!mysqli_stmt_execute($stmt)) {
    die("Error executing statement: " . mysqli_stmt_error($stmt));
}
$result = mysqli_stmt_get_result($stmt);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Smart ID - Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <?php if ($loginSuccess): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                title: 'Login Successful!',
                text: 'Welcome back, <?php echo addslashes(htmlspecialchars($_SESSION["full_name"])); ?>!',
                icon: 'success',
                confirmButtonColor: '#2E7D32',
                confirmButtonText: 'Continue',
                allowOutsideClick: false,
                timer: 3000,
                timerProgressBar: true
            });
        });
    </script>
    <?php endif; ?>
</head>
<body>
<?php
// Include header
include 'includes/header.php';
?>
<div class="container">
    <div class="welcome-message mb-4">
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION["full_name"]); ?></h2>
        <p class="text-white">Adviser of <?php echo htmlspecialchars($section); ?></p>
    </div>

    <?php if (!empty($signaturePath)): ?>
        <div class="mb-4">
            <h3>Your Signature:</h3>
            <img src="<?php echo htmlspecialchars($signaturePath); ?>" alt="Your Signature" style="max-width:300px; max-height:150px; border:1px solid #ccc; border-radius:8px;" />
            <div class="mt-2">
                <a href="add_signature.php" class="btn btn-sm btn-primary me-2">Edit Signature</a>
                <form method="post" action="delete_signature.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete your signature?');">
                    <button type="submit" class="btn btn-sm btn-danger">Delete Signature</button>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <?php
    // Display success message if set in query parameters
    if (isset($_GET['success_message']) && !empty($_GET['success_message'])) {
        echo "<div class='alert alert-success alert-dismissible fade show' role='alert'>";
        echo htmlspecialchars($_GET['success_message']);
        echo "<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>";
        echo "</div>";
    }
    ?>

    <div class="d-flex flex-column gap-3 mb-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h2 class="mb-0">Your Students</h2>
            <div class="d-flex gap-2 flex-wrap">
                <button type="button" 
                        class="btn btn-danger" 
                        data-bs-toggle="modal" 
                        data-bs-target="#deleteAllModal" 
                        id="deleteAllBtn" 
                        <?php echo mysqli_num_rows($result) === 0 ? 'disabled' : ''; ?>>
                    <i class="bi bi-trash me-1"></i>Delete All Students
                </button>
            </div>
        </div>
        
        <!-- Action Buttons with Student Selection -->
        <div class="card shadow-sm">
            <div class="card-body">
                <h6 class="card-title mb-3"><i class="bi bi-gear me-2"></i>Student Actions</h6>
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="studentSelect" class="form-label fw-semibold">Select Student</label>
                        <select class="form-select" id="studentSelect" <?php echo mysqli_num_rows($result) === 0 ? 'disabled' : ''; ?>>
                            <option value="">-- Choose a student --</option>
                            <?php
                            // Reset the result pointer to iterate again
                            mysqli_data_seek($result, 0);
                            while ($row = mysqli_fetch_assoc($result)) {
                                $lrn = htmlspecialchars($row['lrn'], ENT_QUOTES);
                                $fullName = htmlspecialchars($row['first_name'] . ' ' . $row['last_name']);
                                $hasPhoto = !empty($row['photo']) && file_exists($row['photo']) ? '1' : '0';
                                echo "<option value='{$lrn}' data-name='{$fullName}' data-has-photo='{$hasPhoto}'>{$fullName} (LRN: {$lrn})</option>";
                            }
                            // Reset again for table display
                            mysqli_data_seek($result, 0);
                            ?>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="button" class="btn btn-warning text-white" id="editStudentBtn" disabled>
                                <i class="bi bi-pencil me-1"></i>Edit Student
                            </button>
                            <button type="button" class="btn btn-success" id="viewIdBtn" disabled>
                                <i class="bi bi-card-image me-1"></i>View ID
                            </button>
                            <button type="button" class="btn btn-info text-white" id="uploadPhotoBtn" disabled>
                                <i class="bi bi-camera me-1"></i>Upload Photo
                            </button>
                            <button type="button" class="btn btn-danger" id="deleteStudentBtn" disabled>
                                <i class="bi bi-trash me-1"></i>Delete Student
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="d-flex justify-content-end">
            <div class="search-container" style="width: 100%; max-width: 600px;">
                <div class="input-group shadow-sm">
                    <span class="input-group-text bg-white border-end-0" id="search-addon" style="border-color: #198754;">
                        <i class="bi bi-search" style="color: #198754;"></i>
                    </span>
                    <input type="text" 
                           id="searchInput" 
                           class="form-control border-start-0 ps-0" 
                           placeholder="Search students by name, LRN, or contact..." 
                           aria-label="Search students"
                           aria-describedby="search-addon">
                    <button class="btn btn-success px-3" type="button" id="searchButton" style="background-color: #198754;">
                        <i class="bi bi-search me-1"></i> Search
                    </button>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* General styles */
        body {
            background-color: #f5f5f0;
            font-family: 'Roboto', sans-serif;
        }
        
        /* Card container */
        .card-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        /* Search bar styles */
        .search-container {
            margin-bottom: 1.5rem;
        }
        
        .search-container .form-control:focus {
            box-shadow: 0 0 0 0.25rem rgba(25, 135, 84, 0.25);
            border-color: #198754;
        }
        
        .search-container .input-group-text {
            background-color: white;
            border-color: #198754;
            color: #198754;
        }
        
        .search-container .form-control {
            border-color: #198754;
            transition: all 0.3s ease;
        }
        
        .search-container .btn {
            background-color: #198754;
            border-color: #198754;
            transition: all 0.3s ease;
        }
        
        .search-container .btn:hover {
            background-color: #157347;
            border-color: #146c43;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        /* Table responsive container */
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        /* Table styles */
        .table {
            margin-bottom: 0;
            font-size: 1rem;
            min-width: 1400px;
        }
        
        .table thead th {
            background-color: #198754;
            color: white;
            font-weight: 600;
            vertical-align: middle;
            border: none;
            padding: 16px 12px;
            font-size: 1rem;
            letter-spacing: 0.3px;
        }
        
        .table tbody td {
            padding: 16px 12px;
            font-size: 0.95rem;
            vertical-align: middle;
        }
        
        .table thead th:first-child {
            border-top-left-radius: 8px;
        }
        
        .table thead th:last-child {
            border-top-right-radius: 8px;
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(25, 135, 84, 0.05);
        }
        
        /* Clickable row styles */
        .table tbody tr {
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .table tbody tr:hover {
            background-color: rgba(25, 135, 84, 0.1);
            transform: scale(1.01);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        /* Student Action Modal Styles */
        .student-action-modal .modal-header {
            background: linear-gradient(135deg, #198754 0%, #20c997 100%);
            color: white;
            border-radius: 8px 8px 0 0;
        }
        
        .student-action-modal .modal-content {
            border: none;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .action-btn {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            margin: 8px 0;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
            text-align: left;
            width: 100%;
        }
        
        .action-btn:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .action-btn i {
            font-size: 1.2rem;
            margin-right: 12px;
            width: 20px;
        }
        
        .action-btn.edit {
            background-color: #0d6efd;
            color: white;
        }
        
        .action-btn.edit:hover {
            background-color: #0b5ed7;
        }
        
        .action-btn.view-id {
            background-color: #6f42c1;
            color: white;
        }
        
        .action-btn.view-id:hover {
            background-color: #5a32a3;
        }
        
        .action-btn.upload {
            background-color: #fd7e14;
            color: white;
        }
        
        .action-btn.upload:hover {
            background-color: #e85d07;
        }
        
        .action-btn.delete {
            background-color: #dc3545;
            color: white;
        }
        
        .action-btn.delete:hover {
            background-color: #bb2d3b;
        }
        
        /* Button styles */
        .btn {
            transition: all 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-1px);
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .d-flex.justify-content-between.align-items-center {
                flex-direction: column;
                gap: 1rem;
            }
            
            /* Card container */
            .card-container {
                background: white;
                border-radius: 8px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                padding: 1.5rem;
                margin-bottom: 1.5rem;
            }
            
            /* Search bar styles */
            .search-container {
                margin-bottom: 1.5rem;
            }
            
            .search-container .form-control:focus {
                box-shadow: 0 0 0 0.25rem rgba(25, 135, 84, 0.25);
                border-color: #198754;
            }
            
            .search-container .input-group-text {
                background-color: white;
                border-color: #198754;
                color: #198754;
            }
            
            .search-container .form-control {
                border-color: #198754;
                transition: all 0.3s ease;
            }
            
            .search-container .btn {
                background-color: #198754;
                border-color: #198754;
                transition: all 0.3s ease;
            }
            
            .search-container .btn:hover {
                background-color: #157347;
                border-color: #146c43;
                transform: translateY(-1px);
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            }
            
            /* Table styles */
            .table {
                margin-bottom: 0;
                font-size: 1rem;
            }
            
            .table thead th {
                background-color: #198754;
                color: white;
                font-weight: 600;
                vertical-align: middle;
                border: none;
                padding: 16px 12px;
                font-size: 1rem;
            }
            
            .table tbody td {
                padding: 16px 12px;
                font-size: 0.95rem;
            }
            
            .table thead th:first-child {
                border-top-left-radius: 8px;
            }
            
            .table thead th:last-child {
                border-top-right-radius: 8px;
            }
            
            .table-hover tbody tr:hover {
                background-color: rgba(25, 135, 84, 0.05);
            }
            
            /* Button styles */
            .btn {
                transition: all 0.2s;
            }
            
            .btn:hover {
                transform: translateY(-1px);
            }
            
            /* Responsive adjustments */
            @media (max-width: 768px) {
                .d-flex.justify-content-between.align-items-center {
                    flex-direction: column;
                    gap: 1rem;
                }
                
                .search-container {
                    width: 100%;
                }
                
                .search-container .input-group {
                    width: 100% !important;
                }
                
                .table-responsive {
                    border-radius: 8px;
                    overflow: hidden;
                }
            }
        </style>
        <div class="table-responsive">
        <div id="noResults" class="alert alert-warning mt-3" style="display: none;">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>No results found. Please try a different search term.
        </div>
        <table class="table table-hover align-middle" id="studentsTable" style="border-collapse: separate; border-spacing: 0; border-radius: 8px; overflow: hidden; min-width: 1200px;">
            <thead>
                <tr>
                    <th>LRN</th>
                    <th>Last Name</th>
                    <th>M.I.</th>
                    <th>First Name</th>
                    <th>Birthdate</th>
                    <th>Parent/Guardian</th>
                    <th>Address</th>
                    <th>Contact</th>
                    <th>Photo</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                    // Store student data for use in modal
                    $lrn = htmlspecialchars($row['lrn'], ENT_QUOTES);
                    $firstName = htmlspecialchars($row['first_name'], ENT_QUOTES);
                    $lastName = htmlspecialchars($row['last_name'], ENT_QUOTES);
                    $middleInitial = htmlspecialchars($row['middle_initial'], ENT_QUOTES);
                    
                    echo "<tr class='student-row' data-lrn='{$lrn}' data-firstname='{$firstName}' data-lastname='{$lastName}' data-middleinitial='{$middleInitial}'>";
                    echo "<td>" . htmlspecialchars($row['lrn']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['last_name']) . "</td>";
                    echo "<td>" . (empty($row['middle_initial']) ? "N/A" : htmlspecialchars($row['middle_initial'])) . "</td>";
                    echo "<td>" . htmlspecialchars($row['first_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['birthdate']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['parent_guardian']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['permanent_address']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['contact_number']) . "</td>";

                    // Student photo column display - only show image
                    echo "<td class='text-center align-middle' style='width: 180px; padding: 15px !important;'>";
                    if (!empty($row['photo']) && file_exists($row['photo'])) {
                        echo "<img src='" . htmlspecialchars($row['photo']) . "' alt='Student Photo' style='width:150px; height:150px; object-fit:cover; border:2px solid #ddd; border-radius:8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);' />";
                    } else {
                        echo "<div style='width:150px; height:150px; background-color:#f8f9fa; border:2px dashed #dee2e6; border-radius:8px; display:flex; align-items:center; justify-content:center; margin:0 auto;'>";
                        echo "<div class='text-center'><i class='bi bi-person' style='font-size: 2rem; color: #6c757d;'></i><br><small class='text-muted'>No Photo</small></div>";
                        echo "</div>";
                    }
                    echo "</td>";

                    echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='9'>No students found.</td></tr>";
                }
                mysqli_stmt_close($stmt);
                ?>
            </tbody>
        </table>
        </div>
        
        <script>
        // Initialize search functionality and student row clicks
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const searchButton = document.getElementById('searchButton');
            const table = document.getElementById('studentsTable');
            const studentActionModal = new bootstrap.Modal(document.getElementById('studentActionModal'));
            
            if (!searchInput || !table) return;
            
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
            
            // Search functionality
            function filterTable() {
                const searchText = searchInput.value.trim().toLowerCase();
                let anyVisible = false;
                
                for (let i = 0; i < rows.length; i++) {
                    const row = rows[i];
                    const cells = row.getElementsByTagName('td');
                    let found = false;
                    
                    for (let j = 0; j < cells.length; j++) {
                        const cellText = cells[j].textContent || cells[j].innerText;
                        if (cellText.toLowerCase().includes(searchText)) {
                            found = true;
                            anyVisible = true;
                            break;
                        }
                    }
                    
                    row.style.display = found ? '' : 'none';
                }
                
                // Show/hide no results message
                const noResults = document.getElementById('noResults');
                if (searchText.length > 0 && !anyVisible) {
                    noResults.style.display = '';
                } else {
                    noResults.style.display = 'none';
                }
            }
            
            // Student row click functionality
            function setupRowClicks() {
                const studentRows = document.querySelectorAll('.student-row');
                studentRows.forEach(row => {
                    row.addEventListener('click', function(e) {
                        // Prevent click if clicking on a button or link inside the row
                        if (e.target.tagName === 'BUTTON' || e.target.tagName === 'A' || e.target.closest('button') || e.target.closest('a')) {
                            return;
                        }
                        
                        // Get student data from data attributes
                        const lrn = this.dataset.lrn;
                        const firstName = this.dataset.firstname;
                        const lastName = this.dataset.lastname;
                        const middleInitial = this.dataset.middleinitial;
                        
                        // Update modal with student information
                        const fullName = middleInitial && middleInitial !== 'N/A' 
                            ? `${firstName} ${middleInitial}. ${lastName}`
                            : `${firstName} ${lastName}`;
                        
                        document.getElementById('modalStudentName').textContent = fullName;
                        document.getElementById('modalStudentLRN').textContent = lrn;
                        
                        // Store current student data for action functions
                        window.currentStudent = {
                            lrn: lrn,
                            firstName: firstName,
                            lastName: lastName,
                            middleInitial: middleInitial,
                            fullName: fullName
                        };
                        
                        // Show the modal
                        studentActionModal.show();
                    });
                });
            }
            
            // Action functions
            window.editStudent = function() {
                if (window.currentStudent) {
                    // Redirect to edit page with student LRN
                    window.location.href = `edit_student.php?lrn=${encodeURIComponent(window.currentStudent.lrn)}`;
                }
            };
            
            window.viewStudentID = function() {
                if (window.currentStudent) {
                    // Hide action modal and show student ID modal
                    studentActionModal.hide();
                    
                    // Show student ID modal
                    const studentIdModal = new bootstrap.Modal(document.getElementById('studentIdModal'));
                    studentIdModal.show();
                    
                    // Load the student ID using iframe for better compatibility
                    const studentIdContent = document.getElementById('studentIdContent');
                    studentIdContent.innerHTML = `
                        <div style="text-align: center; margin-bottom: 10px;">
                            <small class="text-muted">Loading student ID...</small>
                        </div>
                        <iframe src="view_student_id.php?lrn=${encodeURIComponent(window.currentStudent.lrn)}" 
                                style="width: 100%; height: 500px; border: none; border-radius: 8px;"
                                onload="this.style.display='block'; this.previousElementSibling.style.display='none';"
                                onerror="this.parentElement.innerHTML='<div class=\\'alert alert-danger\\'>Error loading student ID. Please try again.</div>';">
                        </iframe>
                    `;
                }
            };
            
            window.uploadPhoto = function() {
                if (window.currentStudent) {
                    // Redirect to photo upload page with student LRN
                    window.location.href = `upload_photo.php?lrn=${encodeURIComponent(window.currentStudent.lrn)}`;
                }
            };
            
            window.deleteStudent = function() {
                if (window.currentStudent) {
                    // Hide action modal and show delete confirmation modal
                    studentActionModal.hide();
                    
                    // Update delete modal with student name
                    document.getElementById('studentName').textContent = window.currentStudent.fullName;
                    
                    // Set up delete form action
                    const deleteForm = document.getElementById('deleteForm');
                    if (deleteForm) {
                        deleteForm.action = `delete_student.php?lrn=${encodeURIComponent(window.currentStudent.lrn)}`;
                    }
                    
                    // Show delete confirmation modal
                    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
                    deleteModal.show();
                }
            };
            
            // Initialize
            searchInput.addEventListener('input', filterTable);
            searchButton.addEventListener('click', filterTable);
            setupRowClicks();
        });
        </script>
    </div>
</main>

<!-- Delete All Confirmation Modal -->
<div class="modal fade" id="deleteAllModal" tabindex="-1" aria-labelledby="deleteAllModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteAllModalLabel">Confirm Delete All Students</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="lead">Are you sure you want to delete <strong>ALL</strong> students?</p>
                <p class="text-danger">
                    <i class="bi bi-exclamation-triangle-fill"></i> This action cannot be undone. All student records will be permanently deleted.
                </p>
                <p>This will delete all students associated with your account.</p>
                <p>Are you sure you want to delete all students? This action will move all students to the trash and cannot be undone.</p>
                <p class="text-danger"><strong>Warning:</strong> This will mark all students as deleted. You can restore them later from the trash if needed.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg me-1"></i>Cancel
                </button>
                <form action="delete_all_students.php" method="post" id="deleteAllForm">
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash me-1"></i>Delete All Students
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Student Action Modal -->
<div class="modal fade student-action-modal" id="studentActionModal" tabindex="-1" aria-labelledby="studentActionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="studentActionModalLabel">
                    <i class="bi bi-person-circle me-2"></i>Student Actions
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="student-info mb-3 p-3 bg-light rounded">
                    <h6 class="mb-2 text-primary">Student Information</h6>
                    <div class="row">
                        <div class="col-6">
                            <strong>Name:</strong> <span id="modalStudentName"></span>
                        </div>
                        <div class="col-6">
                            <strong>LRN:</strong> <span id="modalStudentLRN"></span>
                        </div>
                    </div>
                </div>
                
                <div class="action-buttons">
                    <button type="button" class="action-btn edit" onclick="editStudent()">
                        <i class="bi bi-pencil-square"></i>
                        Edit Student Information
                    </button>
                    
                    <button type="button" class="action-btn view-id" onclick="viewStudentID()">
                        <i class="bi bi-card-text"></i>
                        View Student ID
                    </button>
                    
                    <button type="button" class="action-btn upload" onclick="uploadPhoto()">
                        <i class="bi bi-cloud-upload"></i>
                        Upload Photo
                    </button>
                    
                    <button type="button" class="action-btn delete" onclick="deleteStudent()">
                        <i class="bi bi-trash"></i>
                        Delete Student
                    </button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg me-1"></i>Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Student ID Modal -->
<div class="modal fade student-id-modal" id="studentIdModal" tabindex="-1" aria-labelledby="studentIdModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="studentIdModalLabel">
                    <i class="bi bi-card-text me-2"></i>Student ID Card
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="studentIdContent">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading student ID...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg me-1"></i>Close
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Single Student Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteModalLabel"><i class="bi bi-exclamation-triangle-fill me-2"></i>Confirm Deletion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong id="studentName"></strong>?</p>
                <p class="text-muted mb-0"><i class="bi bi-info-circle-fill me-1"></i> You can restore this student's data from the 'Restore Deleted Students' page if needed.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteForm" method="post" action="delete_student.php" class="d-inline">
                    <input type="hidden" name="lrn" id="deleteLrn" value="" />
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash me-1"></i> Delete Student
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize modals
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    const deleteAllModal = new bootstrap.Modal(document.getElementById('deleteAllModal'));
    
    // Student selection dropdown functionality
    const studentSelect = document.getElementById('studentSelect');
    const editStudentBtn = document.getElementById('editStudentBtn');
    const viewIdBtn = document.getElementById('viewIdBtn');
    const uploadPhotoBtn = document.getElementById('uploadPhotoBtn');
    const deleteStudentBtn = document.getElementById('deleteStudentBtn');
    
    // Enable/disable action buttons based on student selection
    studentSelect.addEventListener('change', function() {
        const selectedLrn = this.value;
        const isSelected = selectedLrn !== '';
        
        editStudentBtn.disabled = !isSelected;
        viewIdBtn.disabled = !isSelected;
        uploadPhotoBtn.disabled = !isSelected;
        deleteStudentBtn.disabled = !isSelected;
        
        // Update upload photo button text based on whether student has photo
        if (isSelected) {
            const selectedOption = this.options[this.selectedIndex];
            const hasPhoto = selectedOption.getAttribute('data-has-photo') === '1';
            uploadPhotoBtn.innerHTML = hasPhoto 
                ? '<i class="bi bi-arrow-repeat me-1"></i>Change Photo' 
                : '<i class="bi bi-camera me-1"></i>Upload Photo';
        }
    });
    
    // Edit Student button click
    editStudentBtn.addEventListener('click', function() {
        const selectedLrn = studentSelect.value;
        if (selectedLrn) {
            window.location.href = 'edit_student.php?lrn=' + encodeURIComponent(selectedLrn);
        }
    });
    
    // View ID button click
    viewIdBtn.addEventListener('click', function() {
        const selectedLrn = studentSelect.value;
        const selectedOption = studentSelect.options[studentSelect.selectedIndex];
        const studentName = selectedOption.getAttribute('data-name');
        if (selectedLrn) {
            showIdModal(selectedLrn, studentName);
        }
    });
    
    // Upload Photo button click
    uploadPhotoBtn.addEventListener('click', function() {
        const selectedLrn = studentSelect.value;
        if (selectedLrn) {
            window.location.href = 'upload_photo.php?lrn=' + encodeURIComponent(selectedLrn);
        }
    });
    
    // Delete Student button click
    deleteStudentBtn.addEventListener('click', function() {
        const selectedLrn = studentSelect.value;
        const selectedOption = studentSelect.options[studentSelect.selectedIndex];
        const studentName = selectedOption.getAttribute('data-name');
        if (selectedLrn) {
            document.getElementById('deleteLrn').value = selectedLrn;
            document.getElementById('studentName').textContent = studentName;
            document.getElementById('deleteForm').action = 'delete_student.php?lrn=' + encodeURIComponent(selectedLrn);
            deleteModal.show();
        }
    });
    
    // Function to show ID modal
    function showIdModal(lrn, name) {
        console.log('Opening ID modal for:', name, 'LRN:', lrn);
        
        // Create modal HTML with better styling
        const modalHtml = `
            <div class="modal fade" id="idModal" tabindex="-1" aria-labelledby="idModalLabel" aria-hidden="true" style="display: none;">
                <div class="modal-dialog modal-xl modal-dialog-centered">
                    <div class="modal-content" style="border: none; border-radius: 15px; overflow: hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
                        <div class="modal-header bg-gradient" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; border: none; padding: 20px;">
                            <h5 class="modal-title mb-0" id="idModalLabel" style="font-size: 18px; font-weight: 600;">
                                <i class="bi bi-card-image me-2"></i>
                                Student ID Preview: ${name}
                            </h5>
                        </div>
                        <div class="modal-body p-0" style="background: #f8f9fa; max-height: 600px; overflow-y: auto;">
                            <div class="text-center p-4">
                                <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mb-0 text-muted">Loading student ID preview...</p>
                            </div>
                        </div>
                        <div class="modal-footer" style="background: #ffffff; border-top: 1px solid #dee2e6; padding: 30px 55px; position: relative;">
                            <div class="text-muted small d-flex align-items-center" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); height: 100%;">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Front and Back ID Card Preview
                                </div>
                                <div class="d-flex align-items-center" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); height: 100%;">
                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" style="border-radius: 20px; padding: 8px 20px;">
                                        <i class="bi bi-x-lg me-1"></i>Close Preview
                                    </button>
                                </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        console.log('Modal HTML created');
        
        // Add modal to page
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        
        // Add backdrop with better styling
        const backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop fade show';
        backdrop.style.cssText = 'background-color: rgba(0, 0, 0, 0.7); backdrop-filter: blur(5px);';
        document.body.appendChild(backdrop);
        
        console.log('Modal and backdrop added to DOM');
        
        // Show modal with animation
        setTimeout(() => {
            const modal = document.getElementById('idModal');
            if (modal) {
                console.log('Modal found, showing...');
                modal.style.display = 'block';
                modal.classList.add('show');
                
                // Initialize Bootstrap modal
                const bsModal = new bootstrap.Modal(modal);
                bsModal.show();
                
                console.log('Bootstrap modal initialized');
                
                // Load ID content
                loadIdContent(lrn);
                
                // Remove modal and backdrop when hidden
                modal.addEventListener('hidden.bs.modal', function() {
                    console.log('Modal hidden, cleaning up');
                    modal.remove();
                    if (backdrop.parentNode) {
                        backdrop.remove();
                    }
                });
            } else {
                console.error('Modal not found!');
            }
        }, 100);
    }
    
    // Function to load ID content
    async function loadIdContent(lrn) {
        try {
            const response = await fetch('view_student_id.php?lrn=' + encodeURIComponent(lrn));
            const html = await response.text();
            
            // Update modal body with ID content
            const modalBody = document.querySelector('#idModal .modal-body');
            if (modalBody) {
                modalBody.innerHTML = html;
            }
        } catch (error) {
            console.error('Error loading ID:', error);
            const modalBody = document.querySelector('#idModal .modal-body');
            if (modalBody) {
                modalBody.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        Error loading student ID. Please try again.
                    </div>
                `;
            }
        }
    }
    
    // Handle form submission
    const deleteForm = document.getElementById('deleteForm');
    if (deleteForm) {
        deleteForm.addEventListener('submit', function(e) {
            // The form will submit to the URL we set in the action
        });
    }

    const deleteAllForm = document.getElementById('deleteAllForm');
    if (deleteAllForm) {
        deleteAllForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Show loading state
            const submitBtn = deleteAllForm.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Deleting...';
            
            try {
                const response = await fetch('delete_all_students.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    credentials: 'same-origin'
                });
                
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                
                const data = await response.json();
                
                // Close the modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('deleteAllModal'));
                modal.hide();
                
                if (data.success) {
                    // Show success message with SweetAlert2
                    Swal.fire({
                        title: "Success!",
                        text: data.message,
                        icon: "success",
                        confirmButtonColor: "#2E7D32",
                        confirmButtonText: "OK",
                        timer: 3000,
                        timerProgressBar: true,
                        allowOutsideClick: false
                    }).then((result) => {
                        // Reload the page after the alert is closed
                        window.location.reload();
                    });
                } else {
                    throw new Error(data.message || 'Failed to delete students');
                }
            } catch (error) {
                console.error('Error:', error);
                
                // Show error message with SweetAlert2
                Swal.fire({
                    title: "Error!",
                    text: error.message || 'An error occurred while deleting students. Please try again.',
                    icon: "error",
                    confirmButtonColor: "#d33",
                    confirmButtonText: "OK",
                    allowOutsideClick: false
                });
                
                // Reset button state
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            }
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>