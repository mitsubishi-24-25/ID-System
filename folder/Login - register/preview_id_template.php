<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

require_once "database.php";

// Get template type from URL
$templateType = $_GET['template'] ?? 'front';

// Get template positions from database or use defaults
function getTemplatePositions($conn, $userId, $templateType) {
    $positions = [];
    
    $sql = "SELECT field_name, x_position, y_position, font_family, font_size, font_weight, 
                   text_color, text_align, max_width, rotation
            FROM id_template_positions 
            WHERE user_id = ? AND template_type = ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "is", $userId, $templateType);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        while ($row = mysqli_fetch_assoc($result)) {
            $positions[$row['field_name']] = [
                'x' => floatval($row['x_position']),
                'y' => floatval($row['y_position']),
                'fontFamily' => $row['font_family'],
                'fontSize' => intval($row['font_size']),
                'fontWeight' => $row['font_weight'],
                'color' => $row['text_color'],
                'textAlign' => $row['text_align'],
                'maxWidth' => $row['max_width'] ? intval($row['max_width']) : null,
                'rotation' => floatval($row['rotation'])
            ];
        }
        mysqli_stmt_close($stmt);
    }
    
    return $positions;
}

// Default positions if none saved
function getDefaultPositions() {
    return [
        'studentName' => ['x' => 50, 'y' => 50, 'fontSize' => 12, 'fontWeight' => 'bold', 'color' => '#000000'],
        'lrn' => ['x' => 50, 'y' => 80, 'fontSize' => 10, 'color' => '#000000'],
        'grade' => ['x' => 50, 'y' => 110, 'fontSize' => 10, 'color' => '#000000'],
        'section' => ['x' => 50, 'y' => 140, 'fontSize' => 10, 'color' => '#000000'],
        'birthdate' => ['x' => 50, 'y' => 170, 'fontSize' => 9, 'color' => '#000000'],
        'address' => ['x' => 50, 'y' => 200, 'fontSize' => 8, 'color' => '#000000'],
        'parentGuardian' => ['x' => 50, 'y' => 230, 'fontSize' => 9, 'color' => '#000000'],
        'contactNumber' => ['x' => 50, 'y' => 260, 'fontSize' => 9, 'color' => '#000000'],
        'advisorName' => ['x' => 50, 'y' => 290, 'fontSize' => 9, 'color' => '#000000'],
        'principalName' => ['x' => 50, 'y' => 320, 'fontSize' => 8, 'color' => '#000000']
    ];
}

// Get sample student data for preview
function getSampleStudent($conn) {
    $sql = "SELECT lrn, last_name, middle_initial, first_name, birthdate, 
                   parent_guardian, permanent_address, contact_number 
            FROM students 
            WHERE advisor_id = ? 
            LIMIT 1";
    
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($row = mysqli_fetch_assoc($result)) {
            mysqli_stmt_close($stmt);
            return $row;
        }
        mysqli_stmt_close($stmt);
    }
    
    // Return sample data if no student found
    return [
        'lrn' => '123456789012',
        'last_name' => 'Doe',
        'middle_initial' => 'A',
        'first_name' => 'John',
        'birthdate' => '2010-01-01',
        'parent_guardian' => 'Jane Doe',
        'permanent_address' => '123 Main Street, City',
        'contact_number' => '09123456789'
    ];
}

// Create preview PDF
function createPreviewPDF($conn, $userId, $templateType) {
    require_once __DIR__ . '/tcpdf/tcpdf.php';
    
    // Get positions
    $positions = getTemplatePositions($conn, $userId, $templateType);
    if (empty($positions)) {
        $positions = getDefaultPositions();
    }
    
    // Get sample student data
    $student = getSampleStudent($conn);
    
    // Get advisor info
    $advisorName = 'Sample Advisor';
    $advisorSql = "SELECT full_name FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $advisorSql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($result)) {
            $advisorName = $row['full_name'];
        }
        mysqli_stmt_close($stmt);
    }
    
    // Create PDF
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetMargins(0, 0, 0);
    $pdf->SetAutoPageBreak(false);
    $pdf->AddPage();
    
    // Add template background
    $templatePath = __DIR__ . '/' . ($templateType === 'front' ? 'FrontID.jpg' : 'BackID.jpg');
    if (file_exists($templatePath)) {
        $pdf->Image($templatePath, 0, 0, 210, 297, '', '', '', false, 300, '', false, false, 0, false, false, false);
    }
    
    // Helper function to add text with rotation
    function addRotatedText($pdf, $x, $y, $text, $fontFamily, $fontSize, $fontWeight, $color, $align, $maxWidth, $rotation) {
        $pdf->SetFont($fontFamily, $fontWeight, $fontSize);
        $pdf->SetTextColor($color);
        
        if ($rotation != 0) {
            $pdf->StartTransform();
            $pdf->Rotate($rotation, $x, $y);
        }
        
        if ($maxWidth && $align === 'center') {
            $pdf->SetXY($x - $maxWidth/2, $y);
            $pdf->Cell($maxWidth, 5, $text, 0, 0, 'C');
        } elseif ($maxWidth && $align === 'right') {
            $pdf->SetXY($x - $maxWidth, $y);
            $pdf->Cell($maxWidth, 5, $text, 0, 0, 'R');
        } else {
            $pdf->SetXY($x, $y);
            $pdf->Cell(0, 5, $text, 0, 0, $align);
        }
        
        if ($rotation != 0) {
            $pdf->StopTransform();
        }
    }
    
    // Add text fields
    $fieldData = [
        'studentName' => $student['last_name'] . ', ' . $student['first_name'] . ' ' . $student['middle_initial'] . '.',
        'lrn' => $student['lrn'],
        'grade' => 'Grade ' . ($templateType === 'front' ? '7' : ''), // You might want to get actual grade
        'section' => 'Rose', // You might want to get actual section
        'birthdate' => date('m/d/Y', strtotime($student['birthdate'])),
        'address' => $student['permanent_address'],
        'parentGuardian' => $student['parent_guardian'],
        'contactNumber' => $student['contact_number'],
        'advisorName' => $advisorName,
        'principalName' => 'Dr. Sample Principal' // You might want to get actual principal
    ];
    
    foreach ($fieldData as $field => $text) {
        if (isset($positions[$field])) {
            $pos = $positions[$field];
            addRotatedText(
                $pdf,
                $pos['x'],
                $pos['y'],
                $text,
                $pos['fontFamily'] ?? 'Arial',
                $pos['fontSize'] ?? 12,
                $pos['fontWeight'] ?? 'normal',
                $pos['color'] ?? '#000000',
                $pos['textAlign'] ?? 'left',
                $pos['maxWidth'],
                $pos['rotation'] ?? 0
            );
        }
    }
    
    // Output PDF
    $pdf->Output('ID_Preview_' . $templateType . '.pdf', 'I');
    exit;
}

// Generate preview
createPreviewPDF($conn, $_SESSION['user_id'], $templateType);
?>
