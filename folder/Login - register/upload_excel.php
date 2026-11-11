 <?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

require_once "database.php";

$errors = [];
$success_message = "";
$userId = $_SESSION['user_id'];


if (isset($_POST['upload'])) {
    if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "Please select a valid file to upload.";
    } else {
        $file = $_FILES['excel_file'];
        $fileName = $file['name'];
        $fileTmpName = $file['tmp_name'];
        $fileSize = $file['size'];
        $fileError = $file['error'];

        // Check file extension
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if ($fileExt !== 'csv' && $fileExt !== 'xlsx') {
            $errors[] = "Only CSV or XLSX files are allowed.";
        } elseif ($fileSize > 5000000) { // 5MB limit
            $errors[] = "File size must be less than 5MB.";
        } else {
            // Process the file
            $userId = $_SESSION['user_id'];
            $inserted = 0;
            $skipped = 0;

            if ($fileExt === 'csv') {
                // Process CSV
                if (($handle = fopen($fileTmpName, "r")) !== FALSE) {
                    // Skip header row
                    fgetcsv($handle, 1000, ",");

                    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                        if (count($data) >= 8) {
                            $lrn = trim($data[0]);
                            $lastName = trim($data[1]);
                            $middleInitial = trim($data[2]);
                            $firstName = trim($data[3]);
                            $birthdate = trim($data[4]);
                            $parentGuardian = trim($data[5]);
                            $permanentAddress = trim($data[6]);
                            $contactNumber = trim($data[7]);

                            // Format birthdate to YYYY-MM-DD for MySQL
                            if (!empty($birthdate)) {
                                // Try different date formats
                                $dateFormats = [
                                    'm/d/Y',    // 12/31/2023
                                    'm-d-Y',    // 12-31-2023
                                    'm.d.Y',    // 12.31.2023
                                    'n/j/Y',    // 12/31/2023 (without leading zeros)
                                    'Y-m-d',    // 2023-12-31 (ISO format)
                                ];

                                $formattedDate = false;
                                foreach ($dateFormats as $format) {
                                    $date = DateTime::createFromFormat($format, $birthdate);
                                    if ($date !== false) {
                                        $formattedDate = $date->format('Y-m-d');
                                        break;
                                    }
                                }

                                if ($formattedDate === false) {
                                    // If we can't parse the date, skip this record
                                    $skipped++;
                                    continue;
                                }
                                $birthdate = $formattedDate;
                            }

                            // Validate required fields
                            if (!empty($lrn) && !empty($lastName) && !empty($firstName) && !empty($birthdate) && !empty($parentGuardian) && !empty($permanentAddress) && !empty($contactNumber)) {
                                // Check if LRN already exists for this advisor
                                $checkSql = "SELECT id FROM students WHERE lrn = ? AND advisor_id = ?";
                                $checkStmt = mysqli_prepare($conn, $checkSql);
                                $advisor_id = $_SESSION['user_id'];
                                mysqli_stmt_bind_param($checkStmt, "si", $lrn, $advisor_id);
                                mysqli_stmt_execute($checkStmt);
                                mysqli_stmt_store_result($checkStmt);

                                if (mysqli_stmt_num_rows($checkStmt) == 0) {
                                    // Insert new student with advisor_id
                                    $sql = "INSERT INTO students (lrn, last_name, middle_initial, first_name, birthdate, parent_guardian, permanent_address, contact_number, user_id, advisor_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                                    $stmt = mysqli_prepare($conn, $sql);
                                    mysqli_stmt_bind_param($stmt, "ssssssssii", $lrn, $lastName, $middleInitial, $firstName, $birthdate, $parentGuardian, $permanentAddress, $contactNumber, $userId, $advisor_id);
                                    if (mysqli_stmt_execute($stmt)) {
                                        $inserted++;
                                    }
                                    mysqli_stmt_close($stmt);
                                } else {
                                    $skipped++;
                                }
                                mysqli_stmt_close($checkStmt);
                            }
                        }
                    }
                    fclose($handle);
                } else {
                    $errors[] = "Failed to open the uploaded file.";
                }
            } else {
                // For XLSX, we'd need PhpSpreadsheet, but since we don't have it, skip for now
                $errors[] = "XLSX processing not implemented. Please upload a CSV file.";
            }

            if (empty($errors)) {
                $success_message = "Upload completed. $inserted students added, $skipped duplicates skipped.";
            }
        }
    }
}
?>

<?php include 'includes/header.php'; ?>

