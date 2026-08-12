<?php
session_start();
require_once '../../../config/db_connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'veterinary_surgeon') {
    header("Location: ../../../index.php");
    exit();
}

// Extract base operational keys from the live user session wrapper
$user_id = $_SESSION['user_id'] ?? null;
$range_id = $_SESSION['range_id'] ?? null;

$range_name = 'Your Range';
$district_name = 'Your District';
$iframe_url = '';

// Step 1: Query the user's data profile if it's missing from the active session context
if (empty($range_id) && !empty($user_id)) {
    $user_query = $mysqli->prepare("SELECT range_id FROM users WHERE id = ?");
    if ($user_query) {
        $user_query->bind_param("i", $user_id);
        $user_query->execute();
        $user_result = $user_query->get_result();
        if ($row = $user_result->fetch_assoc()) {
            $_SESSION['range_id'] = $row['range_id'];
            $range_id = $row['range_id'];
        }
        $user_query->close();
    }
}

// Step 2: Extract Range Name, District Name, and Map URL using a clean, relational JOIN
if (!empty($range_id)) {
    $details_sql = "
        SELECT 
            vr.name AS range_name,
            d.name AS district_name,
            vrm.iframe_url
        FROM veterinary_ranges vr
        LEFT JOIN districts d ON vr.district_id = d.id
        LEFT JOIN veterinary_range_maps vrm ON vr.id = vrm.range_id
        WHERE vr.id = ?
    ";

    $details_query = $mysqli->prepare($details_sql);
    if ($details_query) {
        $details_query->bind_param("i", $range_id);
        $details_query->execute();
        $details_result = $details_query->get_result();
        if ($data = $details_result->fetch_assoc()) {
            $range_name = $data['range_name'] ?? 'Your Assigned Range';
            $district_name = $data['district_name'] ?? 'Your District';
            $iframe_url = $data['iframe_url'] ?? '';
        }
        $details_query->close();
    }
}

require_once '../../../includes/header.php';

?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="../../../assets/css/veterinary.css">





        <div class="mb-4 d-flex justify-content-between align-items-center">
            <div>
                <h2 class="h4 fw-bold mb-1" style="color: #370709;">Production Balance</h2>
                <p class="text-muted small mb-0">Production Balance metrics dynamically captured for <strong class="text-dark"><?= htmlspecialchars($range_name) ?></strong></p>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <?php if (isset($_SESSION['msg'])): ?>
                    <div class="alert alert-<?= $_SESSION['msg_type'] ?> py-2 px-3 mb-0 small">
                        <?= $_SESSION['msg'] ?>
                    </div>
                    <?php unset($_SESSION['msg'], $_SESSION['msg_type']); ?>
                <?php endif; ?>
                <a href="monthly-annual-reports.php" class="btn btn-secondary shadow-sm text-nowrap">
                    <i class="bi bi-arrow-left me-2"></i>Back
                </a>
            </div>
        </div>

        <div class="row g-4">

            <div class="card shadow-sm mb-4 border-0">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-grid-3x3-gap-fill me-2"></i>Quick Actions</h6>
                </div>
                <div class="card-body pt-0">
                    <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-3">

                        <div class="col">
                            <a href="crop_returns.php" class="btn w-100 h-100 py-3 text-light border-0 shadow-sm d-flex flex-column align-items-center justify-content-center" style="background-color: #820100; min-height: 105px;">
                                <i class="bi bi-file-earmark-bar-graph-fill fs-3 mb-1"></i>
                                <span class="text-center">Crop Returns</span>
                            </a>
                        </div>
                        <div class="col">
                            <a href="section_e.php" class="btn w-100 h-100 py-3 text-light border-0 shadow-sm d-flex flex-column align-items-center justify-content-center" style="background-color: #370709; min-height: 105px;">
                                <i class="bi bi-bar-chart fs-3 mb-1"></i>
                                <span class="text-center">Section E</span>
                            </a>
                        </div>
                        <div class="col">
                            <a href="letter_h_record.php" class="btn w-100 h-100 py-3 text-light border-0 shadow-sm d-flex flex-column align-items-center justify-content-center" style="background-color: #b08723; min-height: 105px;">
                                <i class="bi bi-car-front-fill fs-3 mb-1"></i>
                                <span class="text-center">Letter H Record</span>
                            </a>
                        </div>
                        <div class="col">
                            <a href="cash_book_summary.php" class="btn w-100 h-100 py-3 text-light border-0 shadow-sm d-flex flex-column align-items-center justify-content-center" style="background-color: #a07174; min-height: 105px;">
                                <i class="bi bi-file-earmark-plus fs-3 mb-1"></i>
                                <span class="text-center">Cash Book Summary</span>
                            </a>
                        </div>



                    </div>
                </div>
            </div>
        </div>

    </main>
</div>

<script src="../../../assets/js/veterinary.js"></script>
<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<?php require_once '../../../includes/footer.php'; ?>