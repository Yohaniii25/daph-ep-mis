<?php
/**
 * pages/modules/veterinary/processors/get_range_summary_tab.php
 * AJAX endpoint for the Range Summary tabbed dashboard.
 * Returns JSON { success: bool, html: string } for each tab.
 */
session_start();
header('Content-Type: application/json');

// ── Auth ─────────────────────────────────────────────────────────────────────
if (!isset($_SESSION['logged_in']) || empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'html' => '<div class="alert alert-danger">Unauthorized.</div>']);
    exit();
}

require_once __DIR__ . '/../../../../config/db_connect.php';

/** @var mysqli $mysqli */
global $mysqli;

$user_id   = (int)$_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? '';
$tab       = trim($_GET['tab'] ?? 'overview');

// ── Resolve range_id (safe: always pulled from session/DB, never blindly from GET) ─
$range_id = null;
$q = $mysqli->prepare("SELECT range_id, district_id FROM users WHERE id = ?");
if ($q) {
    $q->bind_param("i", $user_id);
    $q->execute();
    $row = $q->get_result()->fetch_assoc();
    $q->close();
    $range_id   = !empty($row['range_id'])   ? (int)$row['range_id']   : null;
    $district_id = !empty($row['district_id']) ? (int)$row['district_id'] : null;
}

// Supervisory roles can also pass range_id via GET (same logic as range_details.php)
$sup_roles = ['district_dd','deputy_director_district','administrator','provincial_director','deputy_director_hq_1','deputy_director_hq_2'];
if (in_array($user_role, $sup_roles) && isset($_GET['range_id']) && is_numeric($_GET['range_id'])) {
    $range_id = (int)$_GET['range_id'];
}

if (empty($range_id)) {
    echo json_encode(['success' => false, 'html' => '<div class="alert alert-warning p-4">No range assigned to your account.</div>']);
    exit();
}

// ── Helper: safe COUNT query ──────────────────────────────────────────────────
function safeCount($mysqli, string $sql, string $types, ...$params): int {
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) return 0;
    if ($types) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int)($res['cnt'] ?? 0);
}

function safeRows($mysqli, string $sql, string $types, ...$params): array {
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) return [];
    if ($types) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $data;
}

// ── Stat card HTML helper ─────────────────────────────────────────────────────
function statCard(string $icon, string $label, $value, string $color = '#370709', string $sub = ''): string {
    $v = is_numeric($value) ? number_format((float)$value) : htmlspecialchars((string)$value);
    $subHtml = $sub ? "<div class=\"text-muted small mt-1\">" . htmlspecialchars($sub) . "</div>" : '';
    return "
    <div class=\"col-6 col-md-3\">
        <div class=\"card border-0 shadow-sm h-100\" style=\"border-left: 4px solid {$color} !important;\">
            <div class=\"card-body py-3 px-3\">
                <div class=\"d-flex align-items-center gap-2 mb-1\">
                    <i class=\"bi {$icon}\" style=\"color:{$color}; font-size:1.3rem;\"></i>
                    <span class=\"text-muted small fw-semibold text-uppercase\" style=\"font-size:0.72rem;\">{$label}</span>
                </div>
                <div class=\"fw-bold\" style=\"font-size:1.6rem; color:{$color};\">{$v}</div>
                {$subHtml}
            </div>
        </div>
    </div>";
}

function sectionHeader(string $icon, string $title, string $sub = ''): string {
    $s = $sub ? "<p class=\"text-muted small mb-0\">{$sub}</p>" : '';
    return "
    <div class=\"d-flex align-items-center gap-2 mb-3 pb-2\" style=\"border-bottom:2px solid #f0f0f0;\">
        <i class=\"bi {$icon} fs-5\" style=\"color:#820100;\"></i>
        <div>
            <h6 class=\"fw-bold mb-0\" style=\"color:#370709;\">{$title}</h6>
            {$s}
        </div>
    </div>";
}

ob_start();
$current_year = (int)date('Y');

