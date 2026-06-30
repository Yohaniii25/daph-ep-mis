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

require_once '../../../includes/header.php';
require_once '../../../includes/sidebar.php';
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold text-dark">7. Counterfoil Books Registry</h3>
                <p class="text-muted small mb-0">
                    Range Office: <strong class="text-dark"><?= htmlspecialchars($range_name) ?></strong> | 
                    District: <strong class="text-dark"><?= htmlspecialchars($district_name) ?></strong>
                </p>
            </div>
            <div>
                <button class="btn text-white shadow-sm" style="background-color: #e67e22;" data-bs-toggle="modal" data-bs-target="#addCounterfoilModal">
                    <i class="bi bi-plus-circle-fill me-2"></i>Add Counterfoil Record
                </button>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table id="counterfoilTable" class="table table-hover align-middle w-100">
                        <thead class="table-light text-uppercase small">
                            <tr>
                                <th>Type</th>
                                <th>Condition</th>
                                <th class="text-center">Available Quantity</th>
                                <th>Date of Purchase / Received</th>
                                <th>Remarks</th>
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
                                $badge_style = 'bg-secondary';
                                if ($row['current_condition'] === 'Good' || $row['current_condition'] === 'New') $badge_style = 'bg-success';
                                elseif ($row['current_condition'] === 'Needs Repair' || $row['current_condition'] === 'Half-Used') $badge_style = 'bg-warning text-dark';
                                elseif ($row['current_condition'] === 'Unserviceable' || $row['current_condition'] === 'Exhausted') $badge_style = 'bg-danger';
                            ?>
                            <tr id="counterfoil-row-<?= $row['id'] ?>">
                                <td><span class="fw-bold text-dark"><?= htmlspecialchars($row['counterfoil_type']) ?></span></td>
                                <td><span class="badge <?= $badge_style ?> rounded-pill px-2.5 py-1.5"><?= htmlspecialchars($row['current_condition']) ?></span></td>
                                <td class="text-center fw-bold text-dark"><?= sprintf("%02d", $row['available_quantity']) ?></td>
                                <td class="text-secondary small fw-medium"><?= htmlspecialchars($row['purchase_date']) ?></td>
                                <td><small class="text-muted"><?= !empty($row['remarks']) ? htmlspecialchars($row['remarks']) : '-' ?></small></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-danger" onclick="handleCounterfoilDelete(<?= $row['id'] ?>)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; $cf_stmt->close(); ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include 'models/add_counterfoil.php'; ?>


<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    var dataTable;
    $(document).ready(function() {
        dataTable = $('#counterfoilTable').DataTable({ "pageLength": 10 });

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
    });

    function handleCounterfoilDelete(id) {
        Swal.fire({
            title: 'Delete Counterfoil Entry?',
            text: "This soft-deletes the counterfoil ledger block from active display structures safely.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e67e22',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Delete'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'processors/delete_counterfoil.php',
                    type: 'POST',
                    data: { id: id },
                    dataType: 'json',
                    success: function(res) {
                        if (res.success) {
                            Swal.fire('Removed!', res.message, 'success');
                            dataTable.row('#counterfoil-row-' + id).remove().draw(false);
                        } else {
                            Swal.fire('Error', res.message, 'error');
                        }
                    }
                });
            }
        });
    }
</script>

<?php require_once '../../../includes/footer.php'; ?>