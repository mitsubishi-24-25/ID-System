<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

require_once "database.php";

// Get advisor information
$userId = $_SESSION['user_id'];
$advisorInfo = null;
$sql = "SELECT full_name, section, signature_path FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($result && mysqli_num_rows($result) > 0) {
        $advisorInfo = mysqli_fetch_assoc($result);
    }
    mysqli_stmt_close($stmt);
}

// Get all students with SFR information for current advisor
$students = [];
$sql = "SELECT s.lrn, s.last_name, s.first_name, s.middle_initial, s.grade, s.section,
               s.parent_guardian, s.permanent_address, s.contact_number, 
               s.transportation_type, s.travel_minutes, u.section as advisor_section
        FROM students s
        JOIN users u ON s.advisor_id = u.id
        WHERE s.advisor_id = ? AND s.is_deleted = 0 
        ORDER BY s.last_name, s.first_name";
$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $students[] = $row;
    }
    mysqli_stmt_close($stmt);
}

// Check if there are no students
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
                There are no student records available to generate the School Form 1 (SFR) report.
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
                    <li>Verify that students have complete information for SFR generation</li>
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

// Include FPDI TCPDF library
require_once __DIR__ . '/fpdi_tcpdf.php';

// Check if SFR template exists
$templatePath = __DIR__ . '/SFR Temp.pdf';
$useTemplate = file_exists($templatePath);

// Create new PDF instance with FPDI capabilities
$pdf = new FPDI_TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetMargins(0, 0, 0);
$pdf->SetAutoPageBreak(false);
$pdf->SetFont('helvetica', '', 10);

// Set up template if available
$templateId = null;
if ($useTemplate) {
    try {
        $templateId = $pdf->setSourceFile($templatePath);
        error_log('Template loaded successfully, ID: ' . $templateId);
    } catch (Exception $e) {
        error_log('Failed to load template: ' . $e->getMessage());
        $useTemplate = false;
    }
} else {
    error_log('Template file not found: ' . $templatePath);
}

// Get the first student's grade and section for the header (all students are from same grade/section)
$headerGrade = '';
$headerSection = '';
if (!empty($students)) {
    $firstStudent = $students[0];
    if (!empty($firstStudent['grade'])) {
        $headerGrade = 'Grade ' . $firstStudent['grade'];
    }
    if (!empty($firstStudent['section'])) {
        $headerSection = $firstStudent['section'];
    } elseif (!empty($firstStudent['advisor_section'])) {
        $headerSection = $firstStudent['advisor_section'];
    }
}

// Process students in groups of 8 per page
$studentsPerPage = 8;
$totalPages = ceil(count($students) / $studentsPerPage);

