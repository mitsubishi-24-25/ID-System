<?php
require_once 'includes/header.php';
require_once 'database.php';

// Start session to get user_id (advisor's ID)
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$advisor_id = $_SESSION['user_id'];
$lrn = isset($_GET['lrn']) ? $_GET['lrn'] : '';

// Get advisor information including signature
$advisor_sql = "SELECT full_name, section, signature_path FROM users WHERE id = ?";
$advisor_stmt = mysqli_prepare($conn, $advisor_sql);
$advisor_name = "";
$section = "";
$signature_path = "";

if ($advisor_stmt) {
    mysqli_stmt_bind_param($advisor_stmt, "i", $advisor_id);
    mysqli_stmt_execute($advisor_stmt);
    $result = mysqli_stmt_get_result($advisor_stmt);
    if ($row = mysqli_fetch_assoc($result)) {
        $advisor_name = $row['full_name'];
        $section = $row['section'] ?? "N/A";
        $signature_path = $row['signature_path'] ?? '';
    }
    mysqli_stmt_close($advisor_stmt);
}

// Get student information
$student = null;
if ($lrn) {
    $sql = "SELECT lrn, last_name, middle_initial, first_name, birthdate, 
                   parent_guardian, permanent_address, contact_number, photo 
            FROM students 
            WHERE lrn = ? AND advisor_id = ? AND (deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00')";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "si", $lrn, $advisor_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $student = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
    }
}

// If no specific student is selected, go back to index
if (!$student) {
    header("Location: index.php");
    exit();
}

// Format student data
$lrn_display = $student['lrn'];
$birthdate = !empty($student['birthdate']) ? date('F j, Y', strtotime($student['birthdate'])) : 'N/A';
$parent_guardian = $student['parent_guardian'];
$address = $student['permanent_address'];
$contact = $student['contact_number'];
$photo_path = !empty($student['photo']) ? $student['photo'] : 'assets/images/default-avatar.png';

// ID Card dimensions (scaled down to fit container)
$scale = 1; // Further reduced scale factor (50% of original size)
$idWidth = 85.73 * $scale;  // Scaled width in mm
$idHeight = 53.98 * $scale; // Scaled height in mm

// Define paths to the ID templates
$frontImagePath = 'FrontID.png';
$backImagePath = 'BackID.png';

// Check if images exist
if (!file_exists($frontImagePath) || !file_exists($backImagePath)) {
    die('ID card images not found. Please ensure FrontID.png and BackID.png are in the root directory.');
}

?>

<style>
    body {
        background-color: #f5f5f0;
        font-family: 'Times New Roman', serif;
    }
    
    .id-container {
        max-width: 900px;
        margin: 1rem auto;
        padding: 2rem;
        background: white;
        border-radius: 10px;
        box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }
    
    .id-preview {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 10px;
        margin: 20px 0;
        width: 100%;
    }
    
    .id-row {
        display: flex;
        justify-content: center;
        gap: 15px;
        width: 100%;
        margin-bottom: 15px;
    }
    
    .id-card-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 15px;
        width: auto;
    }
    
    .id-card {
        width: 3.375in; /* Exact PDF dimensions */
        height: 2.125in;
        position: relative;
        margin: 0;
        border: 1px solid #ddd;
        box-sizing: border-box;
        flex: 0 0 auto;
        transform: scale(1.2); /* Slightly larger for better visibility */
        transform-origin: top center;
    }
    
    .id-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
    }
    
    .photo-container {
        position: absolute;
        top: 24.5mm;
        left: 50%;
        transform: translateX(-50%);
        width: 29.5mm;
        height: 29.5mm;
        border: 1px solid #ddd;
        border-radius: 2px;
        overflow: hidden;
    }
    
    .photo-container img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .student-name {
        position: absolute;
        top: 55mm;
        left: 0;
        right: 0;
        text-align: center;
        color: #6f42c1;
        font-weight: bold;
        text-transform: uppercase;
        font-size: 12px;
        line-height: 1.2;
    }
    
    .student-section {
        position: absolute;
        bottom: 5mm;
        left: 0;
        right: 0;
        text-align: center;
        color: white;
        font-weight: bold;
        font-size: 10px;
        padding: 3px 0;
        background-color: #6f42c1;
    }
    
    .action-buttons {
        text-align: center;
        margin: 20px 0;
    }
    
    .action-buttons .btn {
        margin: 0 5px;
        padding: 8px 20px;
    }
    
    .signature-box {
        position: absolute;
        bottom: 40px;
        right: 20px;
        text-align: center;
        font-size: 10px;
    }
    
    .signature-box img {
        max-width: 80px;
        height: auto;
        border-bottom: 1px solid #000;
        margin-bottom: 5px;
    }
    
    .no-print {
        display: none;
    }
    
    @media print {
        .no-print {
            display: none;
        }
    }
