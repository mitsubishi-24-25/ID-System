<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

require_once "database.php";

$userId = $_SESSION['user_id'];
$message = "";

// Get user's section for header
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

// Get current principal name and signature if exists
$currentPrincipalName = "";
$currentSignature = "";
$principalSql = "SELECT principal_name, principal_signature_path FROM users WHERE id = ?";
$principalStmt = mysqli_prepare($conn, $principalSql);
if ($principalStmt) {
    mysqli_stmt_bind_param($principalStmt, "i", $userId);
    mysqli_stmt_execute($principalStmt);
    $principalResult = mysqli_stmt_get_result($principalStmt);
    if ($principalResult && mysqli_num_rows($principalResult) > 0) {
        $rowPrincipal = mysqli_fetch_assoc($principalResult);
        $currentPrincipalName = $rowPrincipal['principal_name'] ?? "";
        $currentSignature = $rowPrincipal['principal_signature_path'] ?? "";
    }
    mysqli_stmt_close($principalStmt);
}

// Include header
include 'includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle principal name update
    if (!empty($_POST['principal_name'])) {
        $principalName = trim($_POST['principal_name']);
        
        // Check if principal_name column exists before adding
        $columnCheckSql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'principal_name'";
        $columnCheckResult = mysqli_query($conn, $columnCheckSql);
        if (mysqli_num_rows($columnCheckResult) === 0) {
            $alterSql = "ALTER TABLE users ADD COLUMN principal_name VARCHAR(255) NULL AFTER signature_path";
            if (!mysqli_query($conn, $alterSql)) {
                $message = "Failed to add principal_name column to database: " . mysqli_error($conn);
            }
        }
        mysqli_free_result($columnCheckResult);

        if (empty($message)) {
            $stmt2 = mysqli_prepare($conn, "UPDATE users SET principal_name=? WHERE id=?");
            mysqli_stmt_bind_param($stmt2, "si", $principalName, $userId);
            if (mysqli_stmt_execute($stmt2)) {
                $message = "Principal name updated successfully.";
            } else {
                $message = "Failed to update principal name.";
            }
            mysqli_stmt_close($stmt2);
        }
    }
    
    // Handle file upload only
    if (!empty($_FILES['signatureFile']['name'])) {
        $allowedTypes = ['image/png', 'image/jpeg', 'image/jpg'];
        if (in_array($_FILES['signatureFile']['type'], $allowedTypes)) {
            $ext = pathinfo($_FILES['signatureFile']['name'], PATHINFO_EXTENSION);
            $fileName = "uploads/principal_signature_" . $userId . "_" . time() . "." . $ext;

            if (move_uploaded_file($_FILES['signatureFile']['tmp_name'], $fileName)) {
                // Check if principal_signature_path column exists before adding
                $columnCheckSql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'principal_signature_path'";
                $columnCheckResult = mysqli_query($conn, $columnCheckSql);
                if (mysqli_num_rows($columnCheckResult) === 0) {
                    $alterSql = "ALTER TABLE users ADD COLUMN principal_signature_path VARCHAR(255) NULL AFTER principal_name";
                    if (!mysqli_query($conn, $alterSql)) {
                        $message = "Failed to add principal_signature_path column to database: " . mysqli_error($conn);
                    }
                }
                mysqli_free_result($columnCheckResult);

                if (empty($message)) {
                    $stmt2 = mysqli_prepare($conn, "UPDATE users SET principal_signature_path=? WHERE id=?");
                    mysqli_stmt_bind_param($stmt2, "si", $fileName, $userId);
                    if (mysqli_stmt_execute($stmt2)) {
                        $message = "Principal signature uploaded and saved successfully.";
                    } else {
                        $message = "Failed to save principal signature path to database.";
                    }
                    mysqli_stmt_close($stmt2);
                }
            } else {
                $message = "Failed to move uploaded file.";
            }
        } else {
            $message = "No signature file uploaded.";
        }
    } else {
        $message = "Please select a signature file to upload.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Add Principal Signature</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="style_index.css" />
    <style>
        :root {
            --primary-green: #198754;
            --light-green: #d1e7dd;
            --dark-green: #0f5132;
        }
        
        body {
            background-color: #f8f9fa;
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
        
        #signatureCanvas {
            border: 2px solid #dee2e6;
            border-radius: 8px;
            touch-action: none;
            background-color: #f8f9fa;
            width: 100%;
            max-width: 600px;
            height: 200px;
            margin: 0 auto;
            display: block;
            background-color: #f0f8f4;
        }
        
        .canvas-container {
            margin-bottom: 1.5rem;
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 8px;
            border: 2px dashed #c3e6cb;
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

</head>
<body>

<div class="container py-5">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <i class="bi bi-shield-check me-3" style="font-size: 1.75rem;"></i>
                <h2 class="mb-0">Add Principal Signature</h2>
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
            
            <?php if ($currentPrincipalName || $currentSignature): ?>
                <div class="row justify-content-center mb-4">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header bg-success bg-opacity-10">
                                <h5 class="mb-0 text-success"><i class="bi bi-eye me-2"></i>Current Principal Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php if ($currentPrincipalName): ?>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-semibold">Principal Name:</label>
                                            <p class="form-control-plaintext text-primary fw-bold"><?php echo htmlspecialchars($currentPrincipalName); ?></p>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($currentSignature): ?>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label fw-semibold">Principal Signature:</label>
                                            <div class="text-center">
                                                <img src="<?php echo htmlspecialchars($currentSignature); ?>" 
                                                     alt="Current Principal Signature" 
                                                     class="img-fluid rounded border"
                                                     style="max-height: 100px; width: auto;">
                                                <p class="text-muted small mt-2 mb-0">
                                                    <i class="bi bi-clock-history me-1"></i>
                                                    Last updated: <?php echo date("M d, Y h:i A", filemtime($currentSignature)); ?>
                                                </p>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-header bg-success bg-opacity-10">
                            <h5 class="mb-0 text-success"><i class="bi bi-person-badge me-2"></i>Principal Name</h5>
                        </div>
                        <div class="card-body">
                            <form method="post" class="needs-validation" novalidate>
                                <div class="mb-4">
                                    <label for="principal_name" class="form-label fw-semibold">Enter Principal's Name</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="principal_name" name="principal_name" 
                                               value="<?php echo htmlspecialchars($currentPrincipalName); ?>"
                                               placeholder="Enter principal's full name" required>
                                        <label class="input-group-text bg-success text-white" for="principal_name">
                                            <i class="bi bi-person-badge"></i>
                                        </label>
                                    </div>
                                    <div class="form-text text-muted mt-2">Enter the full name of the principal</div>
                                </div>
                                <div class="d-flex justify-content-end">
                                    <button type="submit" class="btn btn-success">
                                        <i class="bi bi-check-lg me-1"></i>Save Principal Name
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header bg-success bg-opacity-10">
                            <h5 class="mb-0 text-success"><i class="bi bi-upload me-2"></i>Upload Principal Signature Image</h5>
                        </div>
                        <div class="card-body">
                            <form method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
                                <div class="mb-4">
                                    <label for="signatureFile" class="form-label fw-semibold">Choose a principal signature image (JPG only)</label>
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
                                        <i class="bi bi-upload me-1"></i>Upload Principal Signature
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


<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Menu toggle functionality
        const menuBtn = document.getElementById('menuBtn');
        const closeBtn = document.getElementById('closeSidebar');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');
        const mainContent = document.getElementById('mainContent');

        function openSidebar() {
            sidebar.classList.add('active');
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeSidebar() {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        }

        if (menuBtn) menuBtn.addEventListener('click', openSidebar);
        if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
        if (overlay) overlay.addEventListener('click', closeSidebar);

        // Close sidebar when clicking outside
        document.addEventListener('click', (e) => {
            if (!sidebar.contains(e.target) && !menuBtn.contains(e.target) && sidebar.classList.contains('active')) {
                closeSidebar();
            }
        });

        // Close sidebar when pressing Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && sidebar.classList.contains('active')) {
                closeSidebar();
            }
        });
    });
</script>

<!-- Move Bootstrap Bundle to head for better loading -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/js/bootstrap.bundle.min.js" 
integrity="sha384-OERcA2EqjJCMA+/3y+gxIOqMEjwtxJY7qPCqsdltbNJuaOe923+mo//f6V8Qbsw3" crossorigin="anonymous"></script>
</body>
</html>
