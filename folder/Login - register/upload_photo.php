<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

require_once "database.php";

$userId = $_SESSION['user_id'];
$errors = [];
$success_message = "";

$lrn = $_GET['lrn'] ?? '';

if (empty($lrn)) {
    die("No student LRN provided. Please go back to the student selection page.");
} else {
    $sqlCheck = "SELECT first_name, last_name, photo FROM students WHERE lrn = ? AND advisor_id = ?";
    $stmtCheck = mysqli_prepare($conn, $sqlCheck);
    $advisor_id = $userId; // Using advisor_id instead of user_id
    mysqli_stmt_bind_param($stmtCheck, "si", $lrn, $advisor_id);
    mysqli_stmt_execute($stmtCheck);
    $resultCheck = mysqli_stmt_get_result($stmtCheck);
    
    if (mysqli_num_rows($resultCheck) === 0) {
        die("Student not found or you do not have permission to edit this student.");
    }
    
    $student = mysqli_fetch_assoc($resultCheck);
    $photoPath = $student['photo']; 
    mysqli_stmt_close($stmtCheck);

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_photo'])) {
        if (isset($_FILES['student_photo']) && $_FILES['student_photo']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['student_photo'];
            $fileName = $file['name'];
            $fileTmpName = $file['tmp_name'];
            $fileSize = $file['size'];
            $fileError = $file['error'];
            
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png'];

            if (in_array($fileExt, $allowed)) {
                if ($fileError === 0) {
                    if ($fileSize < 5000000) {
                        if (!file_exists('uploads')) {
                            mkdir('uploads', 0777, true);
                        }

                        $newFileName = 'student_' . $lrn . '_' . time() . '.' . $fileExt;
                        $fileDestination = 'uploads/' . $newFileName;

                        if (move_uploaded_file($fileTmpName, $fileDestination)) {
                            $updateSql = "UPDATE students SET photo = ? WHERE lrn = ? AND advisor_id = ?";
                            $updateStmt = mysqli_prepare($conn, $updateSql);
                            
                            if ($updateStmt === false) {
                                $errors[] = "Error preparing update statement.";
                            } else {
                                $advisor_id = $userId; // Using advisor_id instead of user_id
                                mysqli_stmt_bind_param($updateStmt, 'ssi', $fileDestination, $lrn, $advisor_id);
                                
                                if (mysqli_stmt_execute($updateStmt)) {
                                    if (!empty($photoPath) && file_exists($photoPath) && $photoPath !== $fileDestination) {
                                        unlink($photoPath);
                                    }
                                    $success_message = "Photo uploaded successfully!";
                                    header("Location: upload_photo.php?lrn=" . urlencode($lrn) . "&success=1");
                                    exit();
                                } else {
                                    $errors[] = "Error updating photo in database.";
                                }
                                mysqli_stmt_close($updateStmt);
                            }
                        } else {
                            $errors[] = "There was an error moving the uploaded file.";
                        }
                    } else {
                        $errors[] = "File is too large. Maximum size is 5MB.";
                    }
                } else {
                    $errors[] = "File upload error code: " . $fileError;
                }
            } else {
                $errors[] = "You cannot upload files of this type. Only JPG, JPEG & PNG files are allowed.";
            }
        } else {
            if ($_FILES['student_photo']['error'] == UPLOAD_ERR_INI_SIZE || $_FILES['student_photo']['error'] == UPLOAD_ERR_FORM_SIZE) {
                $errors[] = "The selected file exceeds the maximum allowed upload size.";
            } else if (!isset($_FILES['student_photo'])) {
                $errors[] = "Please select a file to upload.";
            } else {
                $errors[] = "An unknown file upload error occurred (Code: " . $_FILES['student_photo']['error'] . ").";
            }
        }
    }
}

include 'includes/header.php';
?>

<div class="container">
    <div class="form-container">
        <div class="form-header">
            <h2><i class="bi bi-camera me-2"></i> Upload Student Photo</h2>
            <p class="mb-0 mt-2">Update profile picture for student</p>
        </div>
        
        <div class="form-body">
            <div class="text-center mb-4">
                <h5 class="mb-3"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h5>
                <p class="text-muted">LRN: <?php echo htmlspecialchars($lrn); ?></p>
            </div>

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

            <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
                <div class="alert alert-success d-flex align-items-center">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <div>Photo uploaded successfully!</div>
                </div>
            <?php endif; ?>

            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card shadow-sm mb-4">
                        <div class="card-body text-center p-4">
                            <h5 class="card-title text-muted mb-4">Current Photo</h5>
                            <div class="d-flex justify-content-center">
                                <?php if (!empty($photoPath) && file_exists($photoPath)): ?>
                                    <div style="width: 200px; height: 200px; overflow: hidden;" class="rounded-3 border">
                                        <img src="<?php echo htmlspecialchars($photoPath); ?>" 
                                             alt="Student Photo" 
                                             style="width: 100%; height: 100%; object-fit: cover;">
                                    </div>
                                <?php else: ?>
                                    <div class="d-flex flex-column align-items-center">
                                        <div class="bg-light rounded-3 d-flex align-items-center justify-content-center" 
                                             style="width: 200px; height: 200px;">
                                            <i class="bi bi-person-circle" style="font-size: 80px; color: #6c757d;"></i>
                                        </div>
                                        <p class="mt-2 text-muted">No photo uploaded</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow-sm">
                        <div class="card-body p-4">
                            <h5 class="card-title mb-4">Upload New Photo</h5>
                            <form method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
                                <div class="mb-4">
                                    <div class="input-group">
                                        <input type="file" 
                                               class="form-control" 
                                               id="student_photo" 
                                               name="student_photo" 
                                               accept="image/jpeg, image/png" 
                                               required 
                                               onchange="previewImage(event)">
                                        <label class="input-group-text" for="student_photo">
                                            <i class="bi bi-cloud-arrow-up-fill"></i>
                                        </label>
                                    </div>
                                    <div class="form-text">Accepted formats: JPG, PNG (Max 5MB)</div>
                                </div>

                                <div class="text-center mb-4">
                                    <h6>Photo Preview</h6>
                                    <div style="width: 200px; height: 200px; margin: 0 auto; overflow: hidden;" class="rounded-3 border">
                                        <img id="photoPreview" 
                                             style="width: 100%; height: 100%; object-fit: cover; display: none;" 
                                             alt="Photo Preview">
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                                    <a href="index.php" class="btn btn-cancel text-white">
                                        <i class="bi bi-arrow-left-circle me-1"></i> Back to Dashboard
                                    </a>
                                    <button type="submit" name="upload_photo" class="btn btn-submit text-white">
                                        <i class="bi bi-upload me-1"></i> Upload Photo
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

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
        justify-content: center;
        gap: 10px;
    }
    .form-header h2 i {
        font-size: 1.5rem;
    }
    .form-body {
        padding: 2rem;
    }
    .form-text {
        font-size: 0.85rem;
        color: #6c757d;
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
    .card {
        border: none;
        border-radius: 10px;
        overflow: hidden;
    }
    .card-title {
        color: #198754;
        font-weight: 500;
    }
</style>

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

function previewImage(event) {
    const preview = document.getElementById('photoPreview');
    const file = event.target.files[0];
    const reader = new FileReader();

    reader.onload = function() {
        preview.src = reader.result;
        preview.style.display = 'block';
    }

    if (file) {
        reader.readAsDataURL(file);
    } else {
        preview.src = '';
        preview.style.display = 'none';
    }
}
</script>

<?php include 'includes/footer.php'; ?>