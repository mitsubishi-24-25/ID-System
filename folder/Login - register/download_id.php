<?php
// Enable error reporting
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 1);

// Include TCPDF library and database connection
$tcpdf_path = __DIR__ . '/tcpdf/tcpdf.php';
if (!file_exists($tcpdf_path)) {
    die('TCPDF library not found at: ' . $tcpdf_path);
}
require_once($tcpdf_path);
require_once 'database.php';

// Start session to get user_id (advisor's ID)
session_start();
if (!isset($_SESSION['user_id'])) {
    die("Please log in to access this page.");
}
$advisor_id = $_SESSION['user_id'];

// Check if user confirmed download despite missing information
$confirmed_download = isset($_POST['confirm_download']) && $_POST['confirm_download'] == '1';

// Get the section that the advisor registered with
$section = "";
$section_sql = "SELECT section FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $section_sql);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $advisor_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($result)) {
        $section = $row['section'] ?? "N/A";
    }
    mysqli_stmt_close($stmt);
}

// Get all students' information for the current advisor
$students = [];
$sql = "SELECT lrn, last_name, middle_initial, first_name, birthdate, 
               parent_guardian, permanent_address, contact_number, photo 
        FROM students 
        WHERE advisor_id = ?
        ORDER BY last_name, first_name";

$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $advisor_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $students[] = $row;
    }
    mysqli_stmt_close($stmt);
}

// Check for missing student information
$studentsWithMissingInfo = [];
foreach ($students as $student) {
    $missingFields = [];
    
    // Check required fields for ID generation
    if (empty($student['lrn'])) $missingFields[] = 'LRN';
    if (empty($student['last_name'])) $missingFields[] = 'Last Name';
    if (empty($student['first_name'])) $missingFields[] = 'First Name';
    if (empty($student['birthdate'])) $missingFields[] = 'Birthdate';
    if (empty($student['parent_guardian'])) $missingFields[] = 'Parent/Guardian';
    if (empty($student['permanent_address'])) $missingFields[] = 'Address';
    if (empty($student['contact_number'])) $missingFields[] = 'Contact Number';
    if (empty($student['photo']) || !file_exists($student['photo'])) $missingFields[] = 'Photo';
    
    if (!empty($missingFields)) {
        $studentsWithMissingInfo[] = [
            'student' => $student,
            'missing_fields' => $missingFields
        ];
    }
}

