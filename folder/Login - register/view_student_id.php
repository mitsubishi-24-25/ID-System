<?php
require_once 'database.php';

// Start session to get user_id (advisor's ID)
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$advisor_id = $_SESSION['user_id'];

// Get LRN from URL parameter
$lrn = $_GET['lrn'] ?? '';
if (empty($lrn)) {
    echo json_encode(['success' => false, 'message' => 'Student LRN not provided']);
    exit();
}

// Get student information
$student = null;
$sql = "SELECT lrn, last_name, middle_initial, first_name, birthdate, 
               parent_guardian, permanent_address, contact_number, photo 
        FROM students 
        WHERE advisor_id = ? AND lrn = ? AND is_deleted = 0";

$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "is", $advisor_id, $lrn);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $student = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

if (!$student) {
    echo json_encode(['success' => false, 'message' => 'Student not found']);
    exit();
}

// Get advisor's section and signature
$section = "";
$advisor_name = "";
$signature_path = "";

$advisor_sql = "SELECT section, full_name, signature_path FROM users WHERE id = ?";
$advisor_stmt = mysqli_prepare($conn, $advisor_sql);
if ($advisor_stmt) {
    mysqli_stmt_bind_param($advisor_stmt, "i", $advisor_id);
    mysqli_stmt_execute($advisor_stmt);
    $advisor_result = mysqli_stmt_get_result($advisor_stmt);
    if ($advisor_row = mysqli_fetch_assoc($advisor_result)) {
        $section = $advisor_row['section'] ?? "N/A";
        $advisor_name = $advisor_row['full_name'] ?? "";
        $signature_path = $advisor_row['signature_path'] ?? "";
    }
    mysqli_stmt_close($advisor_stmt);
}

// Get principal's name and signature
$principal_name = "";
$principal_signature_path = "";

$principal_sql = "SELECT principal_name, principal_signature_path FROM users WHERE id = ?";
$principal_stmt = mysqli_prepare($conn, $principal_sql);
if ($principal_stmt) {
    mysqli_stmt_bind_param($principal_stmt, "i", $advisor_id);
    mysqli_stmt_execute($principal_stmt);
    $principal_result = mysqli_stmt_get_result($principal_stmt);
    if ($principal_row = mysqli_fetch_assoc($principal_result)) {
        $principal_name = $principal_row['principal_name'] ?? "";
        $principal_signature_path = $principal_row['principal_signature_path'] ?? "";
    }
    mysqli_stmt_close($principal_stmt);
}

// Get ID template paths
$frontImagePath = '';
$backImagePath = '';
if (isset($_SESSION['front_id_template']) && file_exists($_SESSION['front_id_template'])) {
    $frontImagePath = $_SESSION['front_id_template'];
} else {
    $frontImagePath = __DIR__ . '/FrontID.jpg';
}

if (isset($_SESSION['back_id_template']) && file_exists($_SESSION['back_id_template'])) {
    $backImagePath = $_SESSION['back_id_template'];
} else {
    $backImagePath = __DIR__ . '/BackID.jpg';
}

