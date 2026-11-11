<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$current_page = basename($_SERVER['PHP_SELF']);
$success = '';
$error = '';

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $target_dir = "assets/uploads/id_templates/";
    
    // Create directory if it doesn't exist
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    // Handle front ID upload
    if (isset($_FILES['front_id_template']) && $_FILES['front_id_template']['error'] == 0) {
        $file_extension = strtolower(pathinfo($_FILES["front_id_template"]["name"], PATHINFO_EXTENSION));
        $new_filename = 'front_id_template_' . time() . '.' . $file_extension;
        $target_file = $target_dir . $new_filename;
        
        // Check if image file is an actual image
        $check = getimagesize($_FILES["front_id_template"]["tmp_name"]);
        if ($check === false) {
            $error = "Front ID file is not an image.";
        } 
        // Check file size (5MB max)
        else if ($_FILES["front_id_template"]["size"] > 5000000) {
            $error = "Sorry, front ID file is too large. Maximum size is 5MB.";
        }
        // Allow certain file formats
        else if (!in_array($file_extension, ["jpg","jpeg"])) {
            $error = "Sorry, only JPG and JPEG are allowed for front ID.";
        }
        // If everything is ok, try to upload file
        else if (move_uploaded_file($_FILES["front_id_template"]["tmp_name"], $target_file)) {
            // Save the front template path to session
            $_SESSION['front_id_template'] = $target_file;
            $success = "Front ID template uploaded successfully.";
        } else {
            $error = "Sorry, there was an error uploading your front ID file. Please try again.";
        }
    }
    
    // Handle back ID upload
    if (isset($_FILES['back_id_template']) && $_FILES['back_id_template']['error'] == 0) {
        $file_extension = strtolower(pathinfo($_FILES["back_id_template"]["name"], PATHINFO_EXTENSION));
        $new_filename = 'back_id_template_' . time() . '.' . $file_extension;
        $target_file = $target_dir . $new_filename;
        
        // Check if image file is an actual image
        $check = getimagesize($_FILES["back_id_template"]["tmp_name"]);
        if ($check === false) {
            $error = "Back ID file is not an image.";
        } 
        // Check file size (5MB max)
        else if ($_FILES["back_id_template"]["size"] > 5000000) {
            $error = "Sorry, back ID file is too large. Maximum size is 5MB.";
        }
        // Allow certain file formats
        else if (!in_array($file_extension, ["jpg","jpeg"])) {
            $error = "Sorry, only JPG and JPEG are allowed for back ID.";
        }
        // If everything is ok, try to upload file
        else if (move_uploaded_file($_FILES["back_id_template"]["tmp_name"], $target_file)) {
            // Save the back template path to session
            $_SESSION['back_id_template'] = $target_file;
            if (!empty($success)) {
                $success .= " Back ID template uploaded successfully.";
            } else {
                $success = "Back ID template uploaded successfully.";
            }
        } else {
            $error = "Sorry, there was an error uploading your back ID file. Please try again.";
        }
    }
}

// Include header
include 'includes/header.php';
?>

