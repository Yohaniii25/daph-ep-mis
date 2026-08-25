<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'veterinary_surgeon') {
    header("Location: ../../../../index.php");
    exit();
}

if (!isset($_SESSION['full_name'])) {
    $_SESSION['full_name'] = $_SESSION['username'] ?? 'Veterinary Surgeon';
}

$full_name   = $_SESSION['full_name'];
$range_id    = $_SESSION['range_id'] ?? null;
$district_id = $_SESSION['district_id'] ?? null;

if (empty($range_id)) {
    die('<div class="alert alert-danger text-center p-5 m-5">Error: Your account is not assigned to any Veterinary Range.</div>');
}

require_once '../../../config/db_connect.php';

$district_name = 'Unknown District';
$range_name    = 'Unknown Range';

// Fetch District and Range Names
if ($district_id) {
    $stmt = $mysqli->prepare("SELECT name FROM districts WHERE id = ?");
    $stmt->bind_param("i", $district_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $district_name = $row['name'];
    }
    $stmt->close();
}

if ($range_id) {
    $stmt = $mysqli->prepare("SELECT name FROM veterinary_ranges WHERE id = ?");
    $stmt->bind_param("i", $range_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $range_name = $row['name'];
    }
    $stmt->close();
}

$forensic_cases = [
    ['date' => '2026-04-02', 'case_id' => 'FOR/TRIN/2026/08', 'type' => 'Post-Mortem', 'subject' => 'Cross-bred Cow', 'request_by' => 'Police - Kantalai', 'status' => 'Report Issued'],
    ['date' => '2026-03-30', 'case_id' => 'LEG/TRIN/2026/22', 'type' => 'Animal Theft', 'subject' => 'Buffalo (Branded)', 'request_by' => 'Magistrate Court', 'status' => 'Pending Examination'],
];

require_once '../../../includes/header.php';
?>

<link rel="stylesheet" href="../../../assets/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="../../../assets/css/buttons.bootstrap5.min.css">


        <div class="mb-4">
            <h4 class="fw-bold mb-0 text-danger"><i class="bi bi-shield-shaded me-2"></i>Animals Act & Forensic Services</h4>
            <p class="text-muted small">Regulatory enforcement and legal veterinary medical reporting</p>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 small fw-bold text-uppercase text-muted">Legal Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <button class="btn btn-danger w-100 py-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#forensicModal">
                            <i class="bi bi-clipboard2-pulse fs-3"></i><br>
                            <span>Post-Mortem Report</span>
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-primary w-100 py-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#brandingModal">
                            <i class="bi bi-tag fs-3"></i><br>
                            <span>Animal Branding</span>
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-dark w-100 py-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#transportModal">
                            <i class="bi bi-truck fs-3"></i><br>
                            <span>Transport Permit</span>
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-success w-100 py-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#semenLogModal">
                            <i class="bi bi-droplet fs-3"></i><br>
                            <span>Semen Inventory</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-file-earmark-medical me-2"></i>Recent Legal & Forensic Cases</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light small text-uppercase">
                        <tr>
                            <th class="ps-4">Case Ref</th>
                            <th>Type</th>
                            <th>Subject</th>
                            <th>Requesting Authority</th>
                            <th>Status</th>
                            <th class="text-end pe-4">Report</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($forensic_cases as $case): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark"><?= $case['case_id'] ?></div>
                                    <small class="text-muted"><?= $case['date'] ?></small>
                                </td>
                                <td><span class="badge bg-light text-dark border"><?= $case['type'] ?></span></td>
                                <td class="small fw-medium"><?= $case['subject'] ?></td>
                                <td class="small"><?= $case['request_by'] ?></td>
                                <td>
                                    <span class="badge rounded-pill <?= $case['status'] == 'Report Issued' ? 'bg-success' : 'bg-warning text-dark' ?>">
                                        <?= $case['status'] ?>
                                    </span>
                                </td>
                                <td class="text-end pe-4">
                                    <button class="btn btn-sm btn-dark px-3"><i class="bi bi-download me-1"></i> PDF</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>