<style>
    :root {
        --primary-green: #28a745;
        --light-green: #d4edda;
        --dark-green: #218838;
        --success-green: #155724;
        --light-bg: #f8f9fa;
    }
    
    body {
        background-color: var(--light-bg);
    }
    
    .card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }
    
    .card-header {
        background-color: var(--primary-green);
        color: white;
        padding: 1.25rem;
    }
    
    .card-header h2 {
        margin: 0;
        font-weight: 600;
    }
    
    .card-body {
        padding: 2rem;
    }
    
    .btn-primary {
        background-color: var(--primary-green);
        border-color: var(--primary-green);
    }
    
    .btn-primary:hover, .btn-primary:focus {
        background-color: var(--dark-green);
        border-color: var(--dark-green);
    }
    
    .alert-info {
        background-color: #e8f5e9;
        border-color: #c8e6c9;
        color: #1b5e20;
    }
    
    .form-control:focus {
        border-color: var(--primary-green);
        box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
    }
    
    .custom-file-button {
        background-color: var(--light-green);
        color: var(--success-green);
        border: 1px dashed var(--primary-green);
        padding: 1.5rem;
        text-align: center;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .custom-file-button:hover {
        background-color: #e2f3e4;
        border-color: var(--dark-green);
    }
    
    .file-input {
        display: none;
    }
    
    .file-name {
        margin-top: 0.5rem;
        color: #6c757d;
        font-size: 0.875rem;
    }
    
    .info-icon {
        color: var(--primary-green);
        margin-right: 0.5rem;
    }
</style>

<div class="container py-5">
    <div class="card">
        <div class="card-header d-flex align-items-center">
            <i class="bi bi-upload me-2" style="font-size: 1.5rem;"></i>
            <h2 class="mb-0">Upload Student Data</h2>
        </div>
        <div class="card-body">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <?php foreach ($errors as $error): ?>
                        <div><?php echo htmlspecialchars($error); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success d-flex align-items-center">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <div><?php echo htmlspecialchars($success_message); ?></div>
                </div>
            <?php endif; ?>

            <div class="alert alert-info mb-4">
                <h5 class="d-flex align-items-center">
                    <i class="bi bi-info-circle-fill me-2"></i>
                    Important Notes:
                </h5>
                <ul class="mb-0 ps-4">
                    <li><i class="bi bi-check2-circle info-icon"></i>File format: LRN, Last Name, Middle Initial, First Name, Birthdate, Parent/Guardian, Permanent Address, Contact Number</li>
                    <li><i class="bi bi-calendar-check info-icon"></i>Birthdate formats: MM/DD/YYYY, MM-DD-YYYY, MM.DD.YYYY, or YYYY-MM-DD</li>
                    <li><i class="bi bi-asterisk info-icon"></i>All fields except Middle Initial are required</li>
                    <li><i class="bi bi-file-earmark-arrow-down info-icon"></i>Download the <a href="download_excel_template.php" class="alert-link fw-bold">Excel template</a> for the correct format</li>
                </ul>
            </div>
            
            <form action="upload_excel.php" method="post" enctype="multipart/form-data" class="mb-4">
                <div class="mb-4">
                    <label for="excel_file" class="form-label fw-semibold mb-3 d-block">Select Excel/CSV File</label>
                    
                    <label for="excel_file" class="custom-file-button w-100 d-flex flex-column align-items-center justify-content-center" id="fileLabel">
                        <i class="bi bi-cloud-arrow-up" style="font-size: 2.5rem; margin-bottom: 1rem;"></i>
                        <span class="mb-2">Click to select file or drag and drop</span>
                        <small class="text-muted">Supported formats: .xlsx, .csv (Max 5MB)</small>
                        <span id="fileName" class="file-name"></span>
                    </label>
                    <input type="file" name="excel_file" id="excel_file" class="file-input" accept=".xlsx,.csv" required>
                </div>
                
                <div class="d-flex gap-3 mt-4 pt-3 border-top">
                    <button type="submit" name="upload" class="btn btn-primary px-4 py-2">
                        <i class="bi bi-upload me-2"></i>Upload File
                    </button>
                    <a href="index.php" class="btn btn-outline-secondary px-4 py-2">
                        <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>
            </form>
            
            <script>
                // Update file name display when a file is selected
                document.getElementById('excel_file').addEventListener('change', function(e) {
                    const fileName = e.target.files[0] ? e.target.files[0].name : 'No file selected';
                    document.getElementById('fileName').textContent = fileName;
                    
                    // Add visual feedback when file is selected
                    const fileLabel = document.getElementById('fileLabel');
                    if (e.target.files[0]) {
                        fileLabel.style.borderStyle = 'solid';
                        fileLabel.style.borderColor = '#28a745';
                    } else {
                        fileLabel.style.borderStyle = 'dashed';
                        fileLabel.style.borderColor = '#28a745';
                    }
                });
                
                // Handle drag and drop functionality
                const fileLabel = document.getElementById('fileLabel');
                const fileInput = document.getElementById('excel_file');
                
                ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                    fileLabel.addEventListener(eventName, preventDefaults, false);
                });
                
                function preventDefaults(e) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                
                ['dragenter', 'dragover'].forEach(eventName => {
                    fileLabel.addEventListener(eventName, highlight, false);
                });
                
                ['dragleave', 'drop'].forEach(eventName => {
                    fileLabel.addEventListener(eventName, unhighlight, false);
                });
                
                function highlight() {
                    fileLabel.style.backgroundColor = '#e8f5e9';
                    fileLabel.style.borderColor = '#28a745';
                    fileLabel.style.borderStyle = 'dashed';
                }
                
                function unhighlight() {
                    fileLabel.style.backgroundColor = '';
                    fileLabel.style.borderColor = '#28a745';
                    fileLabel.style.borderStyle = fileInput.files.length ? 'solid' : 'dashed';
                }
                
                fileLabel.addEventListener('drop', handleDrop, false);
                
                function handleDrop(e) {
                    const dt = e.dataTransfer;
                    const files = dt.files;
                    if (files.length) {
                        fileInput.files = files;
                        document.getElementById('fileName').textContent = files[0].name;
                    }
                }
            </script>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>