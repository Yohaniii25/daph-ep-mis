<?php
require_once '../../../includes/header.php';

require_once '../../../config/db_connect.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $veterinary_range = trim($_POST['veterinary_range']);
    $ds_division = trim($_POST['ds_division']);
    $gn_division = trim($_POST['gn_division']);
    $farmer_name = trim($_POST['farmer_name']);
    $nic_no = trim($_POST['nic_no']);
    $address = trim($_POST['address']);

    // Generate Registration No.
    $year = date('Y');
    $result = $mysqli->query("SELECT COUNT(*) as count FROM livestock_farms WHERE registration_no LIKE 'LF-$year-%'");
    $row = $result->fetch_assoc();
    $next_no = str_pad($row['count'] + 1, 3, '0', STR_PAD_LEFT);
    $registration_no = "LF-$year-$next_no";

    $registered_by = $_SESSION['user_id'];

    $stmt = $mysqli->prepare("INSERT INTO livestock_farms 
        (registration_no, veterinary_range, ds_division, gn_division, farmer_name, nic_no, address, registered_by) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssi", $registration_no, $veterinary_range, $ds_division, $gn_division, $farmer_name, $nic_no, $address, $registered_by);

    if ($stmt->execute()) {
        $message = '<div class="alert alert-success">Farm registered successfully!<br><strong>Registration No: ' . $registration_no . '</strong></div>';
    } else {
        $message = '<div class="alert alert-danger">Error: NIC or data may already exist.</div>';
    }
}
?>

<?php require_once '../../../includes/sidebar.php'; ?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-5 pt-5">
        <h2 class="text-center mb-5">Registration Certificate of Livestock Farms</h2>

        <?= $message ?>

        <div class="card shadow">
            <div class="card-body p-5">
                <form method="POST">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Registration No.</label>
                            <input type="text" class="form-control form-control-lg" value="Auto-generated on save" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Veterinary Range</label>
                            <input type="text" name="veterinary_range" class="form-control form-control-lg" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Divisional Secretariat Division</label>
                            <input type="text" name="ds_division" class="form-control form-control-lg" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Grama Niladhari Division</label>
                            <input type="text" name="gn_division" class="form-control form-control-lg" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-bold">Name of Farmer</label>
                            <input type="text" name="farmer_name" class="form-control form-control-lg" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">NIC No.</label>
                            <input type="text" name="nic_no" class="form-control form-control-lg" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Address</label>
                            <textarea name="address" class="form-control" rows="3" required></textarea>
                        </div>
                        <div class="col-12 text-center mt-4">
                            <button type="submit" class="btn btn-success btn-lg px-5">
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