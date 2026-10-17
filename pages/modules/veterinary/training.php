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

require_once __DIR__ . '/../../../config/db_connect.php';

/** @var mysqli $mysqli */
global $mysqli;

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

// Centralized Integration: Fetch Completed Farmer Training Records
$farmer_trainings = [];
$ft_stmt = $mysqli->prepare("
    SELECT ftr.*, COALESCE(vr.name, '') AS range_name 
    FROM `farmer_training_records` ftr 
    LEFT JOIN `veterinary_ranges` vr ON ftr.range_id = vr.id 
    WHERE (ftr.range_id = ? OR ? = 0)
    ORDER BY ftr.completion_date DESC, ftr.id DESC
");
if ($ft_stmt) {
    $ft_stmt->bind_param("ii", $range_id, $range_id);
    $ft_stmt->execute();
    $farmer_trainings = $ft_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $ft_stmt->close();
}

$training_logs = [
    ['date' => '2026-04-10', 'topic' => 'Modern Milking Hygiene', 'target' => 'Farmers', 'attendees' => 25, 'location' => 'Kantalai West', 'status' => 'Scheduled'],
    ['date' => '2026-04-02', 'topic' => 'Artificial Insemination Refresher', 'target' => 'Staff', 'attendees' => 12, 'location' => 'District Training Center', 'status' => 'Completed'],
    ['date' => '2026-03-25', 'topic' => 'Poultry Disease Management', 'target' => 'Farmers', 'attendees' => 40, 'location' => 'Mutur Range', 'status' => 'Completed'],
];

require_once '../../../includes/header.php';
?>

<link rel="stylesheet" href="../../../assets/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="../../../assets/css/buttons.bootstrap5.min.css">


        <div class="mb-4">
            <h4 class="fw-bold mb-0">Training & Extension Services</h4>
            <p class="text-muted small">Capacity building for veterinary staff and provincial livestock farmers</p>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 small fw-bold text-uppercase text-muted">Training Management</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <button class="btn btn-primary w-100 py-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#farmerTrainingModal">
                            <i class="bi bi-people fs-3"></i><br>
                            <span>Farmer Training</span>
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-dark w-100 py-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#staffTrainingModal">
                            <i class="bi bi-person-badge fs-3"></i><br>
                            <span>Staff Development</span>
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-success w-100 py-3 shadow-sm">
                            <i class="bi bi-calendar-check fs-3"></i><br>
                            <span>Training Calendar</span>
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-secondary w-100 py-3 shadow-sm">
                            <i class="bi bi-file-earmark-pdf fs-3"></i><br>
                            <span>Attendance Report</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm p-3 border-start border-primary border-4">
                    <div class="d-flex align-items-center">
                        <div class="display-6 me-3 text-primary"><i class="bi bi-person-video3"></i></div>
                        <div>
                            <h6 class="mb-0 text-muted small">Total Farmers Trained (YTD)</h6>
                            <h4 class="fw-bold mb-0">1,240</h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm p-3 border-start border-dark border-4">
                    <div class="d-flex align-items-center">
                        <div class="display-6 me-3 text-dark"><i class="bi bi-award"></i></div>
                        <div>
                            <h6 class="mb-0 text-muted small">Staff PD Hours Logged</h6>
                            <h4 class="fw-bold mb-0">340 Hours</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="bi bi-list-ul me-2"></i>Recent Training Activities</h6>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-light border">All</button>
                    <button class="btn btn-light border">Staff</button>
                    <button class="btn btn-light border">Farmers</button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light small text-uppercase">
                        <tr>
                            <th class="ps-4">Date</th>
                            <th>Topic / Program Name</th>
                            <th>Target Group</th>
                            <th>Location</th>
                            <th class="text-center">Attendees</th>
                            <th class="text-center">Status</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($training_logs as $log): ?>
                            <tr>
                                <td class="ps-4 small fw-bold"><?= date('d M, Y', strtotime($log['date'])) ?></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= $log['topic'] ?></div>
                                    <small class="text-muted">ID: TRN-2026-<?= rand(100, 999) ?></small>
                                </td>
                                <td>
                                    <span class="badge <?= $log['target'] == 'Staff' ? 'bg-dark' : 'bg-primary' ?> px-2">
                                        <?= $log['target'] ?>
                                    </span>
                                </td>
                                <td class="small"><i class="bi bi-geo-alt text-danger me-1"></i><?= $log['location'] ?></td>
                                <td class="text-center fw-bold"><?= $log['attendees'] ?></td>
                                <td class="text-center">
                                    <span class="badge rounded-pill <?= $log['status'] == 'Completed' ? 'bg-success' : 'bg-warning text-dark' ?>">
                                        <?= $log['status'] ?>
                                    </span>
                                </td>
                                <td class="text-end pe-4">
                                    <button class="btn btn-sm btn-light border" title="Attendance Sheet"><i class="bi bi-card-checklist"></i></button>
                                    <button class="btn btn-sm btn-light border" title="Edit"><i class="bi bi-pencil"></i></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Completed Farmer Training Courses (Integrated with Dairy Hub Module) -->
        <div class="card shadow-sm border-0 mt-4 mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-0 fw-bold text-dark">
                        <i class="bi bi-patch-check-fill text-success me-2"></i>Completed Farmer Training Courses
                    </h6>
                    <small class="text-muted">Centralized data integrated directly from Dairy Hub applications</small>
                </div>
                <span class="badge bg-success-subtle text-success px-3 py-2 border border-success-subtle">
                    <i class="bi bi-mortarboard me-1"></i><?= count($farmer_trainings) ?> Courses Logged
                </span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light small text-uppercase">
                        <tr>
                            <th class="ps-4">Completion Date</th>
                            <th>Farmer &amp; Farm Reg No</th>
                            <th>NIC Number</th>
                            <th>Course / Program Name</th>
                            <th>Conducting Institute</th>
                            <th class="text-center">Integration Source</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($farmer_trainings)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted small">
                                    <i class="bi bi-info-circle me-1"></i> No completed training courses recorded yet from Dairy Hub applications.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($farmer_trainings as $ft): ?>
                                <tr>
                                    <td class="ps-4 small fw-bold">
                                        <?= !empty($ft['completion_date']) && $ft['completion_date'] !== '0000-00-00' ? date('d M, Y', strtotime($ft['completion_date'])) : '<span class="text-muted">Completed</span>' ?>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($ft['farmer_name'] ?: 'Registered Farmer') ?></div>
                                        <small class="text-muted font-monospace"><i class="bi bi-qr-code me-1"></i><?= htmlspecialchars($ft['farm_reg_no']) ?></small>
                                    </td>
                                    <td class="small font-monospace"><?= htmlspecialchars($ft['nic'] ?: 'N/A') ?></td>
                                    <td>
                                        <div class="fw-semibold text-primary"><?= htmlspecialchars($ft['course_name']) ?></div>
                                    </td>
                                    <td class="small"><?= htmlspecialchars($ft['institute'] ?: 'DAPH') ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border px-2">
                                            <?= htmlspecialchars($ft['source']) ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge rounded-pill bg-success">
                                            <i class="bi bi-check2-circle me-1"></i>Completed
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
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