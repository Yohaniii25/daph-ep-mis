<?php
session_start();
require_once '../../../config/db_connect.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'veterinary_surgeon') {
    header("Location: ../../../../index.php");
    exit();
}

$user_id = $_SESSION['user_id'] ?? null;
$range_id = $_SESSION['range_id'] ?? null;
$range_name = $_SESSION['range_name'] ?? 'Your Range';
$district_id = $_SESSION['district_id'] ?? null;
$district_name = 'Your District';

// Retrieve profile region details
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

require_once '../../../includes/header.php';
?>

<link rel="stylesheet" href="../../../assets/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="../../../assets/css/sweetalert2.min.css">


        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold text-dark">6. Instruments Inventory</h3>
                <p class="text-muted small mb-0">
                    Range Office: <strong class="text-dark"><?= htmlspecialchars($range_name) ?></strong> | 
                    District: <strong class="text-dark"><?= htmlspecialchars($district_name) ?></strong>
                </p>
            </div>
            <div>
                <button class="btn text-light shadow-sm" style="background-color: #003ddc;" data-bs-toggle="modal" data-bs-target="#addInstrumentModal">
                    <i class="bi bi-plus-circle-fill me-2"></i>Add Instrument Record
                </button>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table id="instrumentsTable" class="table table-hover align-middle w-100">
                        <thead class="table-light text-uppercase small">
                            <tr>
                                <th>Type</th>
                                <th>Condition</th>
                                <th class="text-center">Initial Baseline</th>
                                <th class="text-center">Available Quantity</th>
                                <th>Date of Purchase / Received</th>
                                <th>Remarks</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $inst_stmt = $mysqli->prepare("SELECT * FROM instrument_assets WHERE district_id = ? AND range_id = ? AND is_active = 1 ORDER BY id DESC");
                            $inst_stmt->bind_param("ii", $district_id, $range_id);
                            $inst_stmt->execute();
                            $inst_res = $inst_stmt->get_result();

                            while ($row = $inst_res->fetch_assoc()):
                                $cond = $row['current_condition'];
                                $badge_style = 'bg-secondary';
                                if ($cond === 'Good' || $cond === 'Operational') $badge_style = 'bg-success';
                                elseif ($cond === 'Fair' || $cond === 'Needs Repair') $badge_style = 'bg-warning text-dark';
                                elseif ($cond === 'Damaged' || $cond === 'Unserviceable') $badge_style = 'bg-danger';
                            ?>
                            <tr id="instrument-row-<?= $row['id'] ?>">
                                <td><span class="fw-bold text-dark"><?= htmlspecialchars($row['instrument_type']) ?></span></td>
                                <td><span class="badge <?= $badge_style ?> rounded-pill px-2.5 py-1.5"><?= htmlspecialchars($row['current_condition']) ?></span></td>
                                <td class="text-center fw-semibold text-secondary"><?= sprintf("%02d", $row['initial_count'] ?? $row['available_quantity']) ?></td>
                                <td class="text-center fw-bold text-dark"><?= sprintf("%02d", $row['available_quantity']) ?></td>
                                <td class="text-secondary small fw-medium"><?= htmlspecialchars($row['purchase_date']) ?></td>
                                <td><small class="text-muted"><?= !empty($row['remarks']) ? htmlspecialchars($row['remarks']) : '-' ?></small></td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-outline-info me-1" title="View Details" onclick='viewInstrument(<?= json_encode($row) ?>)'>
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-primary me-1" title="Edit Instrument" onclick='editInstrument(<?= json_encode($row) ?>)'>
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-warning text-dark me-1" title="Initiate Inter-Unit Transfer" onclick='openInventoryTransferModal(<?= json_encode($row) ?>, "instrument")'>
                                            <i class="bi bi-arrow-left-right"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" title="Board of Survey Decommission" onclick='openBoardOfSurveyModal(<?= json_encode($row) ?>)'>
                                            <i class="bi bi-shield-x me-1"></i>Decommission
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; $inst_stmt->close(); ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- include modal -->
<?php include 'models/add_instrument.php'; ?>
<?php include 'models/edit_instrument.php'; ?>
<?php include 'models/view_instrument.php'; ?>
<?php include 'models/modal_board_of_survey.php'; ?>
<?php include 'models/modal_inventory_transfer.php'; ?>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    var dataTable;
    $(document).ready(function() {
        dataTable = $('#instrumentsTable').DataTable({ "pageLength": 10 });

        $('#addInstrumentForm').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: 'processors/save_instrument.php',
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

        // Submit Edit Instrument Form
        $('#editInstrumentForm').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: 'processors/update_instrument.php',
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

    function viewInstrument(data) {
        document.getElementById('view_instrument_type').textContent = data.instrument_type || '-';
        document.getElementById('view_instrument_condition').textContent = data.current_condition || '-';
        if (document.getElementById('view_instrument_initial_count')) {
            document.getElementById('view_instrument_initial_count').textContent = (data.initial_count !== undefined && data.initial_count !== null) ? data.initial_count : (data.available_quantity || '-');
        }
        document.getElementById('view_instrument_quantity').textContent = data.available_quantity || '-';
        document.getElementById('view_instrument_purchase_date').textContent = data.purchase_date || '-';
        document.getElementById('view_instrument_remarks').textContent = data.remarks || '-';
        var modal = new bootstrap.Modal(document.getElementById('viewInstrumentModal'));
        modal.show();
    }

    var originalInstrumentQty = 0;
    var originalInstrumentCondition = '';

    function editInstrument(data) {
        document.getElementById('edit_instrument_id').value = data.id || '';
        document.getElementById('edit_instrument_type').value = data.instrument_type || '';
        if (document.getElementById('edit_instrument_initial_count')) {
            document.getElementById('edit_instrument_initial_count').value = (data.initial_count !== undefined && data.initial_count !== null) ? data.initial_count : (data.available_quantity || 1);
        }
        originalInstrumentQty = parseInt(data.available_quantity) || 1;
        originalInstrumentCondition = data.current_condition || 'Good';

        document.getElementById('edit_instrument_quantity').value = originalInstrumentQty;
        document.getElementById('edit_instrument_condition').value = originalInstrumentCondition;
        document.getElementById('edit_instrument_purchase_date').value = data.purchase_date || '';
        document.getElementById('edit_instrument_remarks').value = data.remarks || '';
        document.getElementById('edit_instrument_unit').value = data.unit || 'range_veterinary_officer';
        
        var noticeEl = document.getElementById('edit_instrument_damaged_notice');
        if (noticeEl) {
            if (originalInstrumentCondition === 'Damaged') {
                noticeEl.classList.remove('d-none');
                noticeEl.style.display = 'block';
            } else {
                noticeEl.classList.add('d-none');
                noticeEl.style.display = 'none';
            }
        }

        var modal = new bootstrap.Modal(document.getElementById('editInstrumentModal'));
        modal.show();
    }

    $('#edit_instrument_condition').on('change', function() {
        var selectedCond = $(this).val();
        var noticeEl = document.getElementById('edit_instrument_damaged_notice');
        var qtyInput = document.getElementById('edit_instrument_quantity');
        
        if (selectedCond === 'Damaged') {
            if (noticeEl) {
                noticeEl.classList.remove('d-none');
                noticeEl.style.display = 'block';
            }
            if (qtyInput && originalInstrumentCondition !== 'Damaged') {
                qtyInput.value = Math.max(0, originalInstrumentQty - 1);
            }
        } else {
            if (noticeEl) {
                noticeEl.classList.add('d-none');
                noticeEl.style.display = 'none';
            }
            if (qtyInput && originalInstrumentCondition !== 'Damaged') {
                qtyInput.value = originalInstrumentQty;
            }
        }
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
        var normalizedType = assetMap[assetType] || assetType || 'instrument';
        
        if (document.getElementById('trans_item_id')) {
            document.getElementById('trans_item_id').value = data.id || '';
        }
        if (document.getElementById('trans_asset_type')) {
            document.getElementById('trans_asset_type').value = normalizedType;
        }
        
        var itemName = data.instrument_type || '-';
        
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
        document.getElementById('bos_asset_type').value = 'instrument';
        document.getElementById('bos_item_id').value = data.id || '';
        document.getElementById('bos_item_name').textContent = data.instrument_type || '-';
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

    function handleInstrumentDelete(id) {
        Swal.fire({
            icon: 'warning',
            title: 'Direct Deletion Prohibited',
            text: "Direct deletions are permanently disabled per formal auditing procedures. Items must be formally decommissioned under an authorized Board of Survey reference.",
            confirmButtonColor: '#820100',
            confirmButtonText: 'Understood'
        });
    }
</script>

<?php require_once '../../../includes/footer.php'; ?>