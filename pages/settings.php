<?php
require_once '../includes/header.php';

// Adjust role check as needed (e.g., for any role accessing this page)
if (!isset($_SESSION['user_id'])) {
    die("Access denied");
}
?>
<?php require_once '../includes/sidebar.php'; ?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        <div class="row justify-content-center align-items-center" style="min-height: 70vh;">
            <div class="col-lg-8 col-md-10 text-center">
                <!-- Under Construction Icon -->
                <div class="mb-5">
                    <i class="bi bi-cone-striped fs-1 text-warning" style="font-size: 8rem;"></i>
                </div>

                <!-- Message -->
                <h1 class="display-4 fw-bold text-dark mb-4">Under Construction</h1>
                <p class="lead text-muted mb-5">
                    This page is currently being developed and will be available soon.<br>
                    We're working hard to bring you the best experience.
                </p>


                <!-- Back Button -->
                <a href="javascript:history.back()" class="btn btn-primary btn-lg px-5">
                    <i class="bi bi-arrow-left me-2"></i> Go Back
                </a>
            </div>
        </div>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?>