// Show confirmation popup if there are students with missing information and user hasn't confirmed
if (!empty($studentsWithMissingInfo) && !$confirmed_download) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Missing Information Confirmation</title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background: linear-gradient(135deg, #1e5128 0%, #4e9f3d 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            
            .confirmation-container {
                background: white;
                border-radius: 20px;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                max-width: 800px;
                width: 100%;
                max-height: 80vh;
                padding: 40px;
                text-align: center;
                animation: slideIn 0.5s ease-out;
                overflow-y: auto;
            }
            
            @keyframes slideIn {
                from {
                    opacity: 0;
                    transform: translateY(-30px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            .warning-icon {
                width: 80px;
                height: 80px;
                margin: 0 auto 30px;
                background: linear-gradient(135deg, #ffa726 0%, #ff6f00 100%);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                animation: pulse 2s infinite;
            }
            
            @keyframes pulse {
                0% {
                    box-shadow: 0 0 0 0 rgba(255, 167, 38, 0.7);
                }
                70% {
                    box-shadow: 0 0 0 20px rgba(255, 167, 38, 0);
                }
                100% {
                    box-shadow: 0 0 0 0 rgba(255, 167, 38, 0);
                }
            }
            
            .warning-icon svg {
                width: 40px;
                height: 40px;
                fill: white;
            }
            
            .confirmation-title {
                color: #2c3e50;
                font-size: 28px;
                font-weight: 700;
                margin-bottom: 15px;
                line-height: 1.3;
            }
            
            .confirmation-message {
                color: #7f8c8d;
                font-size: 16px;
                line-height: 1.6;
                margin-bottom: 30px;
            }
            
            .missing-info-list {
                background: #fff3cd;
                border: 1px solid #ffeaa7;
                border-radius: 12px;
                padding: 20px;
                margin: 20px 0;
                text-align: left;
                max-height: 300px;
                overflow-y: auto;
            }
            
            .missing-student {
                background: white;
                border-left: 4px solid #ffa726;
                padding: 15px;
                margin: 10px 0;
                border-radius: 8px;
            }
            
            .student-name {
                font-weight: 600;
                color: #2c3e50;
                margin-bottom: 5px;
                font-size: 14px;
            }
            
            .student-lrn {
                color: #666;
                font-size: 12px;
                margin-bottom: 8px;
            }
            
            .missing-fields {
                display: flex;
                flex-wrap: wrap;
                gap: 5px;
            }
            
            .missing-field {
                background: #ff6f00;
                color: white;
                padding: 3px 8px;
                border-radius: 12px;
                font-size: 11px;
                font-weight: 500;
            }
            
            .action-buttons {
                display: flex;
                gap: 15px;
                justify-content: center;
                flex-wrap: wrap;
                margin-top: 30px;
            }
            
            .btn {
                padding: 12px 24px;
                border: none;
                border-radius: 8px;
                font-size: 14px;
                font-weight: 600;
                text-decoration: none;
                cursor: pointer;
                transition: all 0.3s ease;
                display: inline-flex;
                align-items: center;
                gap: 8px;
            }
            
            .btn-warning {
                background: linear-gradient(135deg, #ffa726 0%, #ff6f00 100%);
                color: white;
            }
            
            .btn-warning:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 20px rgba(255, 167, 38, 0.4);
            }
            
            .btn-secondary {
                background: #e9ecef;
                color: #495057;
            }
            
            .btn-secondary:hover {
                background: #dee2e6;
                transform: translateY(-2px);
            }
            
            .summary-stats {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 15px;
                margin: 20px 0;
            }
            
            .stat-card {
                background: #f8f9fa;
                padding: 15px;
                border-radius: 8px;
                text-align: center;
            }
            
            .stat-number {
                font-size: 24px;
                font-weight: 700;
                color: #2c3e50;
            }
            
            .stat-label {
                font-size: 12px;
                color: #666;
                margin-top: 5px;
            }
        </style>
    </head>
    <body>
        <div class="confirmation-container">
            <div class="warning-icon">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/>
                </svg>
            </div>
            
            <h1 class="confirmation-title">Missing Information Detected</h1>
            
            <p class="confirmation-message">
                Are you sure you want to download the IDs? Some IDs are still missing components that may affect the quality and completeness of the generated cards.
            </p>
            
            <div class="summary-stats">
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($students); ?></div>
                    <div class="stat-label">Total Students</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($studentsWithMissingInfo); ?></div>
                    <div class="stat-label">Students with Missing Info</div>
                </div>
            </div>
            
            <div class="missing-info-list">
                <?php foreach ($studentsWithMissingInfo as $item): ?>
                    <div class="missing-student">
                        <div class="student-name">
                            <?php echo htmlspecialchars($item['student']['last_name'] . ', ' . $item['student']['first_name'] . ' ' . $item['student']['middle_initial'] . '.'); ?>
                        </div>
                        <div class="student-lrn">LRN: <?php echo htmlspecialchars($item['student']['lrn'] ?: 'Not Set'); ?></div>
                        <div class="missing-fields">
                            <?php foreach ($item['missing_fields'] as $field): ?>
                                <span class="missing-field"><?php echo htmlspecialchars($field); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="action-buttons">
                <form method="post" style="display: inline;">
                    <input type="hidden" name="confirm_download" value="1">
                    <button type="submit" class="btn btn-warning">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="17 8 12 3 7 8"></polyline>
                            <line x1="12" y1="3" x2="12" y2="15"></line>
                        </svg>
                        Download Anyway
                    </button>
                </form>
                <a href="javascript:history.back()" class="btn btn-secondary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 12H5M12 19l-7-7 7-7"/>
                    </svg>
                    Go Back
                </a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

if (empty($students)) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>No Students Found</title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background: linear-gradient(135deg, #1e5128 0%, #4e9f3d 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            
            .error-container {
                background: white;
                border-radius: 20px;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                max-width: 600px;
                width: 100%;
                padding: 40px;
                text-align: center;
                animation: slideIn 0.5s ease-out;
            }
            
            @keyframes slideIn {
                from {
                    opacity: 0;
                    transform: translateY(-30px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            .error-icon {
                width: 80px;
                height: 80px;
                margin: 0 auto 30px;
                background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                animation: pulse 2s infinite;
            }
            
            @keyframes pulse {
                0% {
                    box-shadow: 0 0 0 0 rgba(255, 107, 107, 0.7);
                }
                70% {
                    box-shadow: 0 0 0 20px rgba(255, 107, 107, 0);
                }
                100% {
                    box-shadow: 0 0 0 0 rgba(255, 107, 107, 0);
                }
            }
            
            .error-icon svg {
                width: 40px;
                height: 40px;
                fill: white;
            }
            
            .error-title {
                color: #2c3e50;
                font-size: 28px;
                font-weight: 700;
                margin-bottom: 15px;
                line-height: 1.3;
            }
            
            .error-message {
                color: #7f8c8d;
                font-size: 16px;
                line-height: 1.6;
                margin-bottom: 30px;
            }
            
            .error-details {
                background: #f8f9fa;
                border-left: 4px solid #ff6b6b;
                padding: 20px;
                margin: 20px 0;
                border-radius: 8px;
                text-align: left;
            }
            
            .error-details h3 {
                color: #2c3e50;
                font-size: 18px;
                margin-bottom: 10px;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            
            .error-details ul {
                list-style: none;
                padding: 0;
            }
            
            .error-details li {
                color: #555;
                margin: 8px 0;
                padding-left: 20px;
                position: relative;
            }
            
            .error-details li:before {
                content: "→";
                position: absolute;
                left: 0;
                color: #ff6b6b;
                font-weight: bold;
            }
            
            .action-buttons {
                display: flex;
                gap: 15px;
                justify-content: center;
                flex-wrap: wrap;
                margin-top: 30px;
            }
            
            .btn {
                padding: 12px 24px;
                border: none;
                border-radius: 8px;
                font-size: 14px;
                font-weight: 600;
                text-decoration: none;
                cursor: pointer;
                transition: all 0.3s ease;
                display: inline-flex;
                align-items: center;
                gap: 8px;
            }
            
            .btn-primary {
                background: linear-gradient(135deg, #1e5128 0%, #4e9f3d 100%);
                color: white;
            }
            
            .btn-primary:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 20px rgba(30, 81, 40, 0.4);
            }
            
            .btn-secondary {
                background: #e9ecef;
                color: #495057;
            }
            
            .btn-secondary:hover {
                background: #dee2e6;
                transform: translateY(-2px);
            }
        </style>
    </head>
    <body>
        <div class="error-container">
            <div class="error-icon">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
                </svg>
            </div>
            
            <h1 class="error-title">No Students Found</h1>
            
            <p class="error-message">
                There are no student records available to generate ID cards.
            </p>
            
            <div class="error-details">
                <h3>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="16" x2="12" y2="12"></line>
                        <line x1="12" y1="8" x2="12.01" y2="8"></line>
                    </svg>
                    What you need to do:
                </h3>
                <ul>
                    <li>Add students to your account using the student registration form</li>
                    <li>Ensure students are not marked as deleted</li>
                    <li>Check that you are logged in with the correct advisor account</li>
                    <li>Verify that students have complete information for ID generation</li>
                </ul>
            </div>
            
            <div class="action-buttons">
                <a href="index.php" class="btn btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                        <polyline points="9 22 9 12 15 12 15 22"></polyline>
                    </svg>
                    Go to Dashboard
                </a>
                <a href="javascript:history.back()" class="btn btn-secondary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 12H5M12 19l-7-7 7-7"/>
                    </svg>
                    Go Back
                </a>
            </div>
        </div>
        
        <script>
            // Auto-refresh after 30 seconds to check if students have been added
            setTimeout(() => {
                if (confirm('Would you like to check if students have been added?')) {
                    window.location.reload();
                }
            }, 30000);
        </script>
    </body>
    </html>
    <?php
    exit;
}

// Define ID card dimensions in mm (portrait orientation)
$idWidth = 54;     // Width in mm (shorter side)
$idHeight = 85.6;  // Height in mm (longer side)

// A4 dimensions in landscape (297mm x 210mm)
$a4Width = 297;
$a4Height = 210;

// Margins and spacing
$margin = 10;     // Margin around the page
$spacing = 10;    // Space between card pairs

// Create new PDF instance with A4 landscape
$pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetMargins($margin, $margin, $margin);
$pdf->SetAutoPageBreak(false);
$pdf->AddPage();

// Set default font subsetting mode
$pdf->setFontSubsetting(false); // Disable subsetting to use core fonts

// Add a core font
$fontname = TCPDF_FONTS::addTTFfont('tcpdf/fonts/helvetica.ttf', 'TrueTypeUnicode', '', 32);
$pdf->SetFont('times', '', 10); // Use built-in times new roman font

// Get the uploaded ID template paths from session
$frontImagePath = '';
$backImagePath = '';

// Check for uploaded front ID template
if (isset($_SESSION['front_id_template']) && file_exists($_SESSION['front_id_template'])) {
    $frontImagePath = $_SESSION['front_id_template'];
} else {
    // Fallback to default front image if no template uploaded
    $frontImagePath = __DIR__ . '/FrontID.jpg';
}

// Check for uploaded back ID template
if (isset($_SESSION['back_id_template']) && file_exists($_SESSION['back_id_template'])) {
    $backImagePath = $_SESSION['back_id_template'];
} else {
    // Fallback to default back image if no template uploaded
    $backImagePath = __DIR__ . '/BackID.jpg';
}

// Check if template images exist
if (!file_exists($frontImagePath) || !file_exists($backImagePath)) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>ID Card Templates Not Found</title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background: linear-gradient(135deg, #1e5128 0%, #4e9f3d 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            
            .error-container {
                background: white;
                border-radius: 20px;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                max-width: 600px;
                width: 100%;
                padding: 40px;
                text-align: center;
                animation: slideIn 0.5s ease-out;
            }
            
            @keyframes slideIn {
                from {
                    opacity: 0;
                    transform: translateY(-30px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            .error-icon {
                width: 80px;
                height: 80px;
                margin: 0 auto 30px;
                background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                animation: pulse 2s infinite;
            }
            
            @keyframes pulse {
                0% {
                    box-shadow: 0 0 0 0 rgba(255, 107, 107, 0.7);
                }
                70% {
                    box-shadow: 0 0 0 20px rgba(255, 107, 107, 0);
                }
                100% {
                    box-shadow: 0 0 0 0 rgba(255, 107, 107, 0);
                }
            }
            
            .error-icon svg {
                width: 40px;
                height: 40px;
                fill: white;
            }
            
            .error-title {
                color: #2c3e50;
                font-size: 28px;
                font-weight: 700;
                margin-bottom: 15px;
                line-height: 1.3;
            }
            
            .error-message {
                color: #7f8c8d;
                font-size: 16px;
                line-height: 1.6;
                margin-bottom: 30px;
            }
            
            .error-details {
                background: #f8f9fa;
                border-left: 4px solid #ff6b6b;
                padding: 20px;
                margin: 20px 0;
                border-radius: 8px;
                text-align: left;
            }
            
            .error-details h3 {
                color: #2c3e50;
                font-size: 18px;
                margin-bottom: 10px;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            
            .error-details ul {
                list-style: none;
                padding: 0;
            }
            
            .error-details li {
                color: #555;
                margin: 8px 0;
                padding-left: 20px;
                position: relative;
            }
            
            .error-details li:before {
                content: "→";
                position: absolute;
                left: 0;
                color: #ff6b6b;
                font-weight: bold;
            }
            
            .action-buttons {
                display: flex;
                gap: 15px;
                justify-content: center;
                flex-wrap: wrap;
                margin-top: 30px;
            }
            
            .btn {
                padding: 12px 24px;
                border: none;
                border-radius: 8px;
                font-size: 14px;
                font-weight: 600;
                text-decoration: none;
                cursor: pointer;
                transition: all 0.3s ease;
                display: inline-flex;
                align-items: center;
                gap: 8px;
            }
            
            .btn-primary {
                background: linear-gradient(135deg, #1e5128 0%, #4e9f3d 100%);
                color: white;
            }
            
            .btn-primary:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 20px rgba(30, 81, 40, 0.4);
            }
            
            .btn-secondary {
                background: #e9ecef;
                color: #495057;
            }
            
            .btn-secondary:hover {
                background: #dee2e6;
                transform: translateY(-2px);
            }
            
            .file-status {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 15px;
                margin: 20px 0;
            }
            
            .file-check {
                padding: 15px;
                border-radius: 8px;
                border: 2px solid #e9ecef;
                text-align: center;
            }
            
            .file-check.missing {
                border-color: #ff6b6b;
                background: #ffe5e5;
            }
            
            .file-check.found {
                border-color: #51cf66;
                background: #e5ffe5;
            }
            
            .file-check-icon {
                font-size: 24px;
                margin-bottom: 5px;
            }
            
            .file-check-text {
                font-size: 12px;
                color: #666;
                font-weight: 600;
            }
        </style>
    </head>
    <body>
        <div class="error-container">
            <div class="error-icon">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
                </svg>
            </div>
            
            <h1 class="error-title">ID Card Templates Not Found</h1>
            
            <p class="error-message">
                The system cannot locate the required ID card template files to generate student IDs.
            </p>
            
            <div class="file-status">
                <div class="file-check <?php echo file_exists($frontImagePath) ? 'found' : 'missing'; ?>">
                    <div class="file-check-icon">
                        <?php echo file_exists($frontImagePath) ? '✅' : '❌'; ?>
                    </div>
                    <div class="file-check-text">Front Template</div>
                </div>
                <div class="file-check <?php echo file_exists($backImagePath) ? 'found' : 'missing'; ?>">
                    <div class="file-check-icon">
                        <?php echo file_exists($backImagePath) ? '✅' : '❌'; ?>
                    </div>
                    <div class="file-check-text">Back Template</div>
                </div>
            </div>
            
            <div class="error-details">
                <h3>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="16" x2="12" y2="12"></line>
                        <line x1="12" y1="8" x2="12.01" y2="8"></line>
                    </svg>
                    What you need to do:
                </h3>
                <ul>
                    <li>Upload ID card templates using the upload interface</li>
                    <li>Ensure default FrontID.jpg and BackID.jpg files exist in the project directory</li>
                    <li>Check that template files have proper read permissions</li>
                    <li>Verify the uploaded templates are in the correct location</li>
                </ul>
            </div>
            
            <div class="action-buttons">
                <a href="upload_id_template.php" class="btn btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="17 8 12 3 7 8"></polyline>
                        <line x1="12" y1="3" x2="12" y2="15"></line>
                    </svg>
                    Upload Templates
                </a>
                <a href="javascript:history.back()" class="btn btn-secondary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 12H5M12 19l-7-7 7-7"/>
                    </svg>
                    Go Back
                </a>
            </div>
        </div>
        
        <script>
            // Auto-refresh after 30 seconds to check if templates are uploaded
            setTimeout(() => {
                if (confirm('Would you like to check if the templates have been uploaded?')) {
                    window.location.reload();
                }
            }, 30000);
        </script>
    </body>
    </html>
    <?php
    exit;
}

// Calculate positions for ID cards (4 front top, 4 back bottom per page)
$cardWidth = $idWidth;
$cardHeight = $idHeight;
$cardsPerRow = 2; // 2 IDs per row
$totalStudents = count($students);
$currentPage = 1;
$studentsPerPage = 4; // 4 students per page (4 front + 4 back)

// Calculate spacing
$horizontalSpacing = 10; // Space between cards horizontally
$verticalSpacing = 5;    // Space between front and back sections
$availableHeight = $a4Height - (2 * $margin);
$sectionHeight = $cardHeight * 2 + $verticalSpacing; // Height for front or back section

// Calculate positions for front IDs (top half - all in one row)
$frontPositions = [
    ['x' => $margin, 'y' => $margin],
    ['x' => $margin + $cardWidth + $horizontalSpacing, 'y' => $margin],
    ['x' => $margin + ($cardWidth * 2) + ($horizontalSpacing * 2), 'y' => $margin],
    ['x' => $margin + ($cardWidth * 3) + ($horizontalSpacing * 3), 'y' => $margin]
];

// Calculate positions for back IDs (bottom half - all in one row)
$backPositions = [
    ['x' => $margin, 'y' => $margin + $cardHeight + $verticalSpacing],
    ['x' => $margin + $cardWidth + $horizontalSpacing, 'y' => $margin + $cardHeight + $verticalSpacing],
    ['x' => $margin + ($cardWidth * 2) + ($horizontalSpacing * 2), 'y' => $margin + $cardHeight + $verticalSpacing],
    ['x' => $margin + ($cardWidth * 3) + ($horizontalSpacing * 3), 'y' => $margin + $cardHeight + $verticalSpacing]
];

// Process students in batches of 4 per page (4 front + 4 back)
for ($pageStart = 0; $pageStart < $totalStudents; $pageStart += $studentsPerPage) {
    // Add new page (except first page)
    if ($pageStart > 0) {
        $pdf->AddPage('L');
        $currentPage++;
    }
    
    // Get students for current page
    $pageStudents = array_slice($students, $pageStart, $studentsPerPage);
    
    // First, draw all front IDs in 2x2 grid at the top
    foreach ($pageStudents as $index => $student) {
        $full_name = $student['last_name'] . ", " . $student['first_name'] . " " . $student['middle_initial'] . ".";
        
        // Get pre-calculated position for this front ID
        $pos = $frontPositions[$index];
        $x = $pos['x'];
        $y = $pos['y'];
        
        // ===== FRONT OF ID =====
        $pdf->Image($frontImagePath, $x, $y, $cardWidth, $cardHeight);
        
        // Add student photo in the center box with rounded corners
        if (!empty($student['photo']) && file_exists($student['photo'])) {
            $photoSize = 29.5;
            $photoX = $x + ($cardWidth / 2) - ($photoSize / 2);
            $photoY = $y + 24.5;
            $radius = 3;
            
            // Save current graphics state
            $pdf->StartTransform();
            
            // Create clipping path with rounded rectangle
            $pdf->RoundedRect(
                $photoX, 
                $photoY, 
                $photoSize, 
                $photoSize, 
                $radius, 
                '1111', // Round all corners
                'CNZ'   // Clipping mode: C=close, N=no fill, Z=stroke
            );
            
            // Add the image
            $pdf->Image(
                $student['photo'], 
                $photoX, 
                $photoY, 
                $photoSize, 
                $photoSize, 
                '', '', '', false, 300, '', false, false, 0, false, false, false, false, array()
            );
            
            // Restore graphics state
            $pdf->StopTransform();
        }
        
        // Set font for student info
        $pdf->SetFont('times', 'B', 10);
        $pdf->SetTextColor(0, 0, 0);

        // Add name in two lines: last name on top, first name + MI below
        $pdf->SetTextColor(128, 0, 128); // Purple color
        $pdf->SetFont('times', 'B', 12);
        
        // First line: Last Name
        $pdf->SetXY($x, $y + 55);
        $pdf->Cell($cardWidth, 5, strtoupper($student['last_name']), 0, 1, 'C');
        
        // Second line: First Name + Middle Initial
        $pdf->SetXY($x, $y + 62);
        $pdf->Cell($cardWidth, 5, $student['first_name'] . ' ' . $student['middle_initial'] . '.', 0, 1, 'C');
        
        $pdf->SetFont('times', 'B', 10); // Reset to original font size

        // Add section in white with smaller font
        $pdf->SetTextColor(255, 255, 255); // White color
        $pdf->SetFont('times', 'B', 7); // Set smaller font size
        $pdf->SetXY($x + 8.5, $y + 67.8);
        $pdf->Cell($cardWidth, 5, $section, 0, 1, 'C');
        $pdf->SetTextColor(0, 0, 0); // Reset to black for other text
        $pdf->SetFont('times', 'B', 10); // Reset to original font size

        // Add LRN (below section) in normal weight
        $pdf->SetFont('times', '', 10); // Normal weight for LRN
        $pdf->SetXY($x + 4, $y + 74.5);
        $pdf->Cell($cardWidth, 5, $student['lrn'], 0, 1, 'C');
        $pdf->SetFont('times', 'B', 10); // Reset to bold for other text
    }
    
    // Then, draw all back IDs in 2x2 grid at the bottom
    foreach ($pageStudents as $index => $student) {
        // Get pre-calculated position for this back ID
        $pos = $backPositions[$index];
        $backX = $pos['x'];
        $backY = $pos['y'];
        
        // ===== BACK OF ID =====
        $pdf->Image($backImagePath, $backX, $backY, $cardWidth, $cardHeight);
        
        // Add back ID details
        $pdf->SetFont('times', '', 9);
        
        // Birthdate
        $pdf->SetXY($backX + 18, $backY + 17);
        $pdf->Cell(0, 5, date('m/d/Y', strtotime($student['birthdate'])));
        
        // Get advisor's name (if not already retrieved)
        if (!isset($advisor_name)) {
            $advisor_name = "";
            $advisor_sql = "SELECT full_name FROM users WHERE id = ?";
            $stmt = mysqli_prepare($conn, $advisor_sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "i", $advisor_id);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                if ($row = mysqli_fetch_assoc($result)) {
                    $advisor_name = $row['full_name'];
                }
                mysqli_stmt_close($stmt);
            }
        }
        
        // Get advisor's signature path if not already retrieved
        if (!isset($signature_path)) {
            $signature_path = '';
            $signature_sql = "SELECT signature_path FROM users WHERE id = ?";
            $stmt = mysqli_prepare($conn, $signature_sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "i", $advisor_id);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                if ($row = mysqli_fetch_assoc($result)) {
                    $signature_path = $row['signature_path'] ?? '';
                }
                mysqli_stmt_close($stmt);
            }
        }
        
        // Get principal's name and signature path if not already retrieved
        if (!isset($principal_name) || !isset($principal_signature_path)) {
            $principal_name = "";
            $principal_signature_path = "";
            $principal_sql = "SELECT principal_name, principal_signature_path FROM users WHERE id = ?";
            $stmt = mysqli_prepare($conn, $principal_sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "i", $advisor_id);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                if ($row = mysqli_fetch_assoc($result)) {
                    $principal_name = $row['principal_name'] ?? '';
                    $principal_signature_path = $row['principal_signature_path'] ?? '';
                }
                mysqli_stmt_close($stmt);
            }
        }
        
        // Set position for advisor's name
        $pdf->SetFont('times', '', 9);
        $name_width = $pdf->GetStringWidth($advisor_name);
        $name_x = $backX + ($cardWidth / 2);  // Center of the card
        $name_y = $backY + 28.5;
        
        // Add advisor's signature if exists
        if (!empty($signature_path) && file_exists($signature_path)) {
            $signature_width = 30;  // Fixed width for signature
            $signature_height = 10;
            $signature_x = $name_x - ($signature_width / 2);  // Center the signature
            $signature_y = $name_y - 6;  // Position above the name
            
            $pdf->Image(
                $signature_path, 
                $signature_x, 
                $signature_y, 
                $signature_width, 
                $signature_height, 
                '', '', 'T', false, 300, '', false, false, 0, false, false, false, false, array()
            );
        }
        
        // Draw advisor's name - centered
        $pdf->SetXY($backX, $name_y);
        $pdf->Cell($cardWidth, 5, $advisor_name, 0, 0, 'C');
        
        // Add principal information below advisor
        if (!empty($principal_name)) {
            // Set position for principal's name (below advisor)
            $principal_name_y = $name_y + 13; // Position below advisor name
            
            // Add principal's signature if exists
            if (!empty($principal_signature_path) && file_exists($principal_signature_path)) {
                $principal_signature_width = 25;  // Slightly smaller than advisor signature
                $principal_signature_height = 8;
                $principal_signature_x = $name_x - ($principal_signature_width / 2);  // Center the signature
                $principal_signature_y = $principal_name_y - 5;  // Position above the principal name
                
                $pdf->Image(
                    $principal_signature_path, 
                    $principal_signature_x, 
                    $principal_signature_y, 
                    $principal_signature_width, 
                    $principal_signature_height, 
                    '', '', 'T', false, 300, '', false, false, 0, false, false, false, false, array()
                );
            }
            
            // Draw principal's name - centered
            $pdf->SetFont('times', '', 8); // Slightly smaller font for principal
            $pdf->SetXY($backX, $principal_name_y);
            $pdf->Cell($cardWidth, 4, $principal_name, 0, 0, 'C');
            $pdf->SetFont('times', '', 9); // Reset to original font size
        }
        
        // Parent/Guardian with same wrapping as address
        $pdf->SetFont('times', '', 8);
        $parentY = $backY + 56;  // Moved up by 2mm (from 59 to 57)
        $pdf->SetXY($backX + 1, $parentY);
        $parent_guardian = wordwrap($student['parent_guardian'], 16, "\n", true);
        $pdf->MultiCell($cardWidth - 10, 3, $parent_guardian, 0, 'L', false, 1, $backX + 1, $parentY, true, 0, false, true, 10, 'T');
        $pdf->SetFont('times', '', 9);
        
        // Address
        $pdf->SetFont('times', '', 8);
        $pdf->SetXY($backX + 1, $backY + 66);
        $address = wordwrap($student['permanent_address'], 20, "\n", true);
        $pdf->MultiCell($cardWidth - 10, 3, $address, 0, 'L', false, 1, $backX + 1, $backY + 66, true, 0, false, true, 10, 'T');
        $pdf->SetFont('times', '', 9);
        
        // Contact number
        $pdf->SetXY($backX + 8, $backY + 76);
        $pdf->Cell(0, 5, $student['contact_number']);
    }
}

// Output the PDF as download
$filename = 'Student_IDs_Section_' . preg_replace('/[^A-Za-z0-9]/', '_', $section) . '.pdf';

// Close and output PDF document
$pdf->Output($filename, 'D');

exit;
?>