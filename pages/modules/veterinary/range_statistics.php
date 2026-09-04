<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'veterinary_surgeon') {
    header("Location: ../../../index.php");
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

require_once '../../../config/db_connect.php';

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

// Fetch distinct recorded years for human population
$pop_years = [2026, 2025, 2024, 2023];
if ($range_id) {
    $stmt_yr = $mysqli->prepare("SELECT DISTINCT year FROM human_populations WHERE range_id = ? ORDER BY year DESC");
    if ($stmt_yr) {
        $stmt_yr->bind_param("i", $range_id);
        $stmt_yr->execute();
        $yr_res = $stmt_yr->get_result();
        while ($yrow = $yr_res->fetch_assoc()) {
            $pop_years[] = intval($yrow['year']);
        }
        $stmt_yr->close();
    }
}
$pop_years = array_unique($pop_years);
rsort($pop_years);

require_once '../../../includes/header.php';
?>

<link rel="stylesheet" href="../../../assets/css/bootstrap-icons.min.css">
<link rel="stylesheet" href="../../../assets/css/veterinary.css">

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="h4 mb-0 fw-bold" style="color: #370709;">Range Statistics & Overview</h2>
                <small class="text-muted"><?= htmlspecialchars($range_name) ?> | DAPH Eastern Province</small>
            </div>
        </div>

        <?php if (isset($_SESSION['msg'])): ?>
            <div class="alert alert-<?= htmlspecialchars($_SESSION['msg_type'] ?? 'info') ?> alert-dismissible fade show mb-4 shadow-sm" role="alert">
                <?= $_SESSION['msg'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['msg'], $_SESSION['msg_type']); ?>
        <?php endif; ?>

        <!-- Human Population Dynamics section -->
        <div class="row g-4 mb-5 mt-2">
            <div class="col-12">
                <div class="card gov-card">
                    <div class="card-header bg-white pt-4 px-4 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h5 class="fw-bold mb-1" style="color: #370709;"><i class="bi bi-people-fill me-2"></i>Human Population</h5>
                            <p class="text-muted small mb-0">Demographic composition tracking and sector breakdown analytics from database.</p>
                        </div>
                        <div>
                            <button type="button" class="btn btn-sm btn-dark fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#manageHumanPopulationModal">
                                <i class="bi bi-gear-fill me-1"></i> Manage Population
                            </button>
                        </div>
                    </div>
                    <div class="card-body px-4 pb-4">

                        <div class="row g-3 mb-4 p-3 rounded text-dark" style="background-color: #f8fafc; border: 1px solid #e2e8f0;">
                            <div class="col-12 col-md-4">
                                <label class="form-label small fw-bold text-secondary">Year Selection</label>
                                <select id="filterYear" class="form-select form-select-sm filter-control">
                                    <?php foreach ($pop_years as $py): ?>
                                        <option value="<?= $py ?>" <?= $py === 2025 ? 'selected' : '' ?>><?= $py ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label small fw-bold text-secondary">Ethnicity Focus</label>
                                <div class="position-relative" id="ethnicityDropdownWrapper">
                                    <button type="button"
                                        class="form-select form-select-sm text-start filter-control"
                                        id="ethnicityDropdownBtn"
                                        aria-expanded="false">
                                        All Ethnicities
                                    </button>
                                    <div class="ethnicity-dropdown-menu" id="ethnicityDropdownMenu">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="All" id="ethAll" checked>
                                            <label class="form-check-label small fw-bold" for="ethAll">All</label>
                                        </div>
                                        <hr class="dropdown-divider my-1">
                                        <div class="form-check">
                                            <input class="form-check-input ethnicity-option" type="checkbox" value="Sinhala" id="ethSinhala" checked>
                                            <label class="form-check-label small" for="ethSinhala">Sinhala</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input ethnicity-option" type="checkbox" value="Tamil" id="ethTamil" checked>
                                            <label class="form-check-label small" for="ethTamil">Tamil</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input ethnicity-option" type="checkbox" value="Muslim" id="ethMuslim" checked>
                                            <label class="form-check-label small" for="ethMuslim">Muslim</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label small fw-bold text-secondary">Population Type Metric</label>
                                <select id="filterPopType" class="form-select form-select-sm filter-control">
                                    <option value="Total Population" selected>Total Population (Male + Female)</option>
                                    <option value="Male">Male Only</option>
                                    <option value="Female">Female Only</option>
                                    <option value="Households">Households Count</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-4 mb-4">
                            <div class="col-12 col-lg-5 d-flex justify-content-center align-items-center position-relative">
                                <div style="position: relative; width: 100%; max-width: 320px; height: 320px;">
                                    <canvas id="humanPopulationPieChart"></canvas>
                                </div>
                            </div>
                            <div class="col-12 col-lg-7">
                                <div class="table-responsive">
                                    <table id="humanPopulationTable" class="table table-striped table-hover table-bordered align-middle w-100 m-0">
                                        <thead class="table-light text-secondary small">
                                            <tr>
                                                <th>Year</th>
                                                <th>Ethnicity</th>
                                                <th>Population Split</th>
                                                <th>Total Population Group</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <!-- Animal Population Dynamics section -->
        <div class="row g-4 mb-5 mt-2">
            <div class="col-12">
                <div class="card gov-card">
                    <div class="card-header bg-white pt-4 px-4 border-0">
                        <h5 class="fw-bold mb-1" style="color: #370709;"><i class="bi bi-bug-fill me-2"></i>Animal Population</h5>
                        <p class="text-muted small mb-0">Livestock demographics composition tracking and sector breakdown analytics from database.</p>
                    </div>
                    <div class="card-body px-4 pb-4">

                        <div class="row g-3 mb-4 p-3 rounded text-dark" style="background-color: #f8fafc; border: 1px solid #e2e8f0;">
                            <div class="col-12 col-md-4">
                                <label class="form-label small fw-bold text-secondary">Year Selection</label>
                                <select id="filterYearAnimal" class="form-select form-select-sm filter-control-animal">
                                    <option value="2026">2026</option>
                                    <option value="2025" selected>2025</option>
                                    <option value="2024">2024</option>
                                    <option value="2023">2023</option>
                                </select>
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label small fw-bold text-secondary">Livestock Category Focus</label>
                                <div class="position-relative" id="animalDropdownWrapper">
                                    <button type="button"
                                        class="form-select form-select-sm text-start bg-white"
                                        id="animalDropdownBtn"
                                        aria-expanded="false">
                                        All Animals Selected (6)
                                    </button>
                                    <div class="animal-dropdown-menu p-3 border shadow-sm bg-white rounded position-absolute" id="animalDropdownMenu">
                                        <div class="form-check mb-1">
                                            <input class="form-check-input" type="checkbox" value="All" id="animAll" checked>
                                            <label class="form-check-label small fw-bold" for="animAll">All</label>
                                        </div>
                                        <hr class="dropdown-divider my-1">
                                        <div class="form-check mb-1">
                                            <input class="form-check-input animal-option" type="checkbox" value="Cow" id="animCow" checked>
                                            <label class="form-check-label small" for="animCow">Cow</label>
                                        </div>
                                        <div class="form-check mb-1">
                                            <input class="form-check-input animal-option" type="checkbox" value="Buffalo" id="animBuffalo" checked>
                                            <label class="form-check-label small" for="animBuffalo">Buffalo</label>
                                        </div>
                                        <div class="form-check mb-1">
                                            <input class="form-check-input animal-option" type="checkbox" value="Goat" id="animGoat" checked>
                                            <label class="form-check-label small" for="animGoat">Goat</label>
                                        </div>
                                        <div class="form-check mb-1">
                                            <input class="form-check-input animal-option" type="checkbox" value="Chicken" id="animChicken" checked>
                                            <label class="form-check-label small" for="animChicken">Chicken</label>
                                        </div>
                                        <div class="form-check mb-1">
                                            <input class="form-check-input animal-option" type="checkbox" value="Pig" id="animPig" checked>
                                            <label class="form-check-label small" for="animPig">Pig</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input animal-option" type="checkbox" value="Others" id="animOthers" checked>
                                            <label class="form-check-label small" for="animOthers">Others</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-4 mb-4">
                            <div class="col-12 col-lg-5 d-flex justify-content-center align-items-center position-relative">
                                <div style="position: relative; width: 100%; max-width: 320px; height: 320px;">
                                    <canvas id="animalPopulationPieChart"></canvas>
                                </div>
                            </div>
                            <div class="col-12 col-lg-7">
                                <div class="table-responsive">
                                    <table id="animalPopulationTable" class="table table-striped table-hover table-bordered align-middle w-100 m-0">
                                        <thead class="table-light text-secondary small">
                                            <tr>
                                                <th>Year</th>
                                                <th>Animal Type</th>
                                                <th>Count Split</th>
                                                <th>Total Selected Group</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <!-- Annual Returns & Inventories Quick Actions -->
        <div class="card gov-card mb-5">
            <div class="card-header bg-white pt-4 px-4 border-0">
                <h5 class="fw-bold mb-1" style="color: #370709;"><i class="bi bi-grid-3x3-gap-fill me-2"></i>Annual Returns & Inventory Management</h5>
                <p class="text-muted small mb-0">Quick access links to manage annual data logs, production levels, pasture details, and livestock societies.</p>
            </div>
            <div class="card-body px-4 pb-4">
                <div class="row g-3">
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                        <a href="annual_production_levels.php" class="btn btn-primary w-100 py-3 d-flex flex-column align-items-center justify-content-center h-100 border-2">
                            <i class="bi bi-graph-up-arrow fs-3 mb-2"></i>
                            <span class="text-center">Production Levels</span>
                        </a>
                    </div>
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                        <a href="annual_pasture_lands.php" class="btn btn-success w-100 py-3 d-flex flex-column align-items-center justify-content-center h-100 border-2">
                            <i class="bi bi-tree-fill fs-3 mb-2"></i>
                            <span class="text-center">Pasture & Fodder Lands</span>
                        </a>
                    </div>
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                        <a href="annual_pasture_yields.php" class="btn btn-info w-100 py-3 d-flex flex-column align-items-center justify-content-center h-100 border-2">
                            <i class="bi bi-water fs-3 mb-2"></i>
                            <span class="text-center">Pasture Yields</span>
                        </a>
                    </div>
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                        <a href="annual_producers_processors.php" class="btn btn-warning w-100 py-3 d-flex flex-column align-items-center justify-content-center h-100 border-2 text-dark">
                            <i class="bi bi-buildings fs-3 mb-2"></i>
                            <span class="text-center">Producers & Processors</span>
                        </a>
                    </div>
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                        <a href="annual_feed_production.php" class="btn btn-danger w-100 py-3 d-flex flex-column align-items-center justify-content-center h-100 border-2">
                            <i class="bi bi-prescription2 fs-3 mb-2"></i>
                            <span class="text-center">Feed Production</span>
                        </a>
                    </div>
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                        <a href="annual_livestock_societies.php" class="btn btn-secondary w-100 py-3 d-flex flex-column align-items-center justify-content-center h-100 border-2">
                            <i class="bi bi-heart-fill fs-3 mb-2"></i>
                            <span class="text-center">Livestock Societies</span>
                        </a>
                    </div>
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                        <a href="annual_milk_collecting.php" class="btn btn-dark w-100 py-3 d-flex flex-column align-items-center justify-content-center h-100 border-2">
                            <i class="bi bi-bucket-fill fs-3 mb-2"></i>
                            <span class="text-center">Milk Collecting Centers</span>
                        </a>
                    </div>
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                        <a href="annual_milk_processing.php" class="btn btn-primary w-100 py-3 d-flex flex-column align-items-center justify-content-center h-100 border-2">
                            <i class="bi bi-gear-wide-connected fs-3 mb-2"></i>
                            <span class="text-center">Milk Processing Centers</span>
                        </a>
                    </div>
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                        <a href="annual_milk_sales.php" class="btn btn-success w-100 py-3 d-flex flex-column align-items-center justify-content-center h-100 border-2">
                            <i class="bi bi-shop fs-3 mb-2"></i>
                            <span class="text-center">Milk Sales Centers</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

<?php
include 'models/add_health_record.php';
include 'models/manage_human_population_modal.php';

ob_start();
?>
<script>
$(document).ready(function() {
    // Dynamic total calculation in manage population modal
    function updateManagePopTotal() {
        const male = parseInt($("#managePopMale").val()) || 0;
        const female = parseInt($("#managePopFemale").val()) || 0;
        $("#managePopTotalPreview").text((male + female).toLocaleString());
    }
    $(document).on("input", "#managePopMale, #managePopFemale", updateManagePopTotal);

    // Reset button in manage population modal
    $("#btnResetPopForm").on("click", function() {
        $("#manageHumanPopForm")[0].reset();
        const currentYear = $("#filterYear").val() || new Date().getFullYear();
        $("#managePopYear").val(currentYear);
        $("#managePopTotalPreview").text("0");
        $("#formTabLabel").text("Add / Update Record");
        $("#btnSavePopForm").html('<i class="bi bi-check-circle-fill me-1 text-success"></i> Save Demographics');
        $("#managePopAlertBox").empty();
    });

    // Fetch and populate recorded demographics list in modal
    function loadRecordedDemographics() {
        $.ajax({
            url: "processors/save_human_population.php?action=get_list",
            type: "GET",
            dataType: "json",
            success: function(res) {
                if (res.success) {
                    const tbody = $("#recordedDemographicsTable tbody");
                    tbody.empty();
                    $("#recordsCountBadge").text(res.data.length);
                    if (res.data.length === 0) {
                        tbody.append('<tr><td colspan="7" class="text-center py-3 text-muted">No population records found for this range.</td></tr>');
                        return;
                    }
                    res.data.forEach(function(item) {
                        const safeEth = $("<div>").text(item.ethnicity).html();
                        const row = `
                            <tr data-year="${item.year}" data-ethnicity="${safeEth}" data-male="${item.male}" data-female="${item.female}" data-households="${item.households}">
                                <td class="fw-bold">${item.year}</td>
                                <td><span class="badge bg-secondary">${safeEth}</span></td>
                                <td class="text-end text-primary font-monospace">${Number(item.male).toLocaleString()}</td>
                                <td class="text-end text-danger font-monospace">${Number(item.female).toLocaleString()}</td>
                                <td class="text-end fw-bold font-monospace" style="color: #370709;">${Number(item.total).toLocaleString()}</td>
                                <td class="text-end text-success font-monospace">${Number(item.households).toLocaleString()}</td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary btn-xs py-0 px-2 btn-edit-human-pop" title="Edit">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-danger btn-xs py-0 px-2 btn-delete-human-pop" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        `;
                        tbody.append(row);
                    });
                }
            },
            error: function() {
                $("#recordedDemographicsTable tbody").html('<tr><td colspan="7" class="text-center py-3 text-danger">Failed to load records.</td></tr>');
            }
        });
    }

    // Modal open event: pre-fill current active year & refresh demographics list
    $("#manageHumanPopulationModal").on("show.bs.modal", function() {
        const currentYear = $("#filterYear").val() || new Date().getFullYear();
        if ($("#formTabLabel").text() === "Add / Update Record") {
            $("#managePopYear").val(currentYear);
        }
        loadRecordedDemographics();
    });

    $("#btnRefreshPopList").on("click", function() {
        loadRecordedDemographics();
    });

    // Form submission via AJAX
    $("#manageHumanPopForm").on("submit", function(e) {
        e.preventDefault();
        const btn = $("#btnSavePopForm");
        const originalBtnHtml = btn.html();
        btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm me-1" role="status"></span> Saving...');

        $.ajax({
            url: "processors/save_human_population.php",
            type: "POST",
            data: $(this).serialize(),
            dataType: "json",
            success: function(res) {
                btn.prop("disabled", false).html(originalBtnHtml);
                if (res.success) {
                    Swal.fire({
                        icon: "success",
                        title: "Saved Successfully",
                        text: res.message,
                        timer: 2000,
                        showConfirmButton: false
                    });

                    // Ensure saved year exists in filter dropdown and is selected
                    const savedYear = res.year;
                    if (savedYear) {
                        if ($(`#filterYear option[value="${savedYear}"]`).length === 0) {
                            $("#filterYear").prepend(new Option(savedYear, savedYear, true, true));
                        }
                        $("#filterYear").val(savedYear);
                    }

                    // Trigger direct reload of pie chart and datatable
                    if (typeof window.fetchFilteredPopulationData === 'function') {
                        window.fetchFilteredPopulationData();
                    }

                    // Refresh modal records list
                    loadRecordedDemographics();

                    // Close the modal so the updated pie chart and table are immediately visible
                    const modalEl = document.getElementById("manageHumanPopulationModal");
                    if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                        const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
                        if (modalInstance) {
                            modalInstance.hide();
                        }
                    }
                    $("#manageHumanPopulationModal").modal("hide");
                } else {
                    Swal.fire({
                        icon: "error",
                        title: "Save Failed",
                        text: res.message || "An error occurred while saving demographics."
                    });
                }
            },
            error: function() {
                btn.prop("disabled", false).html(originalBtnHtml);
                Swal.fire({
                    icon: "error",
                    title: "Request Error",
                    text: "Server communication failed. Check connection and try again."
                });
            }
        });
    });

    // Edit button click in modal records table
    $(document).on("click", ".btn-edit-human-pop", function() {
        const row = $(this).closest("tr");
        const year = row.data("year");
        const ethnicity = row.data("ethnicity");
        const male = row.data("male");
        const female = row.data("female");
        const households = row.data("households");

        $("#managePopYear").val(year);
        $("#managePopEthnicity").val(ethnicity);
        $("#managePopMale").val(male);
        $("#managePopFemale").val(female);
        $("#managePopHouseholds").val(households);
        updateManagePopTotal();

        $("#formTabLabel").text(`Edit Record: ${ethnicity} (${year})`);
        $("#btnSavePopForm").html('<i class="bi bi-pencil-square me-1"></i> Update Demographics');

        const addTabTrigger = new bootstrap.Tab(document.getElementById("add-pop-tab"));
        addTabTrigger.show();
    });

    // Delete button click in modal records table
    $(document).on("click", ".btn-delete-human-pop", function() {
        const row = $(this).closest("tr");
        const year = row.data("year");
        const ethnicity = row.data("ethnicity");

        Swal.fire({
            icon: "warning",
            title: "Delete Demographic Record?",
            html: `Are you sure you want to delete population data for <strong>${ethnicity}</strong> in year <strong>${year}</strong>?<br><small class="text-danger">This will remove Male, Female, and Household counts for this group.</small>`,
            showCancelButton: true,
            confirmButtonColor: "#370709",
            cancelButtonColor: "#6c757d",
            confirmButtonText: '<i class="bi bi-trash-fill me-1"></i> Yes, Delete',
            cancelButtonText: "Cancel"
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "processors/save_human_population.php",
                    type: "POST",
                    data: {
                        action: "delete",
                        year: year,
                        ethnicity: ethnicity
                    },
                    dataType: "json",
                    success: function(res) {
                        if (res.success) {
                            Swal.fire({
                                icon: "success",
                                title: "Deleted",
                                text: res.message,
                                timer: 1800,
                                showConfirmButton: false
                            });
                            row.fadeOut(300, function() {
                                $(this).remove();
                                const currentCount = parseInt($("#recordsCountBadge").text()) || 1;
                                $("#recordsCountBadge").text(Math.max(0, currentCount - 1));
                                if ($("#recordedDemographicsTable tbody tr").length === 0) {
                                    $("#recordedDemographicsTable tbody").append('<tr><td colspan="7" class="text-center py-3 text-muted">No population records found for this range.</td></tr>');
                                }
                            });

                            // Refresh main page chart & table
                            if (typeof window.fetchFilteredPopulationData === 'function') {
                                window.fetchFilteredPopulationData();
                            }
                            const filterYearEl = document.getElementById("filterYear");
                            if (filterYearEl) {
                                filterYearEl.dispatchEvent(new Event("change"));
                            }
                        } else {
                            Swal.fire({
                                icon: "error",
                                title: "Deletion Failed",
                                text: res.message
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: "error",
                            title: "Error",
                            text: "Server communication error while attempting to delete."
                        });
                    }
                });
            }
        });
    });
});
</script>
<?php
$pageScripts = ob_get_clean();
require_once '../../../includes/footer.php';
?>