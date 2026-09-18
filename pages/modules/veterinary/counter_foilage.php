<?php
session_start();
require_once __DIR__ . '/../../../config/db_connect.php';
require_once __DIR__ . '/../../../includes/counterfoil_module_helper.php';

/** @var mysqli $mysqli */
global $mysqli;

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'veterinary_surgeon') {
    header("Location: ../../../../index.php");
    exit();
}

$user_id = $_SESSION['user_id'] ?? null;
$range_id = $_SESSION['range_id'] ?? null;
$range_name = $_SESSION['range_name'] ?? 'Your Range';
$district_id = $_SESSION['district_id'] ?? null;
$district_name = 'Your District';

// Retrieve profile identities
if (!empty($district_id)) {
    $dst_stmt = $mysqli->prepare("SELECT name FROM districts WHERE id = ?");
    $dst_stmt->bind_param("i", $district_id);
    $dst_stmt->execute();
    $dst_res = $dst_stmt->get_result();
    if ($row = $dst_res->fetch_assoc()) $district_name = $row['name'];
    $dst_stmt->close();
}
if (!empty($range_id)) {
    $rng_stmt = $mysqli->prepare("SELECT name FROM veterinary_ranges WHERE id = ?");
    $rng_stmt->bind_param("i", $range_id);
    $rng_stmt->execute();
    $rng_res = $rng_stmt->get_result();
    if ($row = $rng_res->fetch_assoc()) $range_name = $row['name'];
    $rng_stmt->close();
}

// Compute tab badge counts
$cnt_books = 0;
if (!empty($range_id)) {
    $b_cnt_stmt = $mysqli->prepare("SELECT COUNT(*) AS cnt FROM counterfoil_assets WHERE district_id = ? AND range_id = ? AND is_active = 1");
    if ($b_cnt_stmt) {
        $b_cnt_stmt->bind_param("ii", $district_id, $range_id);
        $b_cnt_stmt->execute();
        $cnt_books = $b_cnt_stmt->get_result()->fetch_assoc()['cnt'] ?? 0;
        $b_cnt_stmt->close();
    }
}

$cnt_leaves = 0;
if (!empty($range_id)) {
    $l_cnt_stmt = $mysqli->prepare("SELECT COUNT(*) AS cnt FROM counterfoil_leaf_issues WHERE range_id = ?");
    if ($l_cnt_stmt) {
        $l_cnt_stmt->bind_param("i", $range_id);
        $l_cnt_stmt->execute();
        $cnt_leaves = $l_cnt_stmt->get_result()->fetch_assoc()['cnt'] ?? 0;
        $l_cnt_stmt->close();
    }
}

require_once '../../../includes/header.php';
?>

<link rel="stylesheet" href="../../../assets/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="../../../assets/css/sweetalert2.min.css">

<style>
.cf-nav-tabs {
    border-bottom: 2px solid #e9ecef;
    margin-bottom: 1.5rem;
    gap: 0.5rem;
}
.cf-nav-tabs .nav-link {
    border: none;
    border-bottom: 3px solid transparent;
    color: #6c757d;
    font-weight: 600;
    padding: 0.75rem 1.25rem;
    border-radius: 0;
    transition: all 0.2s ease-in-out;
    background: transparent;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}
