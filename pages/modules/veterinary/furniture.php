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

// Fetch dynamic regional identity values
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

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">


        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold text-dark">Furniture &amp; Fittings Inventory</h3>
                <p class="text-muted small mb-0">
                    Jurisdiction Range: <strong class="text-dark"><?= htmlspecialchars($range_name) ?></strong> | 
                    District: <strong class="text-dark"><?= htmlspecialchars($district_name) ?></strong>
                </p>
            </div>
            <div>
                <button class="btn text-white shadow-sm" style="background-color: #a07174;" data-bs-toggle="modal" data-bs-target="#addFurnitureModal">
                    <i class="bi bi-plus-circle-fill me-2"></i>Register New Furniture Asset
                </button>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table id="furnitureTable" class="table table-hover align-middle w-100">
                        <thead class="table-light text-uppercase small">
                            <tr>
                                <th>Furniture Type</th>
                                <th>Date Received / Purchased</th>
                                <th>Condition Status</th>
                                <th class="text-center">Available Qty</th>
                                <th>Location Context / Remarks</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $furn_stmt = $mysqli->prepare("SELECT * FROM furniture_assets WHERE district_id = ? AND range_id = ? AND is_active = 1 ORDER BY id DESC");
                            $furn_stmt->bind_param("ii", $district_id, $range_id);
                            $furn_stmt->execute();
                            $furn_res = $furn_stmt->get_result();

                            while ($row = $furn_res->fetch_assoc()):
                                // Condition contextual text color configuration
                                $badge_color = 'bg-secondary';
                                if ($row['current_condition'] === 'Excellent' || $row['current_condition'] === 'Good') $badge_color = 'bg-success';
                                elseif ($row['current_condition'] === 'Fair') $badge_color = 'bg-warning text-dark';
                                elseif ($row['current_condition'] === 'Damaged' || $row['current_condition'] === 'Unserviceable') $badge_color = 'bg-danger';
                            ?>
                            <tr id="furniture-row-<?= $row['id'] ?>">
                                <td class="fw-bold text-dark"><?= htmlspecialchars($row['furniture_type']) ?></td>
                                <td class="fw-semibold text-secondary"><?= htmlspecialchars($row['date_received']) ?></td>
                                <td><span class="badge <?= $badge_color ?> rounded-pill px-2"><?= htmlspecialchars($row['current_condition']) ?></span></td>
                                <td class="text-center fw-bold text-primary"><?= sprintf("%02d", $row['available_quantity']) ?></td>
                                <td><small class="text-muted"><?= htmlspecialchars($row['remarks']) ?></small></td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-outline-info me-1" title="View Details" onclick='viewFurniture(<?= json_encode($row) ?>)'>
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-primary me-1" title="Edit Furniture" onclick='editFurniture(<?= json_encode($row) ?>)'>
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" title="Delete" onclick="handleFurnitureDelete(<?= $row['id'] ?>)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; $furn_stmt->close(); ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include 'models/add_furniture.php'; ?>
<?php include 'models/edit_furniture.php'; ?>
<?php include 'models/view_furniture.php'; ?>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    var dataTable;
    $(document).ready(function() {
        dataTable = $('#furnitureTable').DataTable({ "pageLength": 10 });

        // AJAX dynamic layout transaction pipeline tracking execution
        $('#addFurnitureForm').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: 'processors/save_furniture.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        Swal.fire('Saved!', res.message, 'success').then(() => { location.reload(); });
                    } else {
                        Swal.fire('Insertion Failed', res.message, 'error');
                    }
                }
            });
        });

        // Submit Edit Furniture Form
        $('#editFurnitureForm').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: 'processors/update_furniture.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        Swal.fire('Updated!', res.message, 'success').then(() => { location.reload(); });
                    } else {
                        Swal.fire('Update Failed', res.message, 'error');
                    }
                }
            });
        });
    });

    function viewFurniture(data) {
        document.getElementById('view_furniture_type').textContent = data.furniture_type || '-';
        document.getElementById('view_furniture_quantity').textContent = data.available_quantity || '-';
        document.getElementById('view_date_received').textContent = data.date_received || '-';
        document.getElementById('view_furniture_condition').textContent = data.current_condition || '-';
        document.getElementById('view_furniture_remarks').textContent = data.remarks || '-';
        var modal = new bootstrap.Modal(document.getElementById('viewFurnitureModal'));
        modal.show();
    }

    function editFurniture(data) {
        document.getElementById('edit_furniture_id').value = data.id || '';
        document.getElementById('edit_furniture_type').value = data.furniture_type || '';
        document.getElementById('edit_furniture_quantity').value = data.available_quantity || 1;
        document.getElementById('edit_date_received').value = data.date_received || '';
        document.getElementById('edit_furniture_condition').value = data.current_condition || 'Good';
        document.getElementById('edit_furniture_remarks').value = data.remarks || '';
        var modal = new bootstrap.Modal(document.getElementById('editFurnitureModal'));
        modal.show();
    }

    function handleFurnitureDelete(id) {
        Swal.fire({
            title: 'Remove Furniture Entry?',
            text: "This will drop the item line sequence tracking code data.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#a07174',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Delete Record'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'processors/delete_furniture.php',
                    type: 'POST',
                    data: { id: id },
                    dataType: 'json',
                    success: function(res) {
                        if (res.success) {
                            Swal.fire('Deleted!', res.message, 'success');
                            dataTable.row('#furniture-row-' + id).remove().draw(false);
                        } else {
                            Swal.fire('Failed', res.message, 'error');
                        }
                    }
                });
            }
        });
    }
</script>

<?php require_once '../../../includes/footer.php'; ?>