switch ($tab) {

    // ═══════════════════════════════════════════════════════════════════════════
    // TAB 1 — OVERVIEW
    // ═══════════════════════════════════════════════════════════════════════════
    case 'overview':
        // Fetch range + map
        $info = $mysqli->prepare("
            SELECT vr.id, vr.name AS range_name, d.name AS district_name, vrm.iframe_url
            FROM veterinary_ranges vr
            LEFT JOIN districts d ON vr.district_id = d.id
            LEFT JOIN veterinary_range_maps vrm ON vr.id = vrm.range_id
            WHERE vr.id = ?
        ");
        $info->bind_param("i", $range_id);
        $info->execute();
        $rd = $info->get_result()->fetch_assoc();
        $info->close();
        $range_name   = htmlspecialchars($rd['range_name']   ?? 'Unknown');
        $district_name = htmlspecialchars($rd['district_name'] ?? 'Unknown');
        $iframe_url   = $rd['iframe_url'] ?? '';

        // Quick cross-table counts
        $c_health   = safeCount($mysqli, "SELECT COUNT(*) cnt FROM animal_health_records WHERE range_id=?", "i", $range_id);
        $c_ai       = safeCount($mysqli, "SELECT COUNT(*) cnt FROM breeding_ai_performance WHERE range_id=?", "i", $range_id);
        $c_vaccine  = safeCount($mysqli, "SELECT COUNT(*) cnt FROM monthly_vaccine_balances WHERE range_id=?", "i", $range_id);
        $c_counter  = safeCount($mysqli, "SELECT COUNT(*) cnt FROM counterfoil_assets WHERE range_id=? AND is_active=1", "i", $range_id);
        $c_vehicle  = safeCount($mysqli, "SELECT COUNT(*) cnt FROM registered_vehicles WHERE range_id=? AND is_active=1", "i", $range_id);
        $c_slaughter= safeCount($mysqli, "SELECT COUNT(*) cnt FROM slaughter_statistics WHERE range_id=?", "i", $range_id);
        $c_health_cert = safeCount($mysqli, "SELECT COUNT(*) cnt FROM health_certificate_issues WHERE range_id=?", "i", $range_id);
        $c_human_pop = safeCount($mysqli, "SELECT COUNT(*) cnt FROM human_populations WHERE range_id=?", "i", $range_id);
        ?>
        <div class="row g-4">
            <!-- Profile card -->
            <div class="col-12 col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header border-0 pt-3 pb-2 px-4" style="background: linear-gradient(135deg,#820100,#370709);">
                        <h6 class="text-white fw-bold mb-0"><i class="bi bi-geo-fill me-2"></i>Range Profile</h6>
                    </div>
                    <div class="card-body px-4 py-3">
                        <table class="table table-borderless mb-0 small align-middle">
                            <tr><td class="text-muted fw-semibold ps-0" style="width:40%">Range ID</td><td class="font-monospace fw-bold">#<?= $range_id ?></td></tr>
                            <tr><td class="text-muted fw-semibold ps-0">Range Name</td><td class="fw-bold" style="color:#370709"><?= $range_name ?></td></tr>
                            <tr><td class="text-muted fw-semibold ps-0">District</td><td><?= $district_name ?></td></tr>
                            <tr><td class="text-muted fw-semibold ps-0">Role</td><td><?= ucwords(str_replace('_',' ',$user_role)) ?></td></tr>
                            <tr><td class="text-muted fw-semibold ps-0">Report Year</td><td class="fw-bold"><?= $current_year ?></td></tr>
                        </table>
                        <div class="mt-3">
                            <a href="range_details.php" class="btn btn-sm w-100 text-white fw-semibold" style="background:#370709;">
                                <i class="bi bi-grid-3x3-gap-fill me-1"></i> Open Quick Actions Launcher
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Map -->
            <div class="col-12 col-lg-8">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header border-0 pt-3 pb-2 px-4" style="background:#f8f9fa;">
                        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-map-fill me-2" style="color:#820100;"></i>Range Map View</h6>
                    </div>
                    <div class="card-body p-2">
                        <?php if (!empty($iframe_url) && filter_var($iframe_url, FILTER_VALIDATE_URL)): ?>
                            <iframe src="<?= htmlspecialchars($iframe_url, ENT_QUOTES) ?>" width="100%" height="280" style="border:0;border-radius:6px;" allowfullscreen loading="lazy"></iframe>
                        <?php else: ?>
                            <div class="d-flex flex-column align-items-center justify-content-center bg-light rounded-3" style="min-height:280px;">
                                <i class="bi bi-map text-secondary" style="font-size:2.5rem;"></i>
                                <span class="text-muted small mt-2">No map URL configured for this range.</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Quick Stats Strip -->
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header border-0 pt-3 pb-2 px-4" style="background:#f8f9fa;">
                        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-bar-chart-fill me-2" style="color:#820100;"></i>Range-Wide Data Snapshot</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <?= statCard('bi-activity', 'Health Records', $c_health, '#820100') ?>
                            <?= statCard('bi-heart-pulse', 'AI Performance', $c_ai, '#1565c0') ?>
                            <?= statCard('bi-capsule', 'Vaccine Entries', $c_vaccine, '#2e7d32') ?>
                            <?= statCard('bi-file-earmark-text-fill', 'Counterfoil Books', $c_counter, '#e65100') ?>
                            <?= statCard('bi-truck-front-fill', 'Registered Vehicles', $c_vehicle, '#455a64') ?>
                            <?= statCard('bi-scissors', 'Slaughter Records', $c_slaughter, '#ad1457') ?>
                            <?= statCard('bi-patch-check-fill', 'Health Certificates', $c_health_cert, '#00838f') ?>
                            <?= statCard('bi-people-fill', 'Population Entries', $c_human_pop, '#283593') ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        break;

    // ═══════════════════════════════════════════════════════════════════════════
    // TAB 2 — POPULATION
    // ═══════════════════════════════════════════════════════════════════════════
    case 'population':
        $human_rows = safeRows($mysqli,
            "SELECT year, ethnicity, SUM(male) AS male, SUM(female) AS female, SUM(male+female) AS total, SUM(households) AS households
             FROM human_populations WHERE range_id=? GROUP BY year, ethnicity ORDER BY year DESC, ethnicity ASC",
            "i", $range_id);

        $animal_rows = safeRows($mysqli,
            "SELECT year, animal_type, SUM(total_count) AS total FROM animal_populations WHERE range_id=? GROUP BY year, animal_type ORDER BY year DESC, animal_type ASC",
            "i", $range_id);

        // Totals
        $total_human  = safeCount($mysqli, "SELECT SUM(male+female) cnt FROM human_populations WHERE range_id=?", "i", $range_id);
        $total_animals = safeCount($mysqli, "SELECT SUM(total_count) cnt FROM animal_populations WHERE range_id=?", "i", $range_id);
        $total_hh     = safeCount($mysqli, "SELECT SUM(households) cnt FROM human_populations WHERE range_id=?", "i", $range_id);
        $distinct_years = safeCount($mysqli, "SELECT COUNT(DISTINCT year) cnt FROM human_populations WHERE range_id=?", "i", $range_id);
        ?>
        <div class="row g-3 mb-4">
            <?= statCard('bi-people-fill',   'Total Human Population', $total_human,  '#370709') ?>
            <?= statCard('bi-house-fill',     'Total Households',       $total_hh,     '#1565c0') ?>
            <?= statCard('bi-bug-fill',       'Total Livestock Count',  $total_animals,'#2e7d32') ?>
            <?= statCard('bi-calendar3',      'Years Recorded',         $distinct_years,'#ad1457') ?>
        </div>

        <div class="row g-4">
            <!-- Human Population -->
            <div class="col-12 col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header border-0 px-4 pt-3 pb-2" style="background:#f8f9fa;">
                        <?= sectionHeader('bi-people-fill', 'Human Population by Ethnicity', 'Aggregated across all recorded years') ?>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($human_rows)): ?>
                            <p class="text-muted text-center py-5 small">No population data recorded yet.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle mb-0">
                                    <thead class="table-light"><tr>
                                        <th>Year</th><th>Ethnicity</th>
                                        <th class="text-end">Male</th><th class="text-end">Female</th>
                                        <th class="text-end">Total</th><th class="text-end">Households</th>
                                    </tr></thead>
                                    <tbody>
                                    <?php foreach ($human_rows as $r): ?>
                                        <tr>
                                            <td class="fw-bold"><?= htmlspecialchars($r['year']) ?></td>
                                            <td><span class="badge bg-secondary"><?= htmlspecialchars($r['ethnicity']) ?></span></td>
                                            <td class="text-end text-primary"><?= number_format($r['male']) ?></td>
                                            <td class="text-end text-danger"><?= number_format($r['female']) ?></td>
                                            <td class="text-end fw-bold" style="color:#370709"><?= number_format($r['total']) ?></td>
                                            <td class="text-end text-success"><?= number_format($r['households']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <!-- Animal Population -->
            <div class="col-12 col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header border-0 px-4 pt-3 pb-2" style="background:#f8f9fa;">
                        <?= sectionHeader('bi-bug-fill', 'Animal Population by Species', 'Aggregated across all recorded years') ?>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($animal_rows)): ?>
                            <p class="text-muted text-center py-5 small">No animal population data recorded yet.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle mb-0">
                                    <thead class="table-light"><tr>
                                        <th>Year</th><th>Animal Type</th><th class="text-end">Total Count</th>
                                    </tr></thead>
                                    <tbody>
                                    <?php foreach ($animal_rows as $r): ?>
                                        <tr>
                                            <td class="fw-bold"><?= htmlspecialchars($r['year']) ?></td>
                                            <td><?= htmlspecialchars($r['animal_type']) ?></td>
                                            <td class="text-end fw-bold text-success"><?= number_format($r['total']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
        break;

    // ═══════════════════════════════════════════════════════════════════════════
    // TAB 3 — HEALTH & BREEDING
    // ═══════════════════════════════════════════════════════════════════════════
    case 'health':
        $total_health  = safeCount($mysqli, "SELECT COUNT(*) cnt FROM animal_health_records WHERE range_id=?", "i", $range_id);
        $draft_health  = safeCount($mysqli, "SELECT COUNT(*) cnt FROM animal_health_records WHERE range_id=? AND report_status='Draft'", "i", $range_id);
        $month_health  = safeCount($mysqli, "SELECT COUNT(*) cnt FROM animal_health_records WHERE range_id=? AND MONTH(date)=MONTH(CURRENT_DATE()) AND YEAR(date)=YEAR(CURRENT_DATE())", "i", $range_id);

        $ai_total  = safeCount($mysqli, "SELECT COUNT(*) cnt FROM breeding_ai_performance WHERE range_id=?", "i", $range_id);
        $ai_year   = safeCount($mysqli, "SELECT COUNT(*) cnt FROM breeding_ai_performance WHERE range_id=? AND report_year=?", "ii", $range_id, $current_year);
        $pd_total  = safeCount($mysqli, "SELECT COUNT(*) cnt FROM breeding_pd_performance WHERE range_id=?", "i", $range_id);
        $calv_total= safeCount($mysqli, "SELECT COUNT(*) cnt FROM breeding_calving_performance WHERE range_id=?", "i", $range_id);

        // Top diseases
        $diseases = safeRows($mysqli,
            "SELECT disease_name, COUNT(*) AS cnt FROM animal_health_records WHERE range_id=? GROUP BY disease_name ORDER BY cnt DESC LIMIT 8",
            "i", $range_id);

        // Recent health records
        $recent_health = safeRows($mysqli,
            "SELECT date, farmer_reg_no, disease_name, occurrence_count, report_status FROM animal_health_records WHERE range_id=? ORDER BY date DESC, id DESC LIMIT 10",
            "i", $range_id);

        // AI performance by type
        $ai_by_type = safeRows($mysqli,
            "SELECT ai_type, COUNT(*) AS cnt FROM breeding_ai_performance WHERE range_id=? GROUP BY ai_type ORDER BY cnt DESC",
            "i", $range_id);
        ?>
        <div class="row g-3 mb-4">
            <?= statCard('bi-activity', 'Total Health Records', $total_health, '#820100') ?>
            <?= statCard('bi-pencil-square', 'Draft Records', $draft_health, '#b08723') ?>
            <?= statCard('bi-calendar-check', 'This Month', $month_health, '#2e7d32') ?>
            <?= statCard('bi-heart-pulse', 'AI Performances', $ai_total, '#1565c0') ?>
            <?= statCard('bi-clipboard-data', "AI This Year ({$current_year})", $ai_year, '#00838f') ?>
            <?= statCard('bi-star-fill', 'PD Performances', $pd_total, '#ad1457') ?>
            <?= statCard('bi-baby', 'Calving Records', $calv_total, '#e65100') ?>
        </div>

        <div class="row g-4">
            <!-- Disease Frequency -->
            <div class="col-12 col-lg-5">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header border-0 px-4 pt-3 pb-0" style="background:#f8f9fa;">
                        <?= sectionHeader('bi-virus', 'Most Reported Diseases', 'All-time frequency ranking') ?>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($diseases)): ?>
                            <p class="text-muted text-center py-4 small">No disease records found.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle mb-0">
                                    <thead class="table-light"><tr><th>#</th><th>Disease</th><th class="text-end">Count</th></tr></thead>
                                    <tbody>
                                    <?php foreach ($diseases as $i => $d): ?>
                                        <tr>
                                            <td class="text-muted small"><?= $i+1 ?></td>
                                            <td><?= htmlspecialchars($d['disease_name']) ?></td>
                                            <td class="text-end fw-bold" style="color:#820100"><?= $d['cnt'] ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- AI by Type -->
            <div class="col-12 col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header border-0 px-4 pt-3 pb-0" style="background:#f8f9fa;">
                        <?= sectionHeader('bi-heart-pulse', 'AI by Type') ?>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($ai_by_type)): ?>
                            <p class="text-muted text-center py-4 small">No AI records.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0">
                                    <thead class="table-light"><tr><th>AI Type</th><th class="text-end">Count</th></tr></thead>
                                    <tbody>
                                    <?php foreach ($ai_by_type as $a): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($a['ai_type']) ?></td>
                                            <td class="text-end fw-bold text-primary"><?= $a['cnt'] ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Recent Health Records -->
            <div class="col-12 col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header border-0 px-4 pt-3 pb-0" style="background:#f8f9fa;">
                        <?= sectionHeader('bi-clock-history', 'Recent Health Records', 'Latest 10 entries') ?>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($recent_health)): ?>
                            <p class="text-muted text-center py-4 small">No records found.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0">
                                    <thead class="table-light"><tr><th>Date</th><th>Disease</th><th>Status</th></tr></thead>
                                    <tbody>
                                    <?php foreach ($recent_health as $r): ?>
                                        <tr>
                                            <td class="small"><?= htmlspecialchars($r['date']) ?></td>
                                            <td class="small"><?= htmlspecialchars($r['disease_name']) ?></td>
                                            <td>
                                                <?php $st = $r['report_status']; ?>
                                                <span class="badge bg-<?= $st === 'Draft' ? 'warning text-dark' : 'success' ?> small">
                                                    <?= htmlspecialchars($st) ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
        break;

    // ═══════════════════════════════════════════════════════════════════════════
    // TAB 4 — VACCINATION & DRUGS
    // ═══════════════════════════════════════════════════════════════════════════
    case 'vaccination':
        $vac_total   = safeCount($mysqli, "SELECT COUNT(*) cnt FROM monthly_vaccine_balances WHERE range_id=?", "i", $range_id);
        $vac_year    = safeCount($mysqli, "SELECT COUNT(*) cnt FROM monthly_vaccine_balances WHERE range_id=? AND report_year=?", "ii", $range_id, $current_year);
        $drug_total  = safeCount($mysqli, "SELECT COUNT(*) cnt FROM drug_records WHERE range_id=?", "i", $range_id);
        $drug_types  = safeCount($mysqli, "SELECT COUNT(DISTINCT drug_type_id) cnt FROM drug_records WHERE range_id=?", "i", $range_id);

        // Vaccine usage by vaccine type
        $vac_by_type = safeRows($mysqli,
            "SELECT vt.vaccine_name, SUM(mvb.doses_used) AS used, SUM(mvb.doses_received) AS received
             FROM monthly_vaccine_balances mvb
             LEFT JOIN vaccine_types vt ON mvb.vaccine_type_id = vt.id
             WHERE mvb.range_id=?
             GROUP BY vt.vaccine_name ORDER BY used DESC LIMIT 10",
            "i", $range_id);

        // Drug usage
        $drug_by_type = safeRows($mysqli,
            "SELECT dt.drug_name, COUNT(dr.id) AS records, SUM(dr.quantity_used) AS used
             FROM drug_records dr
             LEFT JOIN drug_types dt ON dr.drug_type_id = dt.id
             WHERE dr.range_id=?
             GROUP BY dt.drug_name ORDER BY used DESC LIMIT 10",
            "i", $range_id);
        ?>
        <div class="row g-3 mb-4">
            <?= statCard('bi-capsule-pill', 'Vaccine Entries (Total)', $vac_total, '#2e7d32') ?>
            <?= statCard('bi-calendar3', "Vaccine Entries ({$current_year})", $vac_year, '#00838f') ?>
            <?= statCard('bi-prescription2', 'Drug Records', $drug_total, '#820100') ?>
            <?= statCard('bi-grid-3x3-gap', 'Drug Types Used', $drug_types, '#1565c0') ?>
        </div>
        <div class="row g-4">
            <div class="col-12 col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header border-0 px-4 pt-3 pb-0" style="background:#f8f9fa;">
                        <?= sectionHeader('bi-capsule-pill', 'Vaccine Usage by Type', 'Total doses across all years') ?>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($vac_by_type)): ?>
                            <p class="text-muted text-center py-4 small">No vaccine records found.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0">
                                    <thead class="table-light"><tr><th>Vaccine</th><th class="text-end">Received</th><th class="text-end">Used</th></tr></thead>
                                    <tbody>
                                    <?php foreach ($vac_by_type as $v): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($v['vaccine_name'] ?? 'Unknown') ?></td>
                                            <td class="text-end text-success"><?= number_format((float)$v['received']) ?></td>
                                            <td class="text-end fw-bold" style="color:#820100"><?= number_format((float)$v['used']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header border-0 px-4 pt-3 pb-0" style="background:#f8f9fa;">
                        <?= sectionHeader('bi-prescription2', 'Drug Usage by Type', 'Top 10 most used drugs') ?>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($drug_by_type)): ?>
                            <p class="text-muted text-center py-4 small">No drug records found.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0">
                                    <thead class="table-light"><tr><th>Drug</th><th class="text-end">Records</th><th class="text-end">Qty Used</th></tr></thead>
                                    <tbody>
                                    <?php foreach ($drug_by_type as $d): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($d['drug_name'] ?? 'Unknown') ?></td>
                                            <td class="text-end"><?= number_format((float)$d['records']) ?></td>
                                            <td class="text-end fw-bold text-danger"><?= number_format((float)$d['used']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
        break;

    // ═══════════════════════════════════════════════════════════════════════════
    // TAB 5 — ANNUAL RETURNS
    // ═══════════════════════════════════════════════════════════════════════════
    case 'returns':
        $c_prod   = safeCount($mysqli, "SELECT COUNT(*) cnt FROM annual_production_levels WHERE range_id=?", "i", $range_id);
        $c_past   = safeCount($mysqli, "SELECT COUNT(*) cnt FROM annual_pasture_fodder_lands WHERE range_id=?", "i", $range_id);
        $c_yield  = safeCount($mysqli, "SELECT COUNT(*) cnt FROM annual_pasture_yields WHERE range_id=?", "i", $range_id);
        $c_milk_c = safeCount($mysqli, "SELECT COUNT(*) cnt FROM annual_milk_collecting_centers WHERE range_id=?", "i", $range_id);
        $c_milk_p = safeCount($mysqli, "SELECT COUNT(*) cnt FROM annual_milk_processing_centers WHERE range_id=?", "i", $range_id);
        $c_milk_s = safeCount($mysqli, "SELECT COUNT(*) cnt FROM annual_milk_product_sales_centers WHERE range_id=?", "i", $range_id);
        $c_soc    = safeCount($mysqli, "SELECT COUNT(*) cnt FROM annual_livestock_societies WHERE range_id=?", "i", $range_id);
        $c_feed   = safeCount($mysqli, "SELECT COUNT(*) cnt FROM annual_feed_production WHERE range_id=?", "i", $range_id);

        // Yearly production trend
        $prod_trend = safeRows($mysqli,
            "SELECT report_year, COUNT(*) AS records FROM annual_production_levels WHERE range_id=? GROUP BY report_year ORDER BY report_year DESC LIMIT 6",
            "i", $range_id);

        // Milk collecting summary by year
        $milk_summary = safeRows($mysqli,
            "SELECT report_year, COUNT(*) AS centers, SUM(total_milk_collected_litres) AS milk_litres
             FROM annual_milk_collecting_centers WHERE range_id=? GROUP BY report_year ORDER BY report_year DESC LIMIT 6",
            "i", $range_id);
        ?>
        <div class="row g-3 mb-4">
            <?= statCard('bi-graph-up-arrow', 'Production Level Records', $c_prod, '#820100') ?>
            <?= statCard('bi-tree-fill', 'Pasture Land Entries', $c_past, '#2e7d32') ?>
            <?= statCard('bi-water', 'Pasture Yield Entries', $c_yield, '#1565c0') ?>
            <?= statCard('bi-bucket-fill', 'Milk Collecting Records', $c_milk_c, '#b08723') ?>
            <?= statCard('bi-gear-wide-connected', 'Milk Processing Records', $c_milk_p, '#455a64') ?>
            <?= statCard('bi-shop', 'Milk Sales Records', $c_milk_s, '#00838f') ?>
            <?= statCard('bi-heart-fill', 'Livestock Societies', $c_soc, '#ad1457') ?>
            <?= statCard('bi-prescription2', 'Feed Production Records', $c_feed, '#e65100') ?>
        </div>
        <div class="row g-4">
            <div class="col-12 col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header border-0 px-4 pt-3 pb-0" style="background:#f8f9fa;">
                        <?= sectionHeader('bi-graph-up-arrow', 'Production Level — Year Trend') ?>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($prod_trend)): ?>
                            <p class="text-muted text-center py-4 small">No production level data found.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0">
                                    <thead class="table-light"><tr><th>Year</th><th class="text-end">Records Count</th></tr></thead>
                                    <tbody>
                                    <?php foreach ($prod_trend as $pt): ?>
                                        <tr>
                                            <td class="fw-bold"><?= htmlspecialchars($pt['report_year']) ?></td>
                                            <td class="text-end fw-bold" style="color:#820100"><?= $pt['records'] ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header border-0 px-4 pt-3 pb-0" style="background:#f8f9fa;">
                        <?= sectionHeader('bi-bucket-fill', 'Milk Collecting Centers Summary', 'Total litres collected by year') ?>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($milk_summary)): ?>
                            <p class="text-muted text-center py-4 small">No milk collecting data found.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0">
                                    <thead class="table-light"><tr><th>Year</th><th class="text-end">Centers</th><th class="text-end">Total (Litres)</th></tr></thead>
                                    <tbody>
                                    <?php foreach ($milk_summary as $ms): ?>
                                        <tr>
                                            <td class="fw-bold"><?= htmlspecialchars($ms['report_year']) ?></td>
                                            <td class="text-end"><?= $ms['centers'] ?></td>
                                            <td class="text-end fw-bold text-success"><?= number_format((float)$ms['milk_litres'], 1) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
        break;

    // ═══════════════════════════════════════════════════════════════════════════
    // TAB 6 — ASSETS & INVENTORY
    // ═══════════════════════════════════════════════════════════════════════════
    case 'assets':
        $c_counter  = safeCount($mysqli, "SELECT COUNT(*) cnt FROM counterfoil_assets WHERE range_id=? AND is_active=1", "i", $range_id);
        $c_furn     = safeCount($mysqli, "SELECT COUNT(*) cnt FROM furniture_assets WHERE range_id=? AND is_active=1", "i", $range_id);
        $c_instr    = safeCount($mysqli, "SELECT COUNT(*) cnt FROM instrument_assets WHERE range_id=? AND is_active=1", "i", $range_id);
        $c_mach     = safeCount($mysqli, "SELECT COUNT(*) cnt FROM machinery_assets WHERE range_id=? AND is_active=1", "i", $range_id);
        $c_vehicle  = safeCount($mysqli, "SELECT COUNT(*) cnt FROM registered_vehicles WHERE range_id=? AND is_active=1", "i", $range_id);

        // Counterfoil by type
        $counterfoil_types = safeRows($mysqli,
            "SELECT counterfoil_type, COUNT(*) AS cnt, SUM(available_quantity) AS qty FROM counterfoil_assets WHERE range_id=? AND is_active=1 GROUP BY counterfoil_type ORDER BY cnt DESC LIMIT 12",
            "i", $range_id);

        // Vehicles by condition
        $vehicle_cond = safeRows($mysqli,
            "SELECT current_condition, COUNT(*) AS cnt FROM registered_vehicles WHERE range_id=? AND is_active=1 GROUP BY current_condition ORDER BY cnt DESC",
            "i", $range_id);

        // Furniture by condition
        $furn_cond = safeRows($mysqli,
            "SELECT current_condition, COUNT(*) AS cnt FROM furniture_assets WHERE range_id=? AND is_active=1 GROUP BY current_condition ORDER BY cnt DESC",
            "i", $range_id);
        ?>
        <div class="row g-3 mb-4">
            <?= statCard('bi-file-earmark-text-fill', 'Counterfoil Books', $c_counter, '#e65100') ?>
            <?= statCard('bi-lamp-fill', 'Furniture Items', $c_furn, '#455a64') ?>
            <?= statCard('bi-tools', 'Instruments', $c_instr, '#00838f') ?>
            <?= statCard('bi-gear-fill', 'Machinery Items', $c_mach, '#1565c0') ?>
            <?= statCard('bi-truck-front-fill', 'Registered Vehicles', $c_vehicle, '#820100') ?>
        </div>
        <div class="row g-4">
            <!-- Counterfoil by type -->
            <div class="col-12 col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header border-0 px-4 pt-3 pb-0" style="background:#f8f9fa;">
                        <?= sectionHeader('bi-file-earmark-text-fill', 'Counterfoil Books by Type') ?>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($counterfoil_types)): ?>
                            <p class="text-muted text-center py-4 small">No counterfoil records found.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0">
                                    <thead class="table-light"><tr><th>Type</th><th class="text-end">Books</th><th class="text-end">Qty Available</th></tr></thead>
                                    <tbody>
                                    <?php foreach ($counterfoil_types as $ct): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($ct['counterfoil_type']) ?></td>
                                            <td class="text-end"><?= $ct['cnt'] ?></td>
                                            <td class="text-end fw-bold text-success"><?= number_format((float)$ct['qty']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <!-- Vehicle condition + Furniture condition -->
            <div class="col-12 col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header border-0 px-4 pt-3 pb-0" style="background:#f8f9fa;">
                        <?= sectionHeader('bi-truck-front-fill', 'Vehicles by Condition') ?>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($vehicle_cond)): ?>
                            <p class="text-muted text-center py-4 small">No vehicle data.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0">
                                    <thead class="table-light"><tr><th>Condition</th><th class="text-end">Count</th></tr></thead>
                                    <tbody>
                                    <?php foreach ($vehicle_cond as $vc):
                                        $bc = match(true) {
                                            str_contains(strtolower($vc['current_condition']), 'good') => 'success',
                                            str_contains(strtolower($vc['current_condition']), 'fair') => 'warning',
                                            str_contains(strtolower($vc['current_condition']), 'poor') || str_contains(strtolower($vc['current_condition']), 'damage') => 'danger',
                                            default => 'secondary'
                                        };
                                    ?>
                                        <tr>
                                            <td><span class="badge bg-<?= $bc ?>"><?= htmlspecialchars($vc['current_condition']) ?></span></td>
                                            <td class="text-end fw-bold"><?= $vc['cnt'] ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header border-0 px-4 pt-3 pb-0" style="background:#f8f9fa;">
                        <?= sectionHeader('bi-lamp-fill', 'Furniture by Condition') ?>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($furn_cond)): ?>
                            <p class="text-muted text-center py-4 small">No furniture data.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0">
                                    <thead class="table-light"><tr><th>Condition</th><th class="text-end">Count</th></tr></thead>
                                    <tbody>
                                    <?php foreach ($furn_cond as $fc):
                                        $bc = match(true) {
                                            str_contains(strtolower($fc['current_condition']), 'good') => 'success',
                                            str_contains(strtolower($fc['current_condition']), 'fair') => 'warning',
                                            str_contains(strtolower($fc['current_condition']), 'poor') || str_contains(strtolower($fc['current_condition']), 'damage') => 'danger',
                                            default => 'secondary'
                                        };
                                    ?>
                                        <tr>
                                            <td><span class="badge bg-<?= $bc ?>"><?= htmlspecialchars($fc['current_condition']) ?></span></td>
                                            <td class="text-end fw-bold"><?= $fc['cnt'] ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
        break;

    // ═══════════════════════════════════════════════════════════════════════════
    // TAB 7 — REGULATORY & REPORTS
    // ═══════════════════════════════════════════════════════════════════════════
    case 'regulatory':
        $c_reg    = safeCount($mysqli, "SELECT COUNT(*) cnt FROM regulatory_records WHERE range_id=?", "i", $range_id);
        $c_slaughter = safeCount($mysqli, "SELECT COUNT(*) cnt FROM slaughter_statistics WHERE range_id=?", "i", $range_id);
        $c_hcert  = safeCount($mysqli, "SELECT COUNT(*) cnt FROM health_certificate_issues WHERE range_id=?", "i", $range_id);
        $c_ear    = safeCount($mysqli, "SELECT COUNT(*) cnt FROM ear_tag_usage WHERE range_id=?", "i", $range_id);

        $slaughter_by_species = safeRows($mysqli,
            "SELECT species, SUM(animal_count) AS total_animals, SUM(total_weight_kg) AS total_weight
             FROM slaughter_statistics WHERE range_id=? GROUP BY species ORDER BY total_animals DESC",
            "i", $range_id);

        $hcert_recent = safeRows($mysqli,
            "SELECT issue_date, certificate_no, farmer_name, animal_type, purpose FROM health_certificate_issues WHERE range_id=? ORDER BY issue_date DESC, id DESC LIMIT 10",
            "i", $range_id);

        $reg_by_type = safeRows($mysqli,
            "SELECT record_type, COUNT(*) AS cnt FROM regulatory_records WHERE range_id=? GROUP BY record_type ORDER BY cnt DESC LIMIT 8",
            "i", $range_id);
        ?>
        <div class="row g-3 mb-4">
            <?= statCard('bi-shield-check', 'Regulatory Records', $c_reg, '#820100') ?>
            <?= statCard('bi-scissors', 'Slaughter Records', $c_slaughter, '#ad1457') ?>
            <?= statCard('bi-patch-check-fill', 'Health Certificates', $c_hcert, '#00838f') ?>
            <?= statCard('bi-tag-fill', 'Ear Tag Records', $c_ear, '#1565c0') ?>
        </div>
        <div class="row g-4">
            <!-- Slaughter by species -->
            <div class="col-12 col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header border-0 px-4 pt-3 pb-0" style="background:#f8f9fa;">
                        <?= sectionHeader('bi-scissors', 'Slaughter by Species', 'All-time totals') ?>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($slaughter_by_species)): ?>
                            <p class="text-muted text-center py-4 small">No slaughter records found.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0">
                                    <thead class="table-light"><tr><th>Species</th><th class="text-end">Animals</th><th class="text-end">Weight (kg)</th></tr></thead>
                                    <tbody>
                                    <?php foreach ($slaughter_by_species as $s): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($s['species']) ?></td>
                                            <td class="text-end fw-bold"><?= number_format((float)$s['total_animals']) ?></td>
                                            <td class="text-end text-muted"><?= number_format((float)$s['total_weight'], 1) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <!-- Regulatory by type -->
            <div class="col-12 col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header border-0 px-4 pt-3 pb-0" style="background:#f8f9fa;">
                        <?= sectionHeader('bi-shield-check', 'Regulatory Records by Type') ?>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($reg_by_type)): ?>
                            <p class="text-muted text-center py-4 small">No regulatory records found.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0">
                                    <thead class="table-light"><tr><th>Record Type</th><th class="text-end">Count</th></tr></thead>
                                    <tbody>
                                    <?php foreach ($reg_by_type as $rt): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($rt['record_type']) ?></td>
                                            <td class="text-end fw-bold" style="color:#820100"><?= $rt['cnt'] ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <!-- Recent Health Certificates -->
            <div class="col-12 col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header border-0 px-4 pt-3 pb-0" style="background:#f8f9fa;">
                        <?= sectionHeader('bi-patch-check-fill', 'Recent Health Certificates', 'Latest 10 issued') ?>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($hcert_recent)): ?>
                            <p class="text-muted text-center py-4 small">No health certificate records found.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0">
                                    <thead class="table-light"><tr><th>Date</th><th>Animal</th><th>Purpose</th></tr></thead>
                                    <tbody>
                                    <?php foreach ($hcert_recent as $hc): ?>
                                        <tr>
                                            <td class="small"><?= htmlspecialchars($hc['issue_date'] ?? '-') ?></td>
                                            <td class="small"><?= htmlspecialchars($hc['animal_type'] ?? '-') ?></td>
                                            <td class="small text-muted"><?= htmlspecialchars($hc['purpose'] ?? '-') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
        break;

    default:
        echo '<div class="alert alert-warning">Unknown tab requested.</div>';
}

$html = ob_get_clean();
echo json_encode(['success' => true, 'html' => $html]);
