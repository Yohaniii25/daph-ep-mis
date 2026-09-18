<?php
require_once '../../../includes/header.php';
if (!in_array($_SESSION['role'], ['veterinary_surgeon', 'sms', 'district_dd', 'admin', 'super_admin'])) die("Access denied");
require_once __DIR__ . '/../../../config/db_connect.php';

/** @var mysqli $mysqli */
global $mysqli;

// Fetch live counts for metric card fallback tracking dynamically
$count_query = "SELECT COUNT(*) AS total_types FROM `drug_types`";
$count_res = $mysqli->query($count_query);
$total_types = ($count_res) ? $count_res->fetch_assoc()['total_types'] : 0;
?>

<style>
    .metric-card-custom {
        border-radius: 16px !important;
        background-color: #ffffff;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04) !important;
        transition: all 0.25s ease-in-out;
    }
    .metric-card-custom:hover {
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08) !important;
    }
    .animal-toggle-btn.active {
        background-color: #cfe2ff !important;
        border-color: #0d6efd !important;
        color: #084298 !important;
        font-weight: 500;
    }
    .animal-toggle-btn.active .check-icon {
        display: inline !important;
    }
    .animal-toggle-btn.active .bi-tag {
        display: none;
    }
</style>

<link rel="stylesheet" href="../../../assets/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="../../../assets/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="../../../assets/css/sweetalert2.min.css">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="h4 fw-bold mb-1" style="color: #370709;">Therapeutic Drug Classifications</h2>
                <p class="text-muted small mb-0">Register brand names and chemical compositions for veterinary pharmaceutical inventory</p>
            </div>
            <a href="drug_maintenance.php" class="btn btn-outline-secondary shadow-sm">
                <i class="bi bi-arrow-left me-1"></i>Back to Drug Maintenance
            </a>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 border-start border-success border-4 metric-card-custom">
                    <div class="card-body p-4">
                        <h6 class="text-muted small text-uppercase fw-bold">Total Registered Drugs</h6>
                        <h2 class="text-success mb-0 fw-bold"><?= number_format($total_types) ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h6 class="mb-0 fw-bold"><i class="bi bi-grid-3x3-gap-fill me-2"></i>Quick Actions</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <button class="btn btn-success w-100 py-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#addDrugTypeModal">
                            <i class="bi bi-capsule mb-2 fs-5"></i><br>
                            Add New Drug Type
                        </button>
                    </div>
                    <div class="col-md-3">
                        <a href="drug_maintenance.php" class="btn btn-outline-primary w-100 py-3 shadow-sm">
                            <i class="bi bi-journal-medical mb-2 fs-5"></i><br>
                            Drug Inventory Ledger
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-3 mb-5">
            <div class="card-header bg-white py-3 border-0">
                <h5 class="m-0 fw-bold text-dark"><i class="bi bi-bookmark-star me-2 text-success"></i>Registered Drug Configuration Register</h5>
            </div>
            <div class="card-body">
                <table id="drugTypeTable" class="table table-striped align-middle row-border" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 8%;">ID</th>
                            <th style="width: 18%;">Brand Name</th>
                            <th style="width: 22%;">Chemical Composition</th>
                            <th style="width: 20%;">Display Name</th>
                            <th style="width: 20%;">Target Animals</th>
                            <th style="width: 12%;" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $ledger_sql = "SELECT * FROM `drug_types` ORDER BY `id` DESC";
                        $res = $mysqli->query($ledger_sql);
                        if ($res && $res->num_rows > 0):
                            while ($row = $res->fetch_assoc()):
                                $brand = !empty($row['brand_name']) ? $row['brand_name'] : $row['vaccine_name'];
                                $chem = !empty($row['chemical_composition']) ? $row['chemical_composition'] : '—';
                        ?>
                                <tr>
                                    <td class="fw-bold text-secondary">#<?= $row['id'] ?></td>
                                    <td><span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 fw-bold fs-7"><?= htmlspecialchars($brand) ?></span></td>
                                    <td class="fw-semibold text-dark"><i class="bi bi-prescription2 text-secondary me-1"></i><?= htmlspecialchars($chem) ?></td>
                                    <td class="text-muted small"><?= htmlspecialchars($row['vaccine_name']) ?></td>
                                    <td>
                                        <?php
                                        $animals = array_filter(array_map('trim', explode(',', $row['target_animal'])));
                                        foreach ($animals as $animal): ?>
                                            <span class="badge bg-secondary px-2 py-1 fs-7 me-1 mb-1">
                                                <i class="bi bi-tag me-1"></i><?= htmlspecialchars($animal) ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-secondary edit-drug-btn"
                                                data-id="<?= $row['id'] ?>"
                                                data-brand="<?= htmlspecialchars($row['brand_name'] ?? '', ENT_QUOTES) ?>"
                                                data-chem="<?= htmlspecialchars($row['chemical_composition'] ?? '', ENT_QUOTES) ?>"
                                                data-name="<?= htmlspecialchars($row['vaccine_name'] ?? '', ENT_QUOTES) ?>"
                                                data-expiry="<?= htmlspecialchars($row['expiry_date'] ?? '', ENT_QUOTES) ?>"
                                                data-animal="<?= htmlspecialchars($row['target_animal'], ENT_QUOTES) ?>"
                                                data-desc="<?= htmlspecialchars($row['description'] ?? '', ENT_QUOTES) ?>"
                                                data-bs-toggle="modal" data-bs-target="#addDrugTypeModal">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <a href="processors/drug_type_crud.php?action=delete&id=<?= $row['id'] ?>"
                                                class="btn btn-outline-danger btn-delete-drugtype"
                                                data-name="<?= htmlspecialchars($row['vaccine_name'], ENT_QUOTES) ?>">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                        <?php
                            endwhile;
                        endif;
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<?php include './models/drug_type_modal.php'; ?>

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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    $(document).ready(function() {
        const selectedAnimals = new Set();

        function updateAnimalHidden() {
            const arr = [...selectedAnimals];
            $('#targetAnimalHidden').val(arr.join(','));

            const select = $('#targetAnimalArray');
            select.empty();
            arr.forEach(value => {
                select.append($('<option>').val(value).text(value).prop('selected', true));
            });

            if (arr.length === 0) {
                $('#animalSelectedPills').text('No animals selected.');
            } else {
                $('#animalSelectedPills').html(
                    arr.map(a => `<span class="badge bg-success me-1">${a}</span>`).join('')
                );
            }
        }

        function resetAnimalSelection() {
            selectedAnimals.clear();
            $('.animal-toggle-btn').removeClass('active');
            $('#targetAnimalArray').empty();
            updateAnimalHidden();
        }

        $(document).on('click', '.animal-toggle-btn', function() {
            const value = $(this).data('value');
            if (selectedAnimals.has(value)) {
                selectedAnimals.delete(value);
                $(this).removeClass('active');
            } else {
                selectedAnimals.add(value);
                $(this).addClass('active');
            }
            updateAnimalHidden();
        });

        $('.edit-drug-btn').on('click', function() {
            $('#modalAction').val('update');
            $('#typeId').val($(this).data('id'));
            $('#brandName').val($(this).data('brand'));
            $('#chemComp').val($(this).data('chem'));
            $('#drugName').val($(this).data('name'));
            $('#expiry_date').val($(this).data('expiry'));
            $('#description').val($(this).data('desc'));

            resetAnimalSelection();
            const animalData = $(this).data('animal') || '';
            if (animalData.trim() !== '') {
                animalData.split(',').map(s => s.trim()).filter(Boolean).forEach(value => {
                    selectedAnimals.add(value);
                    $(`.animal-toggle-btn[data-value="${value}"]`).addClass('active');
                });
                updateAnimalHidden();
            }

            $('#drugModalTitle').html('<i class="bi bi-pencil-square me-2 text-warning"></i>Modify Drug Type Configuration');
            $('#submitBtn').removeClass('btn-success').addClass('btn-warning').text('Save Modifications');
        });

        $('#addDrugTypeModal').on('hidden.bs.modal', function() {
            $('#modalAction').val('create');
            $('#typeId').val('');
            $('#brandName').val('');
            $('#chemComp').val('');
            $('#drugName').val('');
            $('#expiry_date').val('');
            $('#drugTypeForm')[0].reset();
            resetAnimalSelection();

            $('#drugModalTitle').html('<i class="bi bi-patch-plus me-2 text-success"></i>Add New Drug Classification Type');
            $('#submitBtn').removeClass('btn-warning').addClass('btn-success').text('Save Configuration');
        });

        $('#drugTypeForm').on('submit', function() {
            updateAnimalHidden();
            return true;
        });

        // Delete Alert Confirmation Click Handler
        $(document).on('click', '.btn-delete-drugtype', function(e) {
            e.preventDefault();
            var deleteUrl = $(this).attr('href');
            var name = $(this).data('name') || 'this drug type';

            Swal.fire({
                icon: 'warning',
                title: 'Delete Drug Type?',
                html: 'You are about to delete drug configuration for "<strong>' + name + '</strong>".<br>This action cannot be undone and could affect linked inventory rows.',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Delete',
                cancelButtonText: 'Cancel'
            }).then(function(result) {
                if (result.isConfirmed) {
                    window.location.href = deleteUrl;
                }
            });
        });

        // Check status redirects for SweetAlert feedback Toast
        var urlParams = new URLSearchParams(window.location.search);
        var status = urlParams.get('status');
        var msg = urlParams.get('msg');
        
        if (status === 'success') {
            Swal.fire({
                icon: 'success',
                title: 'Operation Successful',
                text: msg || 'Success!',
                confirmButtonColor: '#370709'
            });
            window.history.replaceState({}, document.title, window.location.pathname);
        } else if (status === 'error' || status === 'db_error') {
            Swal.fire({
                icon: 'error',
                title: 'Operation Failed',
                text: msg || 'An error occurred.',
                confirmButtonColor: '#370709'
            });
            window.history.replaceState({}, document.title, window.location.pathname);
        }

        $('#drugTypeTable').DataTable({
            "order": [[0, "desc"]],
            "dom": '<"d-flex justify-content-between align-items-center mb-3"Bf>rt<"d-flex justify-content-between align-items-center mt-3"ip>',
            "language": {
                "search": "_INPUT_",
                "searchPlaceholder": "Search drug register..."
            },
            "buttons": [
                { extend: 'csv', text: '<i class="bi bi-filetype-csv"></i> CSV', className: 'btn btn-sm btn-success shadow-sm me-1 rounded' },
                { extend: 'pdf', text: '<i class="bi bi-file-earmark-pdf"></i> PDF', className: 'btn btn-sm btn-danger shadow-sm me-1 rounded', title: 'Registered Drug Configuration Register' },
                { extend: 'print', text: '<i class="bi bi-printer"></i> Print', className: 'btn btn-sm btn-warning shadow-sm rounded text-dark' }
            ]
        });
    });
</script>

<?php require_once '../../../includes/footer.php'; ?>