<div class="container py-5">
    <div class="card mx-auto" style="max-width: 900px;">
        <div class="card-header d-flex align-items-center" style="background-color: #28a745; color: white; border-bottom: none; border-radius: 0.375rem 0.375rem 0 0;">
            <i class="bi bi-card-image me-3" style="font-size: 1.5rem; color: white;"></i>
            <h2 class="mb-0" style="color: white; font-weight: 600;">Upload ID Template</h2>
        </div>
        <div class="card-body">
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

            <form action="upload_id_template.php" method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-4">
                            <label for="front_id_template" class="form-label">
                                <i class="bi bi-file-earmark-arrow-up me-2"></i>Front ID Template
                            </label>
                            <input type="file" class="form-control" id="front_id_template" name="front_id_template" accept="image/*">
                            <div class="form-text text-muted">
                                <i class="bi bi-info-circle me-1"></i>Supported formats: JPG and JPEG (Max 5MB)
                            </div>
                        </div>
                        
                        <?php if (isset($_SESSION['front_id_template']) && file_exists($_SESSION['front_id_template'])): ?>
                        <div class="border p-3 rounded">
                            <h6 class="mb-3">
                                <i class="bi bi-card-image me-2"></i>Current Front Template
                            </h6>
                            <div class="text-center">
                                <img src="<?php echo $_SESSION['front_id_template']; ?>" 
                                     alt="Current Front ID Template" 
                                     class="img-fluid rounded border"
                                     style="max-height: 150px; width: auto;">
                                <p class="text-muted small mt-2 mb-0">
                                    <i class="bi bi-clock-history me-1"></i>
                                    Last updated: <?php echo date("M d, Y h:i A", filemtime($_SESSION['front_id_template'])); ?>
                                </p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-4">
                            <label for="back_id_template" class="form-label">
                                <i class="bi bi-file-earmark-arrow-up me-2"></i>Back ID Template
                            </label>
                            <input type="file" class="form-control" id="back_id_template" name="back_id_template" accept="image/*">
                            <div class="form-text text-muted">
                                <i class="bi bi-info-circle me-1"></i>Supported formats: JPG and JPEG (Max 5MB)
                            </div>
                        </div>
                        
                        <?php if (isset($_SESSION['back_id_template']) && file_exists($_SESSION['back_id_template'])): ?>
                        <div class="border p-3 rounded">
                            <h6 class="mb-3">
                                <i class="bi bi-card-image me-2"></i>Current Back Template
                            </h6>
                            <div class="text-center">
                                <img src="<?php echo $_SESSION['back_id_template']; ?>" 
                                     alt="Current Back ID Template" 
                                     class="img-fluid rounded border"
                                     style="max-height: 150px; width: auto;">
                                <p class="text-muted small mt-2 mb-0">
                                    <i class="bi bi-clock-history me-1"></i>
                                    Last updated: <?php echo date("M d, Y h:i A", filemtime($_SESSION['back_id_template'])); ?>
                                </p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="d-flex justify-content-between mt-4 pt-3 border-top">
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
                    </a>
                    <button type="submit" class="btn" style="background-color: #28a745; color: white;">
                        <i class="bi bi-upload me-2"></i>Upload Template
                    </button>
                </div>
            </form>
            
            <!-- Preview containers side by side -->
            <div class="row mt-4 pt-3 border-top" id="previewRow" style="display: none; width: 100%;">
                <!-- Front ID Preview -->
                <div class="col-md-6 mb-3" style="display: inline-block; float: left; width: 48%;">
                    <div class="border p-3 rounded">
                        <h6 class="mb-3">
                            <i class="bi bi-card-image me-2"></i>Front ID Preview
                        </h6>
                        <div class="text-center">
                            <img id="frontImagePreview" 
                                 src="#" 
                                 alt="Front ID Preview" 
                                 class="img-fluid rounded border"
                                 style="max-height: 200px; width: auto; display: none;">
                        </div>
                    </div>
                </div>
                
                <!-- Back ID Preview -->
                <div class="col-md-6 mb-3" style="display: inline-block; float: right; width: 48%;">
                    <div class="border p-3 rounded">
                        <h6 class="mb-3">
                            <i class="bi bi-card-image me-2"></i>Back ID Preview
                        </h6>
                        <div class="text-center">
                            <img id="backImagePreview" 
                                 src="#" 
                                 alt="Back ID Preview" 
                                 class="img-fluid rounded border"
                                 style="max-height: 200px; width: auto; display: none;">
                        </div>
                    </div>
                </div>
                <div style="clear: both;"></div>
            </div>
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

// Preview image before upload
document.addEventListener('DOMContentLoaded', function() {
    const frontFileInput = document.getElementById('front_id_template');
    const backFileInput = document.getElementById('back_id_template');
    const previewRow = document.getElementById('previewRow');
    const frontImagePreview = document.getElementById('frontImagePreview');
    const backImagePreview = document.getElementById('backImagePreview');
    
    // Handle front ID preview
    if (frontFileInput) {
        frontFileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    frontImagePreview.src = e.target.result;
                    frontImagePreview.style.display = 'block';
                    previewRow.style.display = 'block';
                    
                    // Scroll to preview
                    previewRow.scrollIntoView({ behavior: 'smooth' });
                };
                reader.readAsDataURL(file);
            }
        });
    }
    
    // Handle back ID preview
    if (backFileInput) {
        backFileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    backImagePreview.src = e.target.result;
                    backImagePreview.style.display = 'block';
                    previewRow.style.display = 'block';
                    
                    // Scroll to preview
                    previewRow.scrollIntoView({ behavior: 'smooth' });
                };
                reader.readAsDataURL(file);
            }
        });
    }
    
    // Close alerts after 5 seconds
    setTimeout(() => {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
});
</script>

<?php include 'includes/footer.php'; ?>