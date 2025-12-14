<!-- includes/sidebar.php  →  ONLY Provincial Director sees full menu -->
<?php
$role = $_SESSION['role'] ?? '';
$is_pd = ($role === 'provincial_director');
?>

<div id="layoutSidenav_nav">
    <nav class="sb-sidenav accordion sb-sidenav-dark" style="background:#6B0F1A; width:260px;">
        <div class="sb-sidenav-menu">
            <div class="nav">

                <!-- Logo Area -->
                <div class="text-center py-4" style="background:rgba(255,255,255,0.1);">
                    <img src="../assets/img/logo.png" height="60" class="mb-2">
                    <h6 class="text-white mb-0">DAPH - EP MIS</h6>
                </div>

                <!-- Always visible for everyone -->
                <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : '' ?>" 
                   href="../dashboard.php">
                    <i class="bi bi-speedometer2 me-3"></i> Dashboard
                </a>

                <!-- ONLY PROVINCIAL DIRECTOR SEES THESE MENU ITEMS -->
                <?php if ($is_pd): ?>

                    <a class="nav-link" href="../pages/diary_management.php">
                        <i class="bi bi-journal-text me-3"></i> Diary Management
                    </a>

                    <a class="nav-link" href="../pages/vehicle_management.php">
                        <i class="bi bi-truck me-3"></i> Vehicle Management
                    </a>

                    <a class="nav-link" href="../pages/reports.php">
                        <i class="bi bi-file-earmark-bar-graph me-3"></i> Reports
                    </a>

                    <div class="sb-sidenav-menu-heading text-white-50 mt-4">System</div>

                    <a class="nav-link" href="../pages/settings.php">
                        <i class="bi bi-gear me-3"></i> Settings
                    </a>

                <?php endif; ?>
                <!-- END OF PD-ONLY MENU -->

                <!-- Common for all users who can see something -->
                <?php if ($is_pd || in_array($role, ['district_dd','veterinary_surgeon','admin'])): ?>
                    <a class="nav-link" href="../pages/my_diary.php">
                        <i class="bi bi-journal me-3"></i> My Diary
                    </a>
                <?php endif; ?>

                <!-- Logout for everyone -->
                <div class="sb-sidenav-menu-heading text-white-50 mt-4">Account</div>
                <a class="nav-link text-danger" href="../logout.php">
                    <i class="bi bi-box-arrow-right me-3"></i> Logout
                </a>

            </div>
        </div>

        <!-- Footer inside sidebar -->
        <div class="sb-sidenav-footer text-white" style="background:rgba(0,0,0,0.3);">
            <div class="small">Logged in as:</div>
            <strong><?= htmlspecialchars($_SESSION['full_name']) ?></strong><br>
            <small><?= ucwords(str_replace('_', ' ', $role)) ?></small>
        </div>
    </nav>
</div>