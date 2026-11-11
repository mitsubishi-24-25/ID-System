<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

// Add CSP header that allows necessary resources
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; img-src 'self' data:; font-src 'self' https://cdn.jsdelivr.net;");

require_once "database.php";

$userId = $_SESSION['user_id'];
$section = "";
$signaturePath = "";

// Get user's section and signature path
$sql = "SELECT section, signature_path FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $section = $row['section'];
        $signaturePath = $row['signature_path'];
    }
    mysqli_stmt_close($stmt);
}

// Get students data with transportation info and advisor's section
$sql = "SELECT s.*, u.section as advisor_section, s.grade as grade_level, s.section as student_section 
        FROM students s
        JOIN users u ON s.advisor_id = u.id
        WHERE s.advisor_id = ? AND s.is_deleted = 0 
        ORDER BY s.last_name, s.first_name";
$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $error = "Error preparing statement: " . mysqli_error($conn);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Smart ID - <?php echo $page_title; ?></title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f5f5f0;
            font-family: 'Roboto', sans-serif;
        }
        
        .card-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .search-container {
            margin-bottom: 1.5rem;
        }
        
        .search-container .form-control:focus {
            box-shadow: 0 0 0 0.25rem rgba(25, 135, 84, 0.25);
            border-color: #198754;
        }
        
        .search-container .input-group-text {
            background-color: white;
            border-color: #198754;
            color: #198754;
        }
        
        .search-container .form-control {
            border-color: #198754;
            transition: all 0.3s ease;
        }
        
        .search-container .btn {
            background-color: #198754;
            border-color: #198754;
            transition: all 0.3s ease;
        }
        
        .search-container .btn:hover {
            background-color: #157347;
            border-color: #146c43;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #495057;
            border-bottom: 2px solid #dee2e6;
        }
        
        .table td {
            vertical-align: middle;
            padding: 0.75rem;
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(25, 135, 84, 0.05);
        }
        
        .welcome-message {
            background: linear-gradient(135deg, #198754, #157347);
            color: white;
            padding: 1.5rem;
            border-radius: 8px;
            margin-top: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .welcome-message h2 {
            margin-bottom: 0.25rem;
            font-weight: 600;
        }
        
        .welcome-message p {
            margin-bottom: 0;
            opacity: 0.9;
        }
        
        @media (max-width: 768px) {
            .d-flex.justify-content-between.align-items-center {
                flex-direction: column;
                gap: 1rem;
            }
            
            .search-container {
                width: 100%;
            }
        }
        
        .badge {
            font-size: 0.8rem;
            font-weight: 500;
            padding: 0.35em 0.65em;
            color: #000 !important;
        }
        
        .table td:nth-child(5) {
            color: #000 !important;
        }
        
        .table td:nth-child(5) .text-muted {
            color: #000 !important;
        }
    </style>
</head>
<body>
<?php include 'includes/header.php'; ?>
<main class="main-content" id="mainContent">
    <div class="container">
        <div class="welcome-message mb-4">
            <h2>Welcome, <?php echo htmlspecialchars($_SESSION["full_name"]); ?></h2>
            <p class="text-white">Adviser of <?php echo htmlspecialchars($section); ?></p>
        </div>

        <div class="d-flex flex-column gap-3 mb-3">
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="mb-0">Students List</h2>
                <div class="d-flex gap-2">
                    <a href="set_grade_section.php" class="btn btn-primary">
                        <i class="bi bi-gear-fill me-1"></i> Set Grade & Section
                    </a>
                    <a href="add_sfr_info.php" class="btn btn-success">
                        <i class="bi bi-plus-circle me-1"></i> Add SFR Info
                    </a>
                </div>
            </div>
            <div class="d-flex justify-content-end">
                <div class="search-container" style="width: 100%; max-width: 600px;">
                    <div class="input-group shadow-sm">
                        <span class="input-group-text bg-white border-end-0" id="search-addon" style="border-color: #198754;">
                            <i class="bi bi-search" style="color: #198754;"></i>
                        </span>
                        <input type="text" 
                               id="searchInput" 
                               class="form-control border-start-0 ps-0" 
                               placeholder="Search students by name, LRN, or contact..." 
                               aria-label="Search students"
                               aria-describedby="search-addon">
                        <button class="btn btn-success px-3" type="button" id="searchButton" style="background-color: #198754;">
                            <i class="bi bi-search me-1"></i> Search
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="studentsTable" style="border-collapse: separate; border-spacing: 0; border-radius: 8px; overflow: hidden;">
                        <thead>
                            <tr>
                                <th>LRN</th>
                                <th>Last Name</th>
                                <th>First Name</th>
                                <th>Grade</th>
                                <th>Section</th>
                                <th>Transportation</th>
                                <th>Minutes of Transportation</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (isset($result) && mysqli_num_rows($result) > 0): ?>
                                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['lrn']); ?></td>
                                        <td><?php echo htmlspecialchars($row['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['first_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['grade_level'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($row['student_section'] ?? 'N/A'); ?></td>
                                        <td>
                                            <?php if (!empty($row['transportation_type'])): ?>
                                                <span class="badge bg-<?php echo strtolower($row['transportation_type']); ?>">
                                                    <?php echo htmlspecialchars($row['transportation_type']); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">Not set</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($row['travel_minutes'])): ?>
                                                <?php echo htmlspecialchars($row['travel_minutes']); ?> mins
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="edit_sfr_info.php?lrn=<?php echo urlencode($row['lrn']); ?>" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-pencil"></i> Edit
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="bi bi-info-circle-fill d-block mb-2" style="font-size: 2rem;"></i>
                                            No students found. <a href="add_student.php" class="text-decoration-none">Add a student</a> to get started.
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Search functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const searchButton = document.getElementById('searchButton');
    const table = document.getElementById('studentsTable');
    
    function filterTable() {
        const filter = searchInput.value.toUpperCase();
        const tr = table.getElementsByTagName('tr');
        
        // Loop through all table rows, and hide those that don't match the search query
        for (let i = 1; i < tr.length; i++) {
            // Skip the header row and any other non-data rows
            if (i === 0) continue;
            
            // Get all cells in the current row
            const tds = tr[i].getElementsByTagName('td');
            let found = false;
            
            // Check each cell in the row
            for (let j = 0; j < tds.length; j++) {
                const td = tds[j];
                if (td) {
                    const txtValue = td.textContent || td.innerText;
                    if (txtValue.toUpperCase().indexOf(filter) > -1) {
                        found = true;
                        break;
                    }
                }
            }
            
            // Show or hide the row based on search match
            tr[i].style.display = found ? '' : 'none';
        }
    }

    // Add event listeners for search
    searchInput.addEventListener('keyup', filterTable);
    searchButton.addEventListener('click', filterTable);
});
</script>

<?php include 'includes/footer.php'; ?>

<?php
// Helper function to get badge class based on transportation type
function getTransportationBadgeClass($type) {
    switch (strtolower($type)) {
        case 'walking':
            return 'walking';
        case 'bus':
            return 'bus';
        case 'car':
            return 'car';
        case 'bicycle':
            return 'bicycle';
        default:
            return 'other';
    }
}
?>
</body>
</html>