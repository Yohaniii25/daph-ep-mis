<?php
require_once '../../../includes/header.php';

// Role check (keep for security)
if (!in_array($_SESSION['role'], ['veterinary_surgeon','farms_dd'])) {
    die("Access denied");
}

// No DB, no POST — demo only
$message = '<div class="alert alert-info text-center">Demo mode - Form submission disabled in Phase 1</div>';
?>

<?php require_once '../../../includes/sidebar.php'; ?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-5 pt-5">
        <h2 class="text-center mb-5 fw-bold">Registration Certificate of Livestock Farms</h2>

     

        <div class="card shadow-lg border-0">
            <div class="card-body p-5">
                <form>
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold fs-5">Registration No.</label>
                            <input type="text" class="form-control form-control-lg bg-light" value="Auto-generated on save" >
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold fs-5">Veterinary Range</label>
                            <input type="text" class="form-control form-control-lg" placeholder="e.g., Amparai" >
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold fs-5">Divisional Secretariat Division</label>
                            <input type="text" class="form-control form-control-lg" placeholder="e.g., Amparai" >
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold fs-5">Grama Niladhari Division</label>
                            <input type="text" class="form-control form-control-lg" placeholder="e.g., Karaitivu" >
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-bold fs-5">Name of Farmer</label>
                            <input type="text" class="form-control form-control-lg" placeholder="e.g., Ahmed Rizwan" >
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold fs-5">NIC No.</label>
                            <input type="text" class="form-control form-control-lg" placeholder="e.g., 198512345678" >
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold fs-5">Address</label>
                            <textarea class="form-control" rows="4" placeholder="Full address..." ></textarea>
                        </div>
                        <div class="col-12 text-center mt-5">
                            <button type="button" class="btn btn-success btn-lg px-5" >
                                Register Farm
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>


    </main>
</div>

<?php require_once '../../../includes/footer.php'; ?>