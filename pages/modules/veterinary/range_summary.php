<?php
/**
 * pages/modules/veterinary/range_summary.php
 * Tabbed Summary Dashboard for the Range Details module.
 * Read-only aggregated statistics; no data entry.
 */
session_start();
require_once __DIR__ . '/../../../config/db_connect.php';

/** @var mysqli $mysqli */
global $mysqli;
require_once __DIR__ . '/../district/processors/db_migration.php';

ensure_quick_action_assignments_table($mysqli);

if (!isset($_SESSION['logged_in']) || empty($_SESSION['user_id'])) {
    header("Location: ../../../index.php");
    exit();
}

$user_id   = (int)$_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? '';

// Allowed roles
$allowed_roles = [
    'veterinary_surgeon', 'government_veterinary_surgeon', 'additional_veterinary_surgeon',
    'employee', 'livestock_development_officer', 'development_officer',
    'driver', 'dispensary_assistant', 'department_laborer', 'night_watcher',
    'district_dd', 'deputy_director_district', 'administrator',
    'provincial_director', 'deputy_director_hq_1', 'deputy_director_hq_2'
];

if (!in_array($user_role, $allowed_roles)) {
    header("Location: ../../../index.php");
    exit();
}

// ── Resolve range_id from DB ──────────────────────────────────────────────────
$user_range_id   = null;
$user_district_id = null;

$user_query = $mysqli->prepare("SELECT range_id, district_id, district FROM users WHERE id = ?");
if ($user_query) {
    $user_query->bind_param("i", $user_id);
    $user_query->execute();
    if ($urow = $user_query->get_result()->fetch_assoc()) {
        $user_range_id    = !empty($urow['range_id'])    ? (int)$urow['range_id']    : null;
        $user_district_id = !empty($urow['district_id']) ? (int)$urow['district_id'] : null;
    }
    $user_query->close();
}

$sup_roles   = ['district_dd','deputy_director_district','administrator','provincial_director','deputy_director_hq_1','deputy_director_hq_2'];
$is_sup      = in_array($user_role, $sup_roles);
$requested_range_id = isset($_GET['range_id']) && is_numeric($_GET['range_id']) ? (int)$_GET['range_id'] : null;

if (!$is_sup) {
    $range_id = $user_range_id ?: (int)($_SESSION['range_id'] ?? 0) ?: null;
} else {
    $range_id = $requested_range_id ?: $user_range_id ?: (int)($_SESSION['range_id'] ?? 0) ?: null;
    if (empty($range_id) && !empty($user_district_id)) {
        $fr = $mysqli->prepare("SELECT id FROM veterinary_ranges WHERE district_id = ? ORDER BY id ASC LIMIT 1");
        if ($fr) {
            $fr->bind_param("i", $user_district_id);
            $fr->execute();
            if ($fr_row = $fr->get_result()->fetch_assoc()) $range_id = (int)$fr_row['id'];
            $fr->close();
        }
    }
}

// ── Range meta ────────────────────────────────────────────────────────────────
$range_name   = 'Your Range';
$district_name = 'Your District';

