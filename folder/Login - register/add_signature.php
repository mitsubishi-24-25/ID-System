<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

require_once "database.php";

$userId = $_SESSION['user_id'];
$message = "";

// Get user's section for the header
$section = "";
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle file upload only
    if (!empty($_FILES['signatureFile']['name'])) {
        $allowedTypes = ['image/jpeg', 'image/jpg'];
        if (in_array($_FILES['signatureFile']['type'], $allowedTypes)) {
            $ext = pathinfo($_FILES['signatureFile']['name'], PATHINFO_EXTENSION);
            // Ensure the file extension is jpg or jpeg
            if (!in_array(strtolower($ext), ['jpg', 'jpeg'])) {
                $message = "Invalid file extension. Only JPG files are allowed.";
            } else {
                $fileName = "uploads/signature_" . $userId . "_" . time() . "." . $ext;

                if (move_uploaded_file($_FILES['signatureFile']['tmp_name'], $fileName)) {
                    // Save $fileName path to DB
                    // Check if 'signature_path' column exists before adding
                    $columnCheckSql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'signature_path'";
                    $columnCheckResult = mysqli_query($conn, $columnCheckSql);
                    if (mysqli_num_rows($columnCheckResult) === 0) {
                        $alterSql = "ALTER TABLE users ADD COLUMN signature_path VARCHAR(255) NULL";
                        if (!mysqli_query($conn, $alterSql)) {
                            $message = "Failed to add signature_path column to database: " . mysqli_error($conn);
                        }
                    }
                    mysqli_free_result($columnCheckResult);

                    if (empty($message)) {
                        $stmt2 = mysqli_prepare($conn, "UPDATE users SET signature_path=? WHERE id=?");
                        mysqli_stmt_bind_param($stmt2, "si", $fileName, $userId);
                        if (mysqli_stmt_execute($stmt2)) {
                            $message = "Signature uploaded and saved successfully.";
                        } else {
                            $message = "Failed to save signature path to database.";
                        }
                        mysqli_stmt_close($stmt2);
                    }
                } else {
                    $message = "Failed to move uploaded file.";
                }
            }
        } else {
            $message = "Invalid file type. Only JPG files are allowed.";
        }
    } else {
        $message = "Please select a signature file to upload.";
    }
}

// Include header
include 'includes/header.php';
?>

<style>
    :root {
        --primary-green: #198754;
        --light-green: #d1e7dd;
        --dark-green: #0f5132;
    }
    
    .card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }
    
    .card-header {
        background-color: var(--primary-green);
        color: white;
        border-bottom: none;
        padding: 1.25rem 1.5rem;
    }
    
    .card-header h2, .card-header h5 {
        color: white;
        margin: 0;
        font-weight: 600;
    }
    
    .card-body {
        padding: 2rem;
    }
    
    .btn-primary {
        background-color: var(--primary-green);
        border: none;
        padding: 0.6rem 2rem;
        font-weight: 500;
        transition: all 0.3s;
    }
    
    .btn-primary:hover {
        background-color: #157347;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    
    .btn-outline-danger {
        padding: 0.6rem 2rem;
        font-weight: 500;
        transition: all 0.3s;
    }
    
    .btn-outline-danger:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    
    .form-control:focus {
        border-color: #198754;
        box-shadow: 0 0 0 0.25rem rgba(25, 135, 84, 0.25);
    }
    
    .alert {
        border: none;
        border-left: 4px solid var(--primary-green);
        border-radius: 0.5rem;
    }
    
    .alert-success {
        background-color: var(--light-green);
        color: var(--dark-green);
    }
    
    .btn-secondary {
        background-color: #6c757d;
        border: none;
        padding: 0.6rem 1.5rem;
    }
    
    .btn-secondary:hover {
        background-color: #5c636a;
    }
</style>

<div class="container py-5">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <i class="bi bi-pen-fill me-3" style="font-size: 1.75rem;"></i>
                <h2 class="mb-0">Add Your Signature</h2>
            </div>
            <a href="index.php" class="btn btn-outline-light">
                <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
            </a>
        </div>
        <div class="card-body">
            <?php if ($message): ?>
                <div class="alert alert-<?php echo strpos($message, 'success') !== false ? 'success' : 'info'; ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header bg-success bg-opacity-10">
                            <h5 class="mb-0 text-success"><i class="bi bi-upload me-2"></i>Upload Signature Image</h5>
                        </div>
                        <div class="card-body">
                            <form method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
                                <div class="mb-4">
                                    <label for="signatureFile" class="form-label fw-semibold">Choose a signature image (JPG only)</label>
                                    <div class="input-group">
                                        <input type="file" class="form-control" id="signatureFile" name="signatureFile" 
                                               accept="image/jpeg, image/jpg" required>
                                        <label class="input-group-text bg-success text-white" for="signatureFile">
                                            <i class="bi bi-file-earmark-image"></i>
                                        </label>
                                    </div>
                                    <div class="form-text text-muted mt-2">Only JPG files are allowed. Max file size: 5MB</div>
                                </div>
                                <div class="d-flex justify-content-end">
                                    <button type="submit" class="btn btn-success">
                                        <i class="bi bi-upload me-1"></i>Upload Signature
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

<?php
include 'includes/footer.php';
?>