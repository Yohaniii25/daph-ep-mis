<?php
require_once '../../../includes/header.php';
if ($_SESSION['role'] !== 'sms') die("Access denied");
require_once '../../../config/db_connect.php';

// Fetch live counts for metric card fallback tracking dynamically
$count_query = "SELECT COUNT(*) AS total_types FROM `vaccine_types`";
$count_res = $mysqli->query($count_query);
$total_types = ($count_res) ? $count_res->fetch_assoc()['total_types'] : 0;

?>

<?php require_once '../../../includes/sidebar.php'; ?>

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

    .metric-label-text {
        font-size: 0.92rem !important;
        font-weight: 500 !important;
        color: #8a92a6 !important;
        margin-bottom: 12px !important;
    }

    .metric-number-display {
        font-size: 2.45rem !important;
        font-weight: 800 !important;
        line-height: 1 !important;
        letter-spacing: -1px !important;
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

<div id="layoutSidenav_content" class="bg-light">
    <main class="container-fluid px-4 pt-4">
        <h2 class="mb-4">Immunization - Vaccination Types</h2>



        <div class="row g-4 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 border-start border-primary border-4">
                    <div class="card-body p-4">
                        <h6 class="text-muted small text-uppercase fw-bold">Total Vaccinations</h6>
                        <h2 class="text-primary mb-0 fw-bold"><?= number_format($total_types) ?></h2>

                    </div>
                </div>
            </div>

        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h5>Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <button class="btn btn-success w-100 py-3" data-bs-toggle="modal" data-bs-target="#addVaccineTypeModal">
                            <i class="bi bi-journal-text"></i><br>
                            Add New Vaccine Type
                        </button>
                    </div>

                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-3 mb-5">
            <div class="card-header bg-white py-3 border-0">
                <h5 class="m-0 fw-bold text-dark"><i class="bi bi-bookmark-star me-2 text-success"></i>Registered Vaccine Configuration Register</h5>
            </div>
            <div class="card-body">
                <table id="vaccineTypeTable" class="table table-striped align-middle row-border" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 10%;">Type ID</th>
                            <th style="width: 30%;">Vaccine Common Name</th>
                            <th style="width: 25%;">Target Animal Classification</th>
                            <th style="width: 20%;">Description Notes</th>
                            <th style="width: 15%;" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $ledger_sql = "SELECT * FROM `vaccine_types` ORDER BY `id` DESC";
                        $res = $mysqli->query($ledger_sql);
                        if ($res && $res->num_rows > 0):
                            while ($row = $res->fetch_assoc()):
                        ?>
                                <tr>
                                    <td class="fw-bold text-secondary">#<?= $row['id'] ?></td>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($row['vaccine_name']) ?></td>
                                    <td>
                                        <?php
                                        $animals = array_filter(array_map('trim', explode(',', $row['target_animal'])));
                                        foreach ($animals as $animal): ?>
                                            <span class="badge bg-secondary px-2 py-1.5 fs-7 me-1">
                                                <i class="bi bi-tag me-1"></i><?= htmlspecialchars($animal) ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </td>
                                    <td class="text-muted text-truncate" style="max-width: 250px;">
                                        <?= !empty($row['description']) ? htmlspecialchars($row['description']) : '<em class="text-light-emphasis">No additional details recorded.</em>' ?>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-secondary edit-type-btn"
                                                data-id="<?= $row['id'] ?>"
                                                data-name="<?= htmlspecialchars($row['vaccine_name'], ENT_QUOTES) ?>"
                                                data-animal="<?= htmlspecialchars($row['target_animal'], ENT_QUOTES) ?>"
                                                data-desc="<?= htmlspecialchars($row['description'] ?? '', ENT_QUOTES) ?>"
                                                data-bs-toggle="modal" data-bs-target="#addVaccineTypeModal">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <a href="processors/vaccine_type_crud.php?action=delete&id=<?= $row['id'] ?>"
                                                class="btn btn-outline-danger"
                                                onclick="return confirm('Are you sure you want to delete this vaccine configuration? This could affect linked inventory rows.');">
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

<?php include './models/vaccine_type_modal.php'; ?>

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
                    arr.map(a => `<span class="badge bg-primary me-1">${a}</span>`).join('')
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

        $('.edit-type-btn').on('click', function() {
            $('#modalAction').val('update');
            $('#typeId').val($(this).data('id'));
            $('#vaccineName').val($(this).data('name'));
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

            $('#modalTitle').html('<i class="bi bi-pencil-square me-2 text-warning"></i>Modify Vaccine Type Configuration');
            $('#submitBtn').removeClass('btn-success').addClass('btn-warning').text('Save Modifications');
        });

        $('#addVaccineTypeModal').on('hidden.bs.modal', function() {
            $('#modalAction').val('create');
            $('#typeId').val('');
            $('#typeForm')[0].reset();
            resetAnimalSelection();

            $('#modalTitle').html('<i class="bi bi-patch-plus me-2"></i>Add New Vaccine Variant Type');
            $('#submitBtn').removeClass('btn-warning').addClass('btn-success').text('Save Configuration');
        });

        $('#typeForm').on('submit', function() {
            updateAnimalHidden();
            return true;
        });

        $('#vaccineTypeTable').DataTable({
            "order": [
                [0, "desc"]
            ],
            "dom": '<"d-flex justify-content-between align-items-center mb-3"Bf>rt<"d-flex justify-content-between align-items-center mt-3"ip>',
            "language": {
                "search": "_INPUT_",
                "searchPlaceholder": "Search vaccine register..."
            },
            "buttons": [{
                    extend: 'csv',
                    text: '<i class="bi bi-filetype-csv"></i> CSV',
                    className: 'btn btn-sm btn-success shadow-sm me-1 rounded',
                    titleAttr: 'Export Filtered CSV'
                },
                {
                    extend: 'pdf',
                    text: '<i class="bi bi-file-earmark-pdf"></i> PDF',
                    className: 'btn btn-sm btn-danger shadow-sm me-1 rounded',
                    title: 'Registered Vaccine Types Configuration Register'
                },
                {
                    extend: 'print',
                    text: '<i class="bi bi-printer"></i> Print',
                    className: 'btn btn-sm btn-warning shadow-sm rounded text-dark'
                }
            ]
        });
    });
</script>

<?php
require_once '../../../includes/footer.php';
?>