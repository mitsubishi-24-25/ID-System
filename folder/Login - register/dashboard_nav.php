<?php

?>
<style>
    .sidebar-header {
        padding: 1rem 1.5rem;
        border-bottom: 1px solid rgba(0,0,0,0.1);
        margin-bottom: 1rem;
    }
    .sidebar-header h3 {
        color: var(--primary-color);
        margin: 0;
        font-weight: 600;
    }
    .sidebar-footer {
        padding: 1rem 1.5rem;
        border-top: 1px solid rgba(0,0,0,0.1);
        margin-top: auto;
    }
    .btn-logout {
        background-color: 
        color: white;
        border: none;
        width: 100%;
        padding: 0.5rem 1rem;
        border-radius: 6px;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    .btn-logout:hover {
        background-color: 
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3);
    }
</style>

<div class="offcanvas offcanvas-start d-flex flex-column" tabindex="-1" id="dashboardOffcanvas" style="width: 280px;">
    <div class="sidebar-header">
        <h3>Menu</h3>
    </div>
    <div class="offcanvas-body p-0 flex-grow-1 d-flex flex-column">
        <div class="d-grid gap-2 px-3">
            <a href="add_student.php" class="btn btn-secondary text-start <?php echo basename($_SERVER['PHP_SELF']) == 'add_student.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-plus me-2"></i> Add Individual Student
            </a>
            <a href="download_excel_template.php" class="btn btn-secondary text-start <?php echo basename($_SERVER['PHP_SELF']) == 'download_excel_template.php' ? 'active' : ''; ?>">
                <i class="fas fa-file-excel me-2"></i> Download Excel Template
            </a>
            <a href="upload_excel.php" class="btn btn-secondary text-start <?php echo basename($_SERVER['PHP_SELF']) == 'upload_excel.php' ? 'active' : ''; ?>">
                <i class="fas fa-upload me-2"></i> Upload Excel
            </a>
            <a href="upload_photo.php" class="btn btn-secondary text-start <?php echo basename($_SERVER['PHP_SELF']) == 'upload_photo.php' ? 'active' : ''; ?>">
                <i class="fas fa-camera me-2"></i> Upload Photo
            </a>
            <a href="add_signature.php" class="btn btn-secondary text-start <?php echo basename($_SERVER['PHP_SELF']) == 'add_signature.php' ? 'active' : ''; ?>">
                <i class="fas fa-signature me-2"></i> Manage Signature
            </a>
            <a href="download_id.php" class="btn btn-secondary text-start <?php echo basename($_SERVER['PHP_SELF']) == 'download_id.php' ? 'active' : ''; ?>
                <i class="fas fa-id-card me-2"></i> Download ID
            </a>
        </div>
    </div>
    <div class="sidebar-footer">
        <a href="logout.php" class="btn btn-logout">
            <i class="fas fa-sign-out-alt me-2"></i> Logout
        </a>
    </div>
</div>

<!-- Hamburger Menu Button -->
<div class="hamburger" data-bs-toggle="offcanvas" data-bs-target="
    <span></span>
    <span></span>
    <span></span>
</div>