// Generate ID card HTML
function generateIDCard($student, $section, $advisor_name, $signature_path, $principal_name, $principal_signature_path, $frontImagePath, $backImagePath, $side) {
    $idWidth = 54;     // Width in mm
    $idHeight = 85.6;  // Height in mm
    
    // Convert mm to pixels (assuming 96 DPI: 1mm = 3.78px)
    $pxWidth = $idWidth * 3.78;
    $pxHeight = $idHeight * 3.78;
    
    $imagePath = ($side === 'front') ? $frontImagePath : $backImagePath;
    
    $html = '<div class="id-card" style="width: ' . $pxWidth . 'px; height: ' . $pxHeight . 'px; position: relative; background: url(\'' . htmlspecialchars($imagePath) . '\') no-repeat center center; background-size: cover; border: 1px solid #ddd; border-radius: 8px; overflow: hidden;">';
    
    if ($side === 'front') {
        // Front of ID
        // Add student photo if available
        if (!empty($student['photo'])) {
            $photoPath = htmlspecialchars($student['photo']);
            if (file_exists($student['photo'])) {
                $html .= '<div style="position: absolute; top: 87px; left: 50%; transform: translateX(-50%); width: 120px; height: 120px; border-radius: 8px; overflow: hidden; border: 2px solid #ff8903ff; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">';
                $html .= '<img src="' . $photoPath . '" style="width: 100%; height: 100%; object-fit: cover;" alt="Student Photo">';
                $html .= '</div>';
            }
        }
        
        $html .= '<div style="position: absolute; bottom: 65.6px; left: 0; right: 0; text-align: center; color: #800080;">';
        $html .= '<div style="font-weight: bold; font-size: 14px; margin-bottom: 2px;">' . htmlspecialchars(strtoupper($student['last_name'])) . '</div>';
        $html .= '<div style="font-size: 12px; margin-bottom: 2px;">' . htmlspecialchars($student['first_name'] . ' ' . $student['middle_initial'] . '.') . '</div>';
        $html .= '<div style="color: white; font-size: 8px; padding: 2px 4px; border-radius: 3px; display: inline-block;  position: absolute; left: 2:">' . htmlspecialchars($section) . '</div>';
        $html .= '<div style="font-size: 10px; color: #333; position: absolute; bottom: -41.4px; right: 63px">' . htmlspecialchars($student['lrn']) . '</div>';
        $html .= '</div>';
    } else {
        // Back of ID
        $html .= '<div style="position: absolute; top: 65px; left: 78px; font-size: 9px; color: #333;">';
        $html .= date('m/d/Y', strtotime($student['birthdate']));
        $html .= '</div>';
        
        // Add advisor signature if exists (smaller and above name)
        if (!empty($signature_path) && file_exists($signature_path)) {
            $html .= '<div style="position: absolute; top: 95px; left: 52%; transform: translateX(-50%); text-align: center;">';
            $html .= '<img src="' . htmlspecialchars($signature_path) . '" style="width: 80px; height: 25px; object-fit: contain;" alt="Advisor Signature">';
            $html .= '</div>';
        }
        
        $html .= '<div style="position: absolute; top: 113px; left: 52%; transform: translateX(-50%); text-align: center; font-size: 9px; color: #333;">';
        $html .= htmlspecialchars($advisor_name);
        $html .= '</div>';
        
        // Add principal information below advisor
        if (!empty($principal_name)) {
            // Add principal signature if exists (smaller and above name)
            if (!empty($principal_signature_path) && file_exists($principal_signature_path)) {
                $html .= '<div style="position: absolute; top: 140px; left: 52%; transform: translateX(-50%); text-align: center;">';
                $html .= '<img src="' . htmlspecialchars($principal_signature_path) . '" style="width: 70px; height: 22px; object-fit: contain;" alt="Principal Signature">';
                $html .= '</div>';
            }
            
            $html .= '<div style="position: absolute; top: 157px; left: 52%; transform: translateX(-50%); text-align: center; font-size: 8px; color: #333;">';
            $html .= htmlspecialchars($principal_name);
            $html .= '</div>';
        }
        
        $html .= '<div style="position: absolute; top: 212px; left: 10px; font-size: 8px; color: #333; max-width: 200px;">';
        $html .= htmlspecialchars(wordwrap($student['parent_guardian'], 20, "\n", true));
        $html .= '</div>';
        
        $html .= '<div style="position: absolute; top: 250px; left: 10px; font-size: 8px; color: #333; max-width: 200px;">';
        $html .= htmlspecialchars(wordwrap($student['permanent_address'], 25, "\n", true));
        $html .= '</div>';
        
        $html .= '<div style="position: absolute; top: 288px; left: 35px; font-size: 9px; color: #333;">';
        $html .= htmlspecialchars($student['contact_number']);
        $html .= '</div>';
    }
    
    $html .= '</div>';
    return $html;
}

// Generate the HTML response
$html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student ID - ' . htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) . '</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background: rgba(0, 0, 0, 0.5);
            margin: 0;
            padding: 20px;
            font-family: Arial, sans-serif;
        }
        .modal-container {
            background: white;
            border-radius: 12px;
            padding: 20px;
            max-width: 800px;
            margin: 0 auto;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        .id-cards-container {
            display: flex;
            justify-content: space-around;
            align-items: flex-start;
            gap: 20px;
            margin: 20px 0;
            flex-wrap: wrap;
        }
        .id-card-wrapper {
            text-align: center;
        }
        .id-card-label {
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
            font-size: 14px;
        }
        .student-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .close-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }
        .close-btn:hover {
            background: #c82333;
        }
        .header-title {
            color: #333;
            font-weight: bold;
            font-size: 18px;
            margin-bottom: 15px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="modal-container">
        <div class="header-title">
            <i class="bi bi-card-image me-2"></i>
            Student ID Preview: ' . htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) . '
        </div>
        
        <div class="student-info">
            <div class="row">
                <div class="col-md-3"><strong>LRN:</strong> ' . htmlspecialchars($student['lrn']) . '</div>
                <div class="col-md-3"><strong>Name:</strong> ' . htmlspecialchars($student['last_name'] . ', ' . $student['first_name'] . ' ' . $student['middle_initial']) . '</div>
                <div class="col-md-3"><strong>Section:</strong> ' . htmlspecialchars($section) . '</div>
                <div class="col-md-3"><strong>Contact:</strong> ' . htmlspecialchars($student['contact_number']) . '</div>
            </div>
        </div>
        
        <div class="id-cards-container">
            <div class="id-card-wrapper">
                <div class="id-card-label">
                    <i class="bi bi-front me-1"></i>Front of ID
                </div>
                ' . generateIDCard($student, $section, $advisor_name, $signature_path, $principal_name, $principal_signature_path, $frontImagePath, $backImagePath, 'front') . '
            </div>
            
            <div class="id-card-wrapper">
                <div class="id-card-label">
                    <i class="bi bi-back me-1"></i>Back of ID
                </div>
                ' . generateIDCard($student, $section, $advisor_name, $signature_path, $principal_name, $principal_signature_path, $frontImagePath, $backImagePath, 'back') . '
            </div>
        </div>
        
        <div class="text-center mt-3">
        </div>
    </div>
</body>
</html>';

echo $html;
?>