if (!empty($range_id)) {
    $meta = $mysqli->prepare("
        SELECT vr.name AS range_name, d.name AS district_name
        FROM veterinary_ranges vr
        LEFT JOIN districts d ON vr.district_id = d.id
        WHERE vr.id = ?
    ");
    if ($meta) {
        $meta->bind_param("i", $range_id);
        $meta->execute();
        if ($mrow = $meta->get_result()->fetch_assoc()) {
            $range_name    = $mrow['range_name']    ?? $range_name;
            $district_name = $mrow['district_name'] ?? $district_name;
        }
        $meta->close();
    }
}

require_once '../../../includes/header.php';
?>

<link rel="stylesheet" href="../../../assets/css/bootstrap-icons.min.css">
<link rel="stylesheet" href="../../../assets/css/veterinary.css">

<style>
/* ── Premium Tab Styles ───────────────────────────────────────────── */
.rs-hero {
    background: linear-gradient(135deg, #820100 0%, #370709 60%, #1a0304 100%);
    border-radius: 16px;
    padding: 1.6rem 2rem;
    margin-bottom: 1.5rem;
    position: relative;
    overflow: hidden;
}
.rs-hero::before {
    content: '';
    position: absolute; inset: 0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.04'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
}
.rs-hero .badge-range {
    background: rgba(255,255,255,0.15);
    color: #fff;
    border: 1px solid rgba(255,255,255,0.25);
    font-size: 0.75rem;
    padding: 4px 10px;
    border-radius: 20px;
    backdrop-filter: blur(4px);
}

/* Tab nav */
.rs-tab-nav {
    background: #fff;
    border-radius: 12px;
    padding: 6px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.07);
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    margin-bottom: 1.25rem;
    border: 1px solid #f0f0f0;
}
.rs-tab-nav .nav-link {
    border-radius: 8px !important;
    color: #555 !important;
    font-size: 0.85rem;
    font-weight: 500;
    padding: 8px 16px;
    border: none !important;
    transition: background 0.18s, color 0.18s, box-shadow 0.18s;
    display: flex;
    align-items: center;
    gap: 6px;
    white-space: nowrap;
}
.rs-tab-nav .nav-link:hover {
    background: #fdf0f0 !important;
    color: #820100 !important;
}
.rs-tab-nav .nav-link.active {
    background: linear-gradient(135deg, #820100, #370709) !important;
    color: #fff !important;
    box-shadow: 0 3px 10px rgba(130,1,0,0.35);
    font-weight: 600;
}
.rs-tab-nav .nav-link.active i {
    color: #fff !important;
}
.rs-tab-nav .nav-link i {
    color: #820100;
    font-size: 1rem;
    transition: color 0.18s;
}

/* Tab content area */
.rs-tab-pane {
    min-height: 320px;
}
.rs-spinner-wrap {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 220px;
    gap: 12px;
}
.rs-spinner-wrap .spinner-border {
    width: 2.5rem; height: 2.5rem;
    border-width: 0.22em;
    color: #820100;
}

/* Stat cards */
.rs-stat-badge {
    display: inline-flex;
    align-items: center;
    background: rgba(255,255,255,0.1);
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 8px;
    padding: 6px 14px;
    font-size: 0.78rem;
    color: rgba(255,255,255,0.9);
    gap: 6px;
}
</style>

        <!-- ── Hero Header ──────────────────────────────────────────────── -->
        <div class="rs-hero">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 position-relative">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-geo-alt-fill text-white" style="font-size:1.5rem;"></i>
                        <h2 class="h4 fw-bold text-white mb-0">Range Details — Summary Dashboard</h2>
                    </div>
                    <p class="text-white mb-0" style="opacity:0.8; font-size:0.88rem;">
                        Read-only statistics and aggregated metrics for
                        <strong class="text-white"><?= htmlspecialchars($range_name) ?> Range</strong>
                        &nbsp;|&nbsp; <?= htmlspecialchars($district_name) ?> District
                    </p>
                </div>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <?php if (!empty($range_id)): ?>
                        <span class="rs-stat-badge"><i class="bi bi-geo-fill"></i> Range #<?= $range_id ?></span>
                    <?php endif; ?>
                    <span class="rs-stat-badge"><i class="bi bi-calendar3"></i> <?= date('Y') ?></span>
                    <a href="range_details.php<?= $range_id ? '?range_id='.$range_id : '' ?>" class="btn btn-sm btn-light fw-semibold shadow-sm" style="color:#370709;">
                        <i class="bi bi-grid-3x3-gap-fill me-1"></i> Quick Actions
                    </a>
                </div>
            </div>
        </div>

        <?php if (empty($range_id)): ?>
            <div class="alert alert-warning border d-flex align-items-center gap-2" role="alert">
                <i class="bi bi-exclamation-triangle-fill fs-5 text-warning flex-shrink-0"></i>
                <div><strong>No Range Assigned:</strong> Your account is not linked to a veterinary range. Please contact your District Deputy Director.</div>
            </div>
        <?php else: ?>

        <!-- ── Tab Navigation ─────────────────────────────────────────── -->
        <div class="rs-tab-nav nav nav-tabs" id="rangeSummaryTabs" role="tablist">
            <a class="nav-link active" id="tab-overview"     data-bs-toggle="tab" href="#pane-overview"     role="tab" aria-controls="pane-overview"     aria-selected="true">
                <i class="bi bi-house-door-fill"></i> Overview
            </a>
            <a class="nav-link" id="tab-population"   data-bs-toggle="tab" href="#pane-population"   role="tab" aria-controls="pane-population"   aria-selected="false">
                <i class="bi bi-people-fill"></i> Population
            </a>
            <a class="nav-link" id="tab-health"       data-bs-toggle="tab" href="#pane-health"       role="tab" aria-controls="pane-health"       aria-selected="false">
                <i class="bi bi-activity"></i> Health &amp; Breeding
            </a>
            <a class="nav-link" id="tab-vaccination"  data-bs-toggle="tab" href="#pane-vaccination"  role="tab" aria-controls="pane-vaccination"  aria-selected="false">
                <i class="bi bi-capsule-pill"></i> Vaccination &amp; Drugs
            </a>
            <a class="nav-link" id="tab-returns"      data-bs-toggle="tab" href="#pane-returns"      role="tab" aria-controls="pane-returns"      aria-selected="false">
                <i class="bi bi-graph-up-arrow"></i> Annual Returns
            </a>
            <a class="nav-link" id="tab-assets"       data-bs-toggle="tab" href="#pane-assets"       role="tab" aria-controls="pane-assets"       aria-selected="false">
                <i class="bi bi-archive-fill"></i> Assets &amp; Inventory
            </a>
            <a class="nav-link" id="tab-regulatory"   data-bs-toggle="tab" href="#pane-regulatory"   role="tab" aria-controls="pane-regulatory"   aria-selected="false">
                <i class="bi bi-shield-check"></i> Regulatory &amp; Reports
            </a>
        </div>

        <!-- ── Tab Content Panes ─────────────────────────────────────── -->
        <div class="tab-content" id="rangeSummaryTabContent">
            <?php
            $tabs = ['overview','population','health','vaccination','returns','assets','regulatory'];
            foreach ($tabs as $t):
            ?>
            <div class="tab-pane fade <?= $t === 'overview' ? 'show active' : '' ?> rs-tab-pane"
                 id="pane-<?= $t ?>"
                 role="tabpanel"
                 aria-labelledby="tab-<?= $t ?>"
                 data-tab="<?= $t ?>"
                 data-loaded="false">
                <!-- Spinner shown until AJAX fills this -->
                <div class="rs-spinner-wrap">
                    <div class="spinner-border" role="status"></div>
                    <span class="text-muted small">Loading <?= ucwords(str_replace('-',' ',$t)) ?> data…</span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php endif; ?>

<?php
ob_start();
?>
<script>
(function () {
    'use strict';

    const RANGE_ID  = <?= (int)($range_id ?? 0) ?>;
    const BASE_URL  = 'processors/get_range_summary_tab.php';

    /**
     * Fetch a tab's HTML from the server and inject it into the pane.
     */
    function loadTab(tabKey, paneEl) {
        if (paneEl.dataset.loaded === 'true') return; // cached

        const url = `${BASE_URL}?tab=${encodeURIComponent(tabKey)}&range_id=${RANGE_ID}`;

        fetch(url)
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    paneEl.innerHTML = data.html;
                    paneEl.dataset.loaded = 'true';
                } else {
                    paneEl.innerHTML = `<div class="alert alert-danger m-3">${data.html || 'Failed to load tab content.'}</div>`;
                }
            })
            .catch(() => {
                paneEl.innerHTML = '<div class="alert alert-danger m-3">Network error loading tab. Please refresh.</div>';
            });
    }

    // Load the Overview tab immediately on page load
    document.addEventListener('DOMContentLoaded', function () {
        const firstPane = document.querySelector('#pane-overview');
        if (firstPane) loadTab('overview', firstPane);
    });

    // Load other tabs lazily on first click
    document.querySelectorAll('#rangeSummaryTabs .nav-link').forEach(function (link) {
        link.addEventListener('shown.bs.tab', function (e) {
            const targetId  = e.target.getAttribute('href'); // e.g. "#pane-health"
            const tabKey    = targetId.replace('#pane-', ''); // e.g. "health"
            const paneEl    = document.querySelector(targetId);
            if (paneEl) loadTab(tabKey, paneEl);
        });
    });
})();
</script>
<?php
$pageScripts = ob_get_clean();
require_once '../../../includes/footer.php';
?>