.cf-nav-tabs .nav-link:hover {
    color: #e67e22;
    border-bottom-color: rgba(230, 126, 34, 0.4);
}
.cf-nav-tabs .nav-link.active {
    color: #e67e22;
    background: transparent;
    border-bottom-color: #e67e22;
}
.cf-nav-tabs .nav-link.active .badge-tab {
    background-color: #e67e22 !important;
    color: #fff !important;
}
.cf-nav-tabs .nav-link .badge-tab {
    font-size: 11px;
    padding: 0.25rem 0.55rem;
    border-radius: 20px;
    background-color: #f1f3f5;
    color: #495057;
    transition: all 0.2s ease-in-out;
}
</style>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold text-dark">7. Counterfoil Records &amp; Register</h3>
                <p class="text-muted small mb-0">
                    Range Office: <strong class="text-dark"><?= htmlspecialchars($range_name) ?></strong> | 
                    District: <strong class="text-dark"><?= htmlspecialchars($district_name) ?></strong>
                </p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#issueCounterfoilLeafModal">
                    <i class="bi bi-file-earmark-person me-2"></i>Issue Individual Certificate / Leaf
                </button>
                <button class="btn text-white shadow-sm" style="background-color: #e67e22;" data-bs-toggle="modal" data-bs-target="#addCounterfoilModal">
                    <i class="bi bi-plus-circle-fill me-2"></i>Add Counterfoil Record
                </button>
                <a href="office_details.php" class="btn btn-secondary shadow-sm">
                    <i class="bi bi-arrow-left me-1"></i>Back
                </a>
            </div>
        </div>

        <!-- ── Navigation Tabs ─────────────────────────────────────────── -->
        <ul class="nav cf-nav-tabs" id="counterfoilTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="tab-books" data-bs-toggle="tab" data-bs-target="#pane-books" type="button" role="tab" aria-controls="pane-books" aria-selected="true">
                    <i class="bi bi-journal-bookmark-fill text-primary"></i>
                    <span>Counterfoil Books Registry</span>
                    <span class="badge badge-tab"><?= number_format($cnt_books) ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-leaves" data-bs-toggle="tab" data-bs-target="#pane-leaves" type="button" role="tab" aria-controls="pane-leaves" aria-selected="false">
                    <i class="bi bi-file-earmark-person-fill text-success"></i>
                    <span>Issued Counterfoil Leaves &amp; Certificates Register</span>
                    <span class="badge badge-tab"><?= number_format($cnt_leaves) ?></span>
                </button>
            </li>
        </ul>

        <!-- ── Tab Contents ────────────────────────────────────────────── -->
        <div class="tab-content" id="counterfoilTabsContent">
            <!-- TAB PANE 1: Counterfoil Books Registry -->
            <div class="tab-pane fade show active" id="pane-books" role="tabpanel" aria-labelledby="tab-books" tabindex="0">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <!-- Module Connection & Categorization Filter Bar -->
                        <div class="d-flex flex-wrap gap-2 align-items-center mb-3 p-2.5 bg-light rounded-3 border">
                            <span class="small fw-bold text-muted me-1"><i class="bi bi-funnel-fill text-secondary me-1"></i>Module Filter:</span>
                            <button type="button" class="btn btn-sm btn-dark cf-module-filter-btn active" data-module="all">
                                All Books
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-success cf-module-filter-btn" data-module="farmer">
                                <i class="bi bi-person-check-fill me-1"></i>Farmer View (12 Types)
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-warning text-dark cf-module-filter-btn" data-module="vehicle">
                                <i class="bi bi-truck-front-fill me-1"></i>Vehicle Module (Fuel Order)
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-info text-dark cf-module-filter-btn" data-module="hr">
                                <i class="bi bi-people-fill me-1"></i>HR Module (Warrants)
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary cf-module-filter-btn" data-module="inventory">
                                <i class="bi bi-boxes me-1"></i>Inventory Module (Issue/Receive)
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary cf-module-filter-btn" data-module="office">
                                <i class="bi bi-building me-1"></i>Office / General (Disease &amp; OPD)
                            </button>
                        </div>

                        <div class="table-responsive">
                    <table id="counterfoilTable" class="table table-hover align-middle w-100">
                        <thead class="table-light text-uppercase small">
                            <tr>
                                <th>Book Type</th>
                                <th>Module Connection</th>
                                <th>Serial No. Range</th>
                                <th>Total Pages</th>
                                <th>Issue Order No.</th>
                                <th>Received From</th>
                                <th>Receipt No.</th>
                                <th class="text-center">Quantity</th>
                                <th>Date Received / Logged</th>
                                <th>Condition</th>
                                <th>Specification / Remarks</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $cf_stmt = $mysqli->prepare("SELECT * FROM counterfoil_assets WHERE district_id = ? AND range_id = ? AND is_active = 1 ORDER BY id DESC");
                            $cf_stmt->bind_param("ii", $district_id, $range_id);
                            $cf_stmt->execute();
                            $cf_res = $cf_stmt->get_result();

                            while ($row = $cf_res->fetch_assoc()):
                                $cond = $row['current_condition'];
                                $badge_style = 'bg-secondary';
                                if ($cond === 'Good' || $cond === 'New') $badge_style = 'bg-success';
                                elseif ($cond === 'Fair' || $cond === 'Needs Repair' || $cond === 'Half-Used') $badge_style = 'bg-warning text-dark';
                                elseif ($cond === 'Damaged' || $cond === 'Unserviceable' || $cond === 'Exhausted') $badge_style = 'bg-danger';

                                $mod = getCounterfoilModuleInfo($row['counterfoil_type']);
                            ?>
                            <tr id="counterfoil-row-<?= $row['id'] ?>" data-module-key="<?= htmlspecialchars($mod['module_key']) ?>">
                                <td>
                                    <span class="fw-bold text-dark"><?= htmlspecialchars($row['counterfoil_type']) ?></span>
                                </td>
                                <td>
                                    <span class="badge <?= $mod['badge_class'] ?> rounded-pill px-2.5 py-1.5 d-inline-flex align-items-center gap-1">
                                        <i class="bi <?= $mod['icon'] ?>"></i> <?= htmlspecialchars($mod['category']) ?>
                                    </span>
                                    <small class="text-muted d-block mt-1" style="font-size: 10px;"><?= htmlspecialchars($mod['desc']) ?></small>
                                </td>
                                <td>
                                    <span class="font-monospace fw-bold text-dark"><?= !empty($row['book_serial_no']) ? htmlspecialchars($row['book_serial_no']) : '-' ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border font-monospace"><?= !empty($row['page_count']) ? htmlspecialchars($row['page_count']) : '-' ?></span>
                                </td>
                                <td><span class="badge bg-light text-dark border font-monospace"><?= !empty($row['issue_order_no']) ? htmlspecialchars($row['issue_order_no']) : '-' ?></span></td>
                                <td><small class="text-secondary"><?= !empty($row['received_from']) ? htmlspecialchars($row['received_from']) : '-' ?></small></td>
                                <td><span class="badge bg-light text-dark border font-monospace"><?= !empty($row['receipt_no']) ? htmlspecialchars($row['receipt_no']) : '-' ?></span></td>
                                <td class="text-center">
                                    <span class="badge bg-primary fs-6 px-2 py-1"><?= sprintf("%02d", $row['available_quantity']) ?></span>
                                    <br>
                                    <small class="text-muted" style="font-size:10px;" title="Baseline + Received">Base: <?= intval($row['initial_count']) ?> | Recv: <?= intval($row['received_quantity'] ?? 0) ?></small>
                                </td>
                                <td class="text-secondary small fw-medium"><?= htmlspecialchars($row['purchase_date']) ?></td>
                                <td><span class="badge <?= $badge_style ?> rounded-pill px-2.5 py-1.5"><?= htmlspecialchars($row['current_condition']) ?></span></td>
                                <td>
                                    <?php if (!empty($row['specification'])): ?>
                                        <div class="fw-semibold text-dark small"><?= htmlspecialchars($row['specification']) ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($row['remarks'])): ?>
                                        <small class="text-muted"><?= htmlspecialchars($row['remarks']) ?></small>
                                    <?php elseif (empty($row['specification'])): ?>
                                        <small class="text-muted">-</small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <?php if ($mod['module_key'] === 'vehicle'): ?>
                                            <a href="vehicles.php" class="btn btn-sm btn-outline-warning text-dark me-1" title="Open Vehicle Tracking Module">
                                                <i class="bi bi-truck-front-fill me-1"></i>Fleet
                                            </a>
                                        <?php elseif ($mod['module_key'] === 'hr'): ?>
                                            <a href="employee_managment.php" class="btn btn-sm btn-outline-info me-1" title="Open Human Resource Module">
                                                <i class="bi bi-people-fill me-1"></i>HR
                                            </a>
                                        <?php elseif ($mod['module_key'] === 'inventory'): ?>
                                            <a href="office_details.php" class="btn btn-sm btn-outline-primary me-1" title="Open Inventory Tracking Module">
                                                <i class="bi bi-boxes me-1"></i>Stores
                                            </a>
                                        <?php elseif ($mod['module_key'] === 'farmer'): ?>
                                            <button class="btn btn-sm btn-outline-success me-1" title="Issue Certificate/Leaf to Farmer" onclick="openIssueLeafWithBook(<?= $row['id'] ?>)">
                                                <i class="bi bi-file-earmark-person-fill me-1"></i>Issue Leaf
                                            </button>
                                        <?php endif; ?>

                                        <button class="btn btn-sm btn-outline-info me-1" title="View Details" onclick='viewCounterfoil(<?= json_encode($row) ?>)'>
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-primary me-1" title="Edit Counterfoil" onclick='editCounterfoil(<?= json_encode($row) ?>)'>
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-warning text-dark me-1" title="Initiate Inter-Unit Transfer" onclick='openInventoryTransferModal(<?= json_encode($row) ?>, "counterfoil")'>
                                            <i class="bi bi-arrow-left-right"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" title="Board of Survey Decommission" onclick='openBoardOfSurveyModal(<?= json_encode($row) ?>)'>
                                            <i class="bi bi-shield-x me-1"></i>Decommission
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; $cf_stmt->close(); ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div><!-- /#pane-books -->

    <!-- TAB PANE 2: Issued Counterfoil Leaves & Certificates Register -->
    <div class="tab-pane fade" id="pane-leaves" role="tabpanel" aria-labelledby="tab-leaves" tabindex="0">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-person-check me-2 text-primary"></i>Issued Counterfoil Leaves &amp; Certificates Register</h5>
                    <small class="text-muted">Direct individual certificate and leaf issuance to farmers linked via National Identity Card (NIC)</small>
                </div>
                <button class="btn btn-sm btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#issueCounterfoilLeafModal">
                    <i class="bi bi-plus-circle me-1"></i>Issue New Leaf / Certificate
                </button>
            </div>
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table id="leafIssuesTable" class="table table-hover align-middle w-100">
                        <thead class="table-light text-uppercase small">
                            <tr>
                                <th>Date</th>
                                <th>Leaf Serial No</th>
                                <th>Book Type</th>
                                <th>Farmer NIC</th>
                                <th>Farmer Name &amp; Location</th>
                                <th>Farm Reg No</th>
                                <th>Current Animal Counts</th>
                                <th>Purpose / Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $lf_stmt = $mysqli->prepare("SELECT * FROM counterfoil_leaf_issues WHERE range_id = ? ORDER BY id DESC");
                            if ($lf_stmt) {
                                $lf_stmt->bind_param("i", $range_id);
                                $lf_stmt->execute();
                                $lf_res = $lf_stmt->get_result();
                                while ($lf = $lf_res->fetch_assoc()):
                            ?>
                            <tr>
                                <td class="text-secondary small fw-medium"><?= htmlspecialchars($lf['issue_date']) ?></td>
                                <td><span class="font-monospace fw-bold text-dark"><?= htmlspecialchars($lf['leaf_serial_no']) ?></span></td>
                                <td><span class="badge bg-secondary-subtle text-secondary border"><?= htmlspecialchars($lf['counterfoil_type']) ?></span></td>
                                <td><span class="font-monospace fw-bold text-primary"><?= htmlspecialchars($lf['farmer_nic']) ?></span></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($lf['farmer_name']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($lf['location_address'] ?: '-') ?></small>
                                </td>
                                <td><span class="badge bg-light text-dark border font-monospace"><?= htmlspecialchars($lf['farm_registration_no'] ?: '-') ?></span></td>
                                <td><small class="text-secondary fw-semibold"><?= htmlspecialchars($lf['animal_counts_summary'] ?: '-') ?></small></td>
                                <td>
                                    <div class="text-dark small fw-medium"><?= htmlspecialchars($lf['purpose'] ?: '-') ?></div>
                                    <?php if (!empty($lf['remarks'])): ?>
                                        <small class="text-muted"><?= htmlspecialchars($lf['remarks']) ?></small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php 
                                endwhile;
                                $lf_stmt->close();
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div><!-- /#pane-leaves -->
</div><!-- /#counterfoilTabsContent -->
    </main>
</div>

<?php include 'models/add_counterfoil.php'; ?>
<?php include 'models/edit_counterfoil.php'; ?>
<?php include 'models/view_counterfoil.php'; ?>
<?php include 'models/modal_issue_counterfoil_leaf.php'; ?>
<?php include 'models/modal_board_of_survey.php'; ?>
<?php include 'models/modal_inventory_transfer.php'; ?>


<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    var dataTable;
    $(document).ready(function() {
        dataTable = $('#counterfoilTable').DataTable({ "pageLength": 10 });
        if ($('#leafIssuesTable').length) {
            $('#leafIssuesTable').DataTable({ "pageLength": 10, "order": [[0, "desc"]] });
        }

        // Adjust DataTables column alignments when toggling tabs
        $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
            $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
        });

        $('#addCounterfoilForm').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: 'processors/save_counterfoil.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        Swal.fire('Saved!', res.message, 'success').then(() => { location.reload(); });
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                }
            });
        });

        // Submit Edit Counterfoil Form
        $('#editCounterfoilForm').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: 'processors/update_counterfoil.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        if (res.staged) {
                            Swal.fire({
                                icon: 'info',
                                title: 'Pending Authorization',
                                text: res.message,
                                confirmButtonColor: '#500707'
                            }).then(() => { location.reload(); });
                        } else {
                            Swal.fire('Updated!', res.message, 'success').then(() => { location.reload(); });
                        }
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                }
            });
        });
    });

    function viewCounterfoil(data) {
        document.getElementById('view_counterfoil_type').textContent = data.counterfoil_type || '-';
        document.getElementById('view_counterfoil_condition').textContent = data.current_condition || '-';
        if (document.getElementById('view_counterfoil_initial_count')) {
            document.getElementById('view_counterfoil_initial_count').textContent = (data.initial_count !== undefined && data.initial_count !== null) ? data.initial_count : (data.available_quantity || '-');
        }
        if (document.getElementById('view_counterfoil_received_quantity')) {
            document.getElementById('view_counterfoil_received_quantity').textContent = (data.received_quantity !== undefined && data.received_quantity !== null) ? data.received_quantity : 0;
        }
        document.getElementById('view_counterfoil_quantity').textContent = data.available_quantity || '-';
        if (document.getElementById('view_counterfoil_book_serial_no')) {
            document.getElementById('view_counterfoil_book_serial_no').textContent = data.book_serial_no || '-';
        }
        if (document.getElementById('view_counterfoil_page_count')) {
            document.getElementById('view_counterfoil_page_count').textContent = data.page_count || '-';
        }
        document.getElementById('view_counterfoil_purchase_date').textContent = data.purchase_date || '-';
        if (document.getElementById('view_counterfoil_issue_order_no')) {
            document.getElementById('view_counterfoil_issue_order_no').textContent = data.issue_order_no || '-';
        }
        if (document.getElementById('view_counterfoil_received_from')) {
            document.getElementById('view_counterfoil_received_from').textContent = data.received_from || '-';
        }
        if (document.getElementById('view_counterfoil_receipt_no')) {
            document.getElementById('view_counterfoil_receipt_no').textContent = data.receipt_no || '-';
        }
        if (document.getElementById('view_counterfoil_specification')) {
            document.getElementById('view_counterfoil_specification').textContent = data.specification || '-';
        }
        document.getElementById('view_counterfoil_remarks').textContent = data.remarks || '-';
        var modal = new bootstrap.Modal(document.getElementById('viewCounterfoilModal'));
        modal.show();
    }

    var originalCounterfoilCondition = '';

    function editCounterfoil(data) {
        document.getElementById('edit_counterfoil_id').value = data.id || '';
        document.getElementById('edit_counterfoil_type').value = data.counterfoil_type || '';
        if (document.getElementById('edit_counterfoil_book_serial_no')) {
            document.getElementById('edit_counterfoil_book_serial_no').value = data.book_serial_no || '';
        }
        if (document.getElementById('edit_counterfoil_page_count')) {
            document.getElementById('edit_counterfoil_page_count').value = data.page_count || '';
        }
        if (document.getElementById('edit_counterfoil_initial_count')) {
            document.getElementById('edit_counterfoil_initial_count').value = (data.initial_count !== undefined && data.initial_count !== null) ? data.initial_count : (data.available_quantity || 1);
        }
        if (document.getElementById('edit_counterfoil_received_quantity')) {
            document.getElementById('edit_counterfoil_received_quantity').value = (data.received_quantity !== undefined && data.received_quantity !== null) ? data.received_quantity : 0;
        }
        originalCounterfoilCondition = data.current_condition || 'Good';

        document.getElementById('edit_counterfoil_condition').value = originalCounterfoilCondition;
        document.getElementById('edit_counterfoil_purchase_date').value = data.purchase_date || '';
        if (document.getElementById('edit_counterfoil_issue_order_no')) {
            document.getElementById('edit_counterfoil_issue_order_no').value = data.issue_order_no || '';
        }
        if (document.getElementById('edit_counterfoil_received_from')) {
            document.getElementById('edit_counterfoil_received_from').value = data.received_from || '';
        }
        if (document.getElementById('edit_counterfoil_receipt_no')) {
            document.getElementById('edit_counterfoil_receipt_no').value = data.receipt_no || '';
        }
        if (document.getElementById('edit_counterfoil_specification')) {
            document.getElementById('edit_counterfoil_specification').value = data.specification || '';
        }
        document.getElementById('edit_counterfoil_remarks').value = data.remarks || '';
        document.getElementById('edit_counterfoil_unit').value = data.unit || 'range_veterinary_officer';
        
        calcEditCounterfoilAvailability();

        var noticeEl = document.getElementById('edit_counterfoil_damaged_notice');
        if (noticeEl) {
            if (originalCounterfoilCondition === 'Damaged') {
                noticeEl.classList.remove('d-none');
                noticeEl.style.display = 'block';
            } else {
                noticeEl.classList.add('d-none');
            }
        }
        updateModulePreview('#edit_counterfoil_type', '#edit_cf_module_preview');

        var modal = new bootstrap.Modal(document.getElementById('editCounterfoilModal'));
        modal.show();
    }

    function calcAddCounterfoilAvailability() {
        var base = parseInt(document.getElementById('add_counterfoil_initial_count').value) || 0;
        var recv = parseInt(document.getElementById('add_counterfoil_received_quantity').value) || 0;
        var avail = base + recv;
        var condSelect = document.querySelector('#addCounterfoilForm select[name="current_condition"]');
        if (condSelect && condSelect.value === 'Damaged') {
            avail = Math.max(0, avail - 1);
        }
        document.getElementById('add_counterfoil_available_quantity').value = avail;
    }

    $('#edit_counterfoil_condition').on('change', function() {
        var selectedCond = $(this).val();
        var noticeEl = document.getElementById('edit_counterfoil_damaged_notice');
        if (selectedCond === 'Damaged') {
            if (noticeEl) {
                noticeEl.classList.remove('d-none');
                noticeEl.style.display = 'block';
            }
        } else {
            if (noticeEl) {
                noticeEl.classList.add('d-none');
                noticeEl.style.display = 'none';
            }
        }
        calcEditCounterfoilAvailability();
    });

    $('#addCounterfoilForm select[name="current_condition"]').on('change', function() {
        calcAddCounterfoilAvailability();
    });

    function openInventoryTransferModal(data, assetType) {
        var modalEl = document.getElementById('inventoryTransferModal');
        if (!modalEl) return;
        
        var assetMap = {
            'building': 'building_inventory',
            'furniture': 'furniture',
            'machinery': 'machinery',
            'instrument': 'instrument',
            'counterfoil': 'counterfoil'
        };
        var normalizedType = assetMap[assetType] || assetType || 'counterfoil';
        
        if (document.getElementById('trans_item_id')) {
            document.getElementById('trans_item_id').value = data.id || '';
        }
        if (document.getElementById('trans_asset_type')) {
            document.getElementById('trans_asset_type').value = normalizedType;
        }
        
        var itemName = data.counterfoil_type || '-';
        
        if (document.getElementById('trans_item_name')) {
            document.getElementById('trans_item_name').textContent = itemName;
        }
        if (document.getElementById('trans_item_name_input')) {
            document.getElementById('trans_item_name_input').value = itemName;
        }
        if (document.getElementById('trans_item_location')) {
            document.getElementById('trans_item_location').textContent = data.remarks || 'Range Office';
        }
        if (document.getElementById('trans_item_condition')) {
            document.getElementById('trans_item_condition').textContent = data.current_condition || 'Good';
        }
        
        var availQty = parseInt(data.available_quantity) || 1;
        if (document.getElementById('trans_item_available_qty')) {
            document.getElementById('trans_item_available_qty').textContent = availQty;
        }
        
        var qtyInput = document.getElementById('trans_transfer_quantity');
        if (qtyInput) {
            qtyInput.max = availQty;
            qtyInput.value = 1;
        }
        
        if (document.getElementById('trans_target_unit')) {
            document.getElementById('trans_target_unit').selectedIndex = 0;
        }
        if (document.getElementById('trans_dispatch_reference')) {
            document.getElementById('trans_dispatch_reference').value = '';
        }
        if (document.getElementById('trans_transfer_reason')) {
            document.getElementById('trans_transfer_reason').value = '';
        }
        if (document.getElementById('trans_from_unit')) {
            document.getElementById('trans_from_unit').value = data.unit || 'range_veterinary_officer';
        }
        
        var modal = new bootstrap.Modal(modalEl);
        modal.show();
    }

    $('#inventoryTransferForm').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var submitBtn = form.find('button[type="submit"]');
        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Processing...');

        $.ajax({
            url: 'processors/process_inventory_transfer.php',
            type: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function(res) {
                submitBtn.prop('disabled', false).html('<i class="bi bi-send-check me-1"></i> Dispatch Transfer Request');
                if (res.success) {
                    var modalEl = document.getElementById('inventoryTransferModal');
                    var modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();

                    Swal.fire({
                        icon: 'success',
                        title: 'Transfer Request Initiated',
                        html: '<p>' + res.message + '</p><div class="alert alert-info py-2 small mb-0"><i class="bi bi-info-circle me-1"></i><strong>Notice:</strong> As required by inventory policy, the active count remains intact at <strong>' + res.current_available_quantity + '</strong> until formal executive approval.</div>',
                        confirmButtonColor: '#820100'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Transfer Request Failed', res.message, 'error');
                }
            },
            error: function(xhr, status, err) {
                submitBtn.prop('disabled', false).html('<i class="bi bi-send-check me-1"></i> Dispatch Transfer Request');
                Swal.fire('Error', 'Server processing failure: ' + err, 'error');
            }
        });
    });

    function openBoardOfSurveyModal(data) {
        document.getElementById('bos_asset_type').value = 'counterfoil';
        document.getElementById('bos_item_id').value = data.id || '';
        document.getElementById('bos_item_name').textContent = data.counterfoil_type || '-';
        document.getElementById('bos_item_location').textContent = data.remarks || 'Range Office';
        document.getElementById('bos_item_available_qty').textContent = data.available_quantity || '0';
        
        var availQty = parseInt(data.available_quantity) || 1;
        var qtyInput = document.getElementById('bos_removal_quantity');
        qtyInput.max = availQty;
        qtyInput.value = availQty;
        
        document.getElementById('bos_removal_status').value = 'Destroyed';
        document.getElementById('bos_ref').value = '';
        document.getElementById('bos_removal_date').value = new Date().toISOString().split('T')[0];
        document.getElementById('bos_remarks').value = '';
        
        var modal = new bootstrap.Modal(document.getElementById('boardOfSurveyModal'));
        modal.show();
    }

    $('#boardOfSurveyForm').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var submitBtn = form.find('button[type="submit"]');
        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Processing...');

        $.ajax({
            url: 'processors/process_board_of_survey.php',
            type: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function(res) {
                submitBtn.prop('disabled', false).html('<i class="bi bi-check2-circle me-1"></i> Execute Formal Removal');
                if (res.success) {
                    var modalEl = document.getElementById('boardOfSurveyModal');
                    var modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();

                    Swal.fire({
                        icon: 'success',
                        title: 'Item Decommissioned',
                        text: res.message,
                        confirmButtonColor: '#820100'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Removal Failed', res.message, 'error');
                }
            },
            error: function(xhr, status, err) {
                submitBtn.prop('disabled', false).html('<i class="bi bi-check2-circle me-1"></i> Execute Formal Removal');
                Swal.fire('Error', 'Server processing failure: ' + err, 'error');
            }
        });
    });

    function handleCounterfoilDelete(id) {
        Swal.fire({
            icon: 'warning',
            title: 'Direct Deletion Prohibited',
            text: "Direct deletions are permanently disabled per formal auditing procedures. Items must be formally decommissioned under an authorized Board of Survey reference.",
            confirmButtonColor: '#820100',
            confirmButtonText: 'Understood'
        });
    }

    // Auto-suggest implementation for Counterfoil Book Type
    function setupCounterfoilTypeAutocomplete(inputSelector, dropdownSelector, apiUrl) {
        var timer = null;
        apiUrl = apiUrl || 'processors/get_counterfoil_types.php';

        function escapeHtml(str) {
            if (!str) return '';
            return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }
        function escapeRegex(str) {
            return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        }

        $(inputSelector).on('input focus', function() {
            var term = $(this).val().trim();
            var $dropdown = $(dropdownSelector);
            var previewSelector = inputSelector === '#add_counterfoil_type' ? '#add_cf_module_preview' : '#edit_cf_module_preview';
            updateModulePreview(inputSelector, previewSelector);

            clearTimeout(timer);
            timer = setTimeout(function() {
                $.ajax({
                    url: apiUrl,
                    type: 'GET',
                    data: { q: term },
                    dataType: 'json',
                    success: function(res) {
                        if (res.success && res.suggestions && res.suggestions.length > 0) {
                            var html = '';
                            res.suggestions.forEach(function(item) {
                                var highlighted = escapeHtml(item);
                                if (term.length > 0) {
                                    var re = new RegExp('(' + escapeRegex(term) + ')', 'gi');
                                    highlighted = highlighted.replace(re, '<strong class="text-primary">$1</strong>');
                                }
                                html += '<a class="dropdown-item py-2 px-3 d-flex align-items-center suggestion-item" href="javascript:void(0)" data-value="' + escapeHtml(item) + '">' +
                                        '<i class="bi bi-journal-text me-2 text-muted" style="font-size: 13px;"></i>' +
                                        '<span>' + highlighted + '</span>' +
                                        '</a>';
                            });
                            $dropdown.html(html).show();
                        } else if (term.length > 0) {
                            $dropdown.html('<div class="dropdown-header text-muted py-2 px-3 small"><i class="bi bi-pencil me-1"></i>New book type: "' + escapeHtml(term) + '" (will be auto-saved)</div>').show();
                        } else {
                            $dropdown.hide();
                        }
                    }
                });
            }, 180);
        });

        $(dropdownSelector).on('click', '.suggestion-item', function(e) {
            e.preventDefault();
            var val = $(this).data('value');
            $(inputSelector).val(val);
            $(dropdownSelector).hide();
            var previewSelector = inputSelector === '#add_counterfoil_type' ? '#add_cf_module_preview' : '#edit_cf_module_preview';
            updateModulePreview(inputSelector, previewSelector);
        });

        $(document).on('click', function(e) {
            if (!$(e.target).closest(inputSelector + ', ' + dropdownSelector).length) {
                $(dropdownSelector).hide();
            }
        });
    }

    // Dynamic Module Connection Preview
    function updateModulePreview(inputSelector, previewContainerSelector) {
        var val = $(inputSelector).val() || '';
        var clean = val.toLowerCase().replace(/[^a-z0-9]/g, '');
        var $preview = $(previewContainerSelector);

        if (!clean) {
            $preview.hide().html('');
            return;
        }

        var info = null;

        // 1. Vehicle Module: Fuel order
        if (clean.indexOf('fuelorder') !== -1 || clean.indexOf('fuel') !== -1) {
            info = {
                badgeClass: 'bg-warning text-dark border border-warning',
                icon: 'bi-truck-front-fill',
                module: 'Vehicle Module',
                linkUrl: 'vehicles.php',
                linkLabel: 'Open Vehicle Tracking',
                desc: 'Hardwired directly to Vehicle Tracking, Fuel Orders & Running Charts.'
            };
        }
        // 2. HR Module: Holiday warrant & Railway warrant Goods
        else if (clean.indexOf('holidaywar') !== -1 || clean.indexOf('railwaywar') !== -1) {
            info = {
                badgeClass: 'bg-info-subtle text-info border border-info-subtle',
                icon: 'bi-people-fill',
                module: 'Human Resource Module',
                linkUrl: 'employee_managment.php',
                linkLabel: 'Open HR Module',
                desc: 'Hardwired directly to HR Module for Employee Travel Warrants & Movement.'
            };
        }
        // 3. Inventory Items: Issue order & Receive order
        else if (clean.indexOf('issueorder') !== -1 || clean.indexOf('receiveorder') !== -1 || clean.indexOf('receiptorder') !== -1) {
            info = {
                badgeClass: 'bg-primary-subtle text-primary border border-primary-subtle',
                icon: 'bi-boxes',
                module: 'Inventory Items Module',
                linkUrl: 'office_details.php',
                linkLabel: 'Open Inventory Registry',
                desc: 'Hardwired directly to Inventory items tracking (Stores, Instruments, Furniture & Machinery).'
            };
        }
        // 4. Office / General: Disease out Break register & OPD Register
        else if (clean.indexOf('diseaseoutbreak') !== -1 || clean.indexOf('diseaseout') !== -1 || clean.indexOf('opdregister') !== -1 || clean.indexOf('opd') !== -1) {
            info = {
                badgeClass: 'bg-secondary-subtle text-secondary border border-secondary',
                icon: 'bi-building',
                module: 'Office / General',
                linkUrl: null,
                linkLabel: null,
                desc: 'Categorized strictly for internal office tracking and clinical registry.'
            };
        }
        // 5. Authorized Farmer View: 12 exact books
        else {
            var farmerKeys = [
                'airegister', 'animaltransport', 'aicertificatebook', 'aicertificate', 
                'cashreceiptbook', 'cashreceipt', 'certificateforslaughterofbuffalo', 
                'healthcertificate', 'ownershipvoucher', 'registerofcattlebranded', 
                'cattlebranded', 'registerforanimalidentificationschedule08', 
                'registerforanimalidentification', 'pivschedule08', 'schedule08', 
                'arvregister', 'animalbirthcontrolregister', 'produceregister'
            ];
            var isFarmer = farmerKeys.some(function(k) {
                return clean === k || clean.indexOf(k) !== -1 || k.indexOf(clean) !== -1;
            });

            if (isFarmer) {
                info = {
                    badgeClass: 'bg-success-subtle text-success border border-success-subtle',
                    icon: 'bi-person-check-fill',
                    module: 'Farmer Services (Authorized View)',
                    linkUrl: '#issueCounterfoilLeafModal',
                    linkLabel: 'Issue Certificate',
                    desc: 'Authorized for Individual Farmer Certificate/Leaf issuance with automated NIC farmer lookup.'
                };
            } else {
                info = {
                    badgeClass: 'bg-light text-dark border',
                    icon: 'bi-journal-text',
                    module: 'Office / General',
                    linkUrl: null,
                    linkLabel: null,
                    desc: 'Categorized strictly for internal office record-keeping and register audits.'
                };
            }
        }

        var html = '<div class="alert alert-light border shadow-sm p-2 mb-0 d-flex align-items-center justify-content-between">' +
                   '  <div class="d-flex align-items-center">' +
                   '    <span class="badge ' + info.badgeClass + ' me-2 px-2 py-1">' +
                   '      <i class="bi ' + info.icon + ' me-1"></i>' + info.module +
                   '    </span>' +
                   '    <span class="small text-muted">' + info.desc + '</span>' +
                   '  </div>';
        if (info.linkUrl && (info.linkUrl.startsWith('http') || (!info.linkUrl.startsWith('#') && info.linkLabel))) {
            html += '  <a href="' + info.linkUrl + '" target="_blank" class="btn btn-sm btn-outline-dark ms-2 text-nowrap py-0 px-2 small">' +
                    '    <i class="bi bi-box-arrow-up-right me-1"></i>' + info.linkLabel +
                    '  </a>';
        }
        html += '</div>';

        $preview.html(html).show();
    }

    // Open Issue Leaf modal with a specific book pre-selected
    function openIssueLeafWithBook(bookId) {
        if (!bookId) return;
        var modalEl = document.getElementById('issueCounterfoilLeafModal');
        if (!modalEl) return;

        // Switch to books tab or activate modal
        $('#leaf_counterfoil_id').val(bookId);
        var modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modal.show();
    }

    $(document).ready(function() {
        setupCounterfoilTypeAutocomplete('#add_counterfoil_type', '#add_counterfoil_type_suggestions', 'processors/get_counterfoil_types.php');
        setupCounterfoilTypeAutocomplete('#edit_counterfoil_type', '#edit_counterfoil_type_suggestions', 'processors/get_counterfoil_types.php');

        $('#add_counterfoil_type').on('input change', function() {
            updateModulePreview('#add_counterfoil_type', '#add_cf_module_preview');
        });
        $('#edit_counterfoil_type').on('input change', function() {
            updateModulePreview('#edit_counterfoil_type', '#edit_cf_module_preview');
        });

        // Module filter buttons handler
        $('.cf-module-filter-btn').on('click', function() {
            var mod = $(this).data('module');
            $('.cf-module-filter-btn').removeClass('active btn-dark btn-success btn-warning btn-info btn-primary btn-secondary')
                .addClass(function() {
                    var m = $(this).data('module');
                    if (m === 'all') return 'btn-outline-dark';
                    if (m === 'farmer') return 'btn-outline-success';
                    if (m === 'vehicle') return 'btn-outline-warning text-dark';
                    if (m === 'hr') return 'btn-outline-info text-dark';
                    if (m === 'inventory') return 'btn-outline-primary';
                    if (m === 'office') return 'btn-outline-secondary';
                    return 'btn-outline-secondary';
                });

            $(this).removeClass('btn-outline-dark btn-outline-success btn-outline-warning btn-outline-info btn-outline-primary btn-outline-secondary text-dark')
                .addClass(function() {
                    if (mod === 'all') return 'btn-dark active';
                    if (mod === 'farmer') return 'btn-success active';
                    if (mod === 'vehicle') return 'btn-warning text-dark active';
                    if (mod === 'hr') return 'btn-info text-dark active';
                    if (mod === 'inventory') return 'btn-primary active';
                    if (mod === 'office') return 'btn-secondary active';
                    return 'btn-dark active';
                });

            if ($.fn.DataTable.isDataTable('#counterfoilTable')) {
                var table = $('#counterfoilTable').DataTable();
                if (mod === 'all') {
                    $.fn.dataTable.ext.search.pop();
                    table.draw();
                } else {
                    $.fn.dataTable.ext.search.pop();
                    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                        var rowNode = table.row(dataIndex).node();
                        var key = $(rowNode).attr('data-module-key');
                        return key === mod;
                    });
                    table.draw();
                }
            } else {
                if (mod === 'all') {
                    $('#counterfoilTable tbody tr').show();
                } else {
                    $('#counterfoilTable tbody tr').each(function() {
                        var k = $(this).attr('data-module-key');
                        $(this).toggle(k === mod);
                    });
                }
            }
        });

        // Check if filter parameter is in URL (e.g. counter_foilage.php?filter=vehicle)
        var urlParams = new URLSearchParams(window.location.search);
        var initialFilter = urlParams.get('filter');
        if (initialFilter) {
            $('.cf-module-filter-btn[data-module="' + initialFilter + '"]').trigger('click');
        }
    });
</script>

<?php require_once '../../../includes/footer.php'; ?>