</style>

<div class="id-container">
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <h2>ID Card Preview</h2>
        <div>
            <a href="index.php" class="btn btn-outline-secondary me-2">
                <i class="bi bi-arrow-left me-1"></i> Back to List
            </a>
            <button onclick="window.print()" class="btn btn-print me-2">
                <i class="bi bi-printer me-1"></i> Print ID
            </button>
            <a href="download_id.php?lrn=<?php echo urlencode($lrn); ?>" class="btn btn-primary">
                <i class="bi bi-download me-1"></i> Download All IDs
            </a>
        </div>
    </div>
    
    <div class="text-center mb-4 no-print">
        <p class="text-muted">This is how the ID will look when printed or downloaded.</p>
    </div>
    
    <div class="id-preview">
        <div class="id-card-container">
            <!-- Front of ID -->
            <div class="id-card">
            <img src="<?php echo $frontImagePath; ?>" alt="ID Front">
            <div class="id-overlay">
                <!-- Student Photo -->
                <div class="photo-container">
                    <img src="<?php echo $photo_path; ?>" alt="Student Photo" onerror="this.src='assets/images/default-avatar.png'">
                </div>
                
                <!-- Student Name -->
                <div class="student-name">
                    <?php echo htmlspecialchars(strtoupper($student['last_name'])); ?>
                </div>
                
                <!-- Section -->
                <div class="student-section">
                    <?php echo htmlspecialchars(strtoupper($section)); ?>
                </div>
            </div>
            </div>
            
            <!-- Back of ID -->
            <div class="id-card">
            <img src="<?php echo $backImagePath; ?>" alt="ID Back">
            <div class="id-overlay">
                <!-- Student details -->
                <div style="position: absolute; top: 15mm; left: 5mm; right: 5mm; font-size: 8px; line-height: 1.3;">
                    <div style="margin-bottom: 2mm;">
                        <strong>LRN:</strong> <?php echo htmlspecialchars($lrn_display); ?>
                    </div>
                    <div style="margin-bottom: 2mm;">
                        <strong>Name:</strong> <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['middle_initial'] . '. ' . $student['last_name']); ?>
                    </div>
                    <div style="margin-bottom: 2mm;">
                        <strong>Birthdate:</strong> <?php echo htmlspecialchars($birthdate); ?>
                    </div>
                    <div style="margin-bottom: 2mm;">
                        <strong>Parent/Guardian:</strong> <?php echo htmlspecialchars($parent_guardian); ?>
                    </div>
                    <div style="margin-bottom: 2mm;">
                        <strong>Address:</strong> <?php echo htmlspecialchars($address); ?>
                    </div>
                    <div>
                        <strong>Contact #:</strong> <?php echo htmlspecialchars($contact); ?>
                    </div>
                </div>
                
                <!-- Advisor's Signature -->
                <?php if (!empty($signature_path) && file_exists($signature_path)): ?>
                <div style="position: absolute; bottom: 10mm; right: 5mm; text-align: center; font-size: 8px;">
                    <div style="border-bottom: 1px solid #000; width: 25mm; margin: 0 auto 1mm;">
                        <img src="<?php echo htmlspecialchars($signature_path); ?>" alt="Advisor's Signature" style="max-width: 25mm; height: auto;">
                    </div>
                    <div>Adviser's Signature</div>
                </div>
                <?php endif; ?>
            </div>
            </div>
        </div>
        
        <div class="action-buttons no-print">
            <a href="download_id.php?lrn=<?php echo urlencode($lrn); ?>" class="btn btn-success">
                <i class="bi bi-download me-1"></i> Download ID
            </a>
            <button onclick="window.print()" class="btn btn-print">
                <i class="bi bi-printer me-1"></i> Print ID
            </button>
            <a href="edit_student.php?lrn=<?php echo urlencode($lrn); ?>" class="btn btn-outline-primary">
                <i class="bi bi-pencil me-1"></i> Edit Information
            </a>
        </div>
    </div>
</div>

<script>
// Automatically trigger print dialog when printing
function beforePrint() {
    // Add any print-specific styles or modifications here
}

function afterPrint() {
    // Restore any changes made for printing
}

// Add event listeners for print
if (window.matchMedia) {
    const mediaQueryList = window.matchMedia('print');
    mediaQueryList.addListener(function(mql) {
        if (mql.matches) {
            beforePrint();
        } else {
            afterPrint();
        }
    });
}

window.onbeforeprint = beforePrint;
window.onafterprint = afterPrint;
</script>

<?php require_once 'includes/footer.php'; ?>