for ($page = 0; $page < $totalPages; $page++) {
    $pdf->AddPage();
    
    // Add template as background if available
    if ($useTemplate && $templateId !== null) {
        $result = $pdf->useTemplate(0, 0, 0, 297, 210); // Use exact A4 landscape dimensions (297x210mm)
        error_log('Template use result: ' . ($result ? 'success' : 'failed'));
    }
    
    // Set text color
    $pdf->SetTextColor(0, 0, 0);
    
    // Grade and Section - positioned in the header area (same for all students on this page)
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetXY(36, 75.7); // Grade position in header (lowered)
    $pdf->Cell(30, 6, $headerGrade, 0, 0, 'L');
    
    $pdf->SetXY(128 , 75.7); // Section position in header (lowered)
    $pdf->Cell(40, 6, $headerSection, 0, 0, 'L');
    
    // Adviser Name - positioned in the header area
    $advisorName = $advisorInfo ? $advisorInfo['full_name'] : 'Not Available';
    $pdf->SetXY(39, 85.6); // Adviser position in header (lowered)
    $pdf->Cell(60, 6, $advisorName, 0, 0, 'L');
    
    // Table data - positioned in the table rows (8 students per page)
    $pdf->SetFont('helvetica', '', 9);
    
    // Calculate start and end indices for this page
    $startIndex = $page * $studentsPerPage;
    $endIndex = min($startIndex + $studentsPerPage, count($students));
    
    // Fill students 1-8 on this page
    for ($i = $startIndex; $i < $endIndex; $i++) {
        $student = $students[$i];
        $rowIndex = $i - $startIndex; // 0-7 for this page
        
        // Calculate Y position based on row (starting from Y=75, each row 9mm apart)
        $yPosition = 115 + ($rowIndex * 7.5   );
        
        // Define transportation variables before using them
        $transportType = !empty($student['transportation_type']) ? $student['transportation_type'] : '';
        $travelTime = !empty($student['travel_minutes']) ? $student['travel_minutes'] . ' mins' : '';
        
        // Full Name - first column
        $fullName = trim($student['last_name'] . ', ' . $student['first_name'] . ' ' . $student['middle_initial'] . '.');
        
        // Auto-adjust font size to fit in column
        $pdf->SetFont('helvetica', '', 9);
        $maxWidth = 50; // Maximum width for Full Name column
        $fontSize = 9;
        
        // Reduce font size only if text exceeds maximum width
        while ($pdf->GetStringWidth($fullName, '', $fontSize, false) > $maxWidth && $fontSize > 6) {
            $fontSize--;
        }
        $pdf->SetFont('helvetica', '', $fontSize);
        
        $pdf->SetXY(22, $yPosition); // Full Name column position
        $pdf->Cell(60, 8, $fullName, 0, 0, 'L');
        
        // Parent/Guardian - third column
        $pdf->SetFont('helvetica', '', 9);
        $maxWidth = 38; // Maximum width for Parent column
        $fontSize = 9;
        
        // Reduce font size if text is too long
        while ($pdf->GetStringWidth($student['parent_guardian'], '', $fontSize, false) > $maxWidth && $fontSize > 6) {
            $fontSize--;
        }
        $pdf->SetFont('helvetica', '', $fontSize);
        
        $pdf->SetXY(144, $yPosition); // Parent column position
        $pdf->Cell(40, 8, $student['parent_guardian'], 0, 0, 'L');
        
        // Address - second column (same position as Parent/Guardian)
        $pdf->SetFont('helvetica', '', 9);
        $maxWidth = 65; // Maximum width for Address column
        $fontSize = 9;
        
        // Reduce font size if text is too long
        while ($pdf->GetStringWidth($student['permanent_address'], '', $fontSize, false) > $maxWidth && $fontSize > 6) {
            $fontSize--;
        }
        $pdf->SetFont('helvetica', '', $fontSize);
        
        $pdf->SetXY(75, $yPosition + 1); // Address column position (10mm below Parent)
        $pdf->Cell(40, 8, $student['permanent_address'], 0, 0, 'L');
        
        // Contact Number - third column
        $pdf->SetFont('helvetica', '', 9);
        $maxWidth = 33; // Maximum width for Contact column
        $fontSize = 9;
        
        // Reduce font size if text is too long
        while ($pdf->GetStringWidth($student['contact_number'], '', $fontSize, false) > $maxWidth && $fontSize > 6) {
            $fontSize--;
        }
        $pdf->SetFont('helvetica', '', $fontSize);
        
        $pdf->SetXY(192, $yPosition + 1); // Contact column position (10mm below Parent)
        $pdf->Cell(35, 8, $student['contact_number'], 0, 0, 'L');
        
        // Means of Transportation - fourth column
        $pdf->SetFont('helvetica', '', 9);
        $maxWidth = 23; // Maximum width for Transportation column
        $fontSize = 9;
        
        // Reduce font size if text is too long
        while ($pdf->GetStringWidth($transportType, '', $fontSize, false) > $maxWidth && $fontSize > 6) {
            $fontSize--;
        }
        $pdf->SetFont('helvetica', '', $fontSize);
        
        $pdf->SetXY(233, $yPosition); // Transportation column position
        $pdf->Cell(25, 8, $transportType, 0, 0, 'L');
        
        // Time of Travel - fifth column
        $pdf->SetFont('helvetica', '', 9);
        $maxWidth = 18; // Maximum width for Time column
        $fontSize = 9;
        
        // Reduce font size if text is too long
        while ($pdf->GetStringWidth($travelTime, '', $fontSize, false) > $maxWidth && $fontSize > 6) {
            $fontSize--;
        }
        $pdf->SetFont('helvetica', '', $fontSize);
        
        $pdf->SetXY(260, $yPosition); // Time of Travel column position
        $pdf->Cell(20, 8, $travelTime, 0, 0, 'L');
    }
    
    // Add page number
    $pdf->SetXY(180, 280);
    $pdf->SetFont('helvetica', '', 8);
    $pdf->Cell(30, 5, 'Page ' . ($page + 1), 0, 0, 'C');
}

// Output the PDF
$pdf->Output('SFR_Report_' . date('Y-m-d') . '.pdf', 'D');
?>
