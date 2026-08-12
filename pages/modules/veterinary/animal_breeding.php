<?php
session_start();
require_once '../../../config/db_connect.php';

if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'], ['veterinary_surgeon', 'sms'])) {
    header("Location: ../../../index.php");
    exit();
}

$user_id = $_SESSION['user_id'] ?? null;
$range_id = $_SESSION['range_id'] ?? null;

$range_name = 'Your Range';
$district_name = 'Your District';

// Extract Range Name and District Name
if (!empty($range_id)) {
    $details_sql = "
        SELECT 
            vr.name AS range_name,
            d.name AS district_name
        FROM veterinary_ranges vr
        LEFT JOIN districts d ON vr.district_id = d.id
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
        }
        $details_query->close();
    }
}

$selected_year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));

// Calculate stats for current dashboard context
$total_ai = 0;
$total_pd = 0;
$total_calving = 0;

if (!empty($range_id)) {
    // 1. AI count
    $ai_stmt = $mysqli->prepare("SELECT COUNT(*) FROM breeding_ai_performance WHERE range_id = ? AND report_year = ?");
    if ($ai_stmt) {
        $ai_stmt->bind_param("ii", $range_id, $selected_year);
        $ai_stmt->execute();
        $ai_stmt->bind_result($total_ai);
        $ai_stmt->fetch();
        $ai_stmt->close();
    }

    // 2. PD count
    $pd_stmt = $mysqli->prepare("SELECT COUNT(*) FROM breeding_pd_performance WHERE range_id = ? AND report_year = ?");
    if ($pd_stmt) {
        $pd_stmt->bind_param("ii", $range_id, $selected_year);
        $pd_stmt->execute();
        $pd_stmt->bind_result($total_pd);
        $pd_stmt->fetch();
        $pd_stmt->close();
    }

    // 3. Calving count
    $calving_stmt = $mysqli->prepare("SELECT COUNT(*) FROM breeding_calving_performance WHERE range_id = ? AND report_year = ?");
    if ($calving_stmt) {
        $calving_stmt->bind_param("ii", $range_id, $selected_year);
        $calving_stmt->execute();
        $calving_stmt->bind_result($total_calving);
        $calving_stmt->fetch();
        $calving_stmt->close();
    }
}

require_once '../../../includes/header.php';
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="../../../assets/css/veterinary.css">



        <div class="mb-4 d-flex justify-content-between align-items-center">
            <div>
                <h2 class="h4 fw-bold mb-1" style="color: #370709;">Animal Breeding & Performance Dashboard</h2>
                <p class="text-muted small mb-0">Monitor Artificial Insemination, Pregnancy Diagnosis, and Calvings for <strong class="text-dark"><?= htmlspecialchars($range_name) ?></strong> (<?= htmlspecialchars($district_name) ?> District)</p>
            </div>
            
            <div class="d-flex align-items-center gap-2">
                <form method="GET" class="d-flex align-items-center gap-2">
                    <label class="small fw-bold text-muted mb-0">Year:</label>
                    <select name="year" class="form-select form-select-sm" onchange="this.form.submit()" style="width: 100px;">
                        <?php
                        $curr_year = intval(date('Y'));
                        for ($y = $curr_year - 5; $y <= $curr_year + 5; $y++) {
                            $sel = ($y === $selected_year) ? 'selected' : '';
                            echo "<option value=\"$y\" $sel>$y</option>";
                        }
                        ?>
                    </select>
                </form>
                <a href="monthly-annual-reports.php" class="btn btn-secondary shadow-sm text-nowrap">
                    <i class="bi bi-arrow-left me-2"></i>Back
                </a>
            </div>
        </div>

        <!-- STATS CARD GROUP -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3">
                <div class="card shadow-sm border-0 border-start border-primary border-4">
                    <div class="card-body py-3">
                        <span class="text-muted small text-uppercase fw-bold">Active Year</span>
                        <h4 class="mb-0 fw-bold math-numeric text-primary mt-1"><?= $selected_year ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card shadow-sm border-0 border-start border-success border-4">
                    <div class="card-body py-3">
                        <span class="text-muted small text-uppercase fw-bold">Total AI Performed</span>
                        <h4 class="mb-0 fw-bold math-numeric text-success mt-1"><?= number_format($total_ai) ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card shadow-sm border-0 border-start border-info border-4">
                    <div class="card-body py-3">
                        <span class="text-muted small text-uppercase fw-bold">Total PD Completed</span>
                        <h4 class="mb-0 fw-bold math-numeric text-info mt-1"><?= number_format($total_pd) ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card shadow-sm border-0 border-start border-warning border-4">
                    <div class="card-body py-3">
                        <span class="text-muted small text-uppercase fw-bold">Total Calvings Logged</span>
                        <h4 class="mb-0 fw-bold math-numeric text-warning mt-1"><?= number_format($total_calving) ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- THREE ACTION PAGES LINKS -->
        <div class="row g-4 mb-4">
            <div class="col-12 col-md-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body p-4 d-flex flex-column justify-content-between align-items-center text-center">
                        <div class="bg-light p-3 rounded-circle mb-3">
                            <i class="bi bi-activity text-primary display-5"></i>
                        </div>
                        <h5 class="fw-bold mb-2">Artificial Insemination</h5>
                        <p class="text-muted small mb-4">Record and manage Artificial Insemination (AI) activities: technicians, cow registry, semen codes and AI types.</p>
                        <a href="ai_performance.php?year=<?= $selected_year ?>" class="btn btn-primary btn-sm w-100">
                            <i class="bi bi-arrow-right-circle me-1"></i> Manage AI Records
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-12 col-md-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body p-4 d-flex flex-column justify-content-between align-items-center text-center">
                        <div class="bg-light p-3 rounded-circle mb-3">
                            <i class="bi bi-gender-female text-info display-5"></i>
                        </div>
                        <h5 class="fw-bold mb-2">Pregnancy Diagnosis</h5>
                        <p class="text-muted small mb-4">Register pregnancy diagnosis tests: test dates, pregnant/non-pregnant results, and linking with initial AI date.</p>
                        <a href="pd_performance.php?year=<?= $selected_year ?>" class="btn btn-info text-white btn-sm w-100">
                            <i class="bi bi-arrow-right-circle me-1"></i> Manage PD Records
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body p-4 d-flex flex-column justify-content-between align-items-center text-center">
                        <div class="bg-light p-3 rounded-circle mb-3">
                            <i class="bi bi-clipboard-plus text-warning display-5"></i>
                        </div>
                        <h5 class="fw-bold mb-2">Calving Performance</h5>
                        <p class="text-muted small mb-4">Track calf registration logs: mapping parent cow, semen code details, calving date, sex and calf ID tracking.</p>
                        <a href="calving_performance.php?year=<?= $selected_year ?>" class="btn btn-warning text-white btn-sm w-100">
                            <i class="bi bi-arrow-right-circle me-1"></i> Manage Calving Records
                        </a>
                    </div>
                </div>
            </div>
        </div>

    <?php require_once '../../../includes/footer.php'; ?>
