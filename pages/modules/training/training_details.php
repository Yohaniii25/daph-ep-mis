<?php
require_once '../../../includes/header.php';
if ($_SESSION['role'] !== 'training_officer') die("Access denied");
require_once '../../../config/db_connect.php';


?>

<?php require_once '../../../includes/sidebar.php'; ?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">

<div id="layoutSidenav_content" class="bg-light">
    <main class="container-fluid px-4 pt-4">
        <h2 class="mb-4 text-dark fw-bold">Training Activities</h2>

        <div class="row g-4 mb-4">
            <div class="col-xl-4 col-md-6">
                <div class="card border-0 shadow-sm h-100 border-start border-primary border-4">
                    <div class="card-body p-4">
                        <h6 class="text-muted small text-uppercase fw-bold">Identified Target Groups</h6>
                        <h2 class="text-primary mb-0 fw-bold">8</h2>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-6">
                <div class="card border-0 shadow-sm h-100 border-start border-warning border-4">
                    <div class="card-body p-4">
                        <h6 class="text-muted small text-uppercase fw-bold">Total Participants</h6>
                        <h2 class="text-warning mb-0 fw-bold">65</h2>
                    </div>
                </div>
            </div>
            <div class="col-xl-4 col-md-6">
                <div class="card border-0 shadow-sm h-100 border-start border-success border-4">
                    <div class="card-body p-4">
                        <h6 class="text-muted small text-uppercase fw-bold">Total Allocations Run</h6>
                        <h2 class="text-success mb-0 fw-bold">40</h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 border-0">
                <h5 class="m-0 text-dark fw-bold">Quick Administrative Actions</h5>
            </div>
            <div class="card-body pt-0">
                <div class="row g-3">
                    <div class="col-md-3">
                        <button class="btn btn-success w-100 py-3 rounded-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#addTrainingModal">
                            <i class="bi bi-plus-circle fs-5 mb-1 d-block"></i> Create Training Program
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn w-100 py-3 rounded-3 shadow-sm" style="background-color: #370709; color: #ffffff;" data-bs-toggle="modal" data-bs-target="#addTargetGroupModal">
                            <i class="bi bi-people-fill fs-5 mb-1 d-block"></i> Add New Target Group
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <h5 class="m-0 fw-bold text-dark"><i class="bi bi-journal-text text-primary me-2"></i>Operational Ledger Data</h5>
                
                <ul class="nav nav-pills card-header-pills" id="ledgerTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold px-4" id="programs-tab" data-bs-toggle="tab" data-bs-target="#programs-view" type="button" role="tab" aria-controls="programs-view" aria-selected="true">
                            <i class="bi bi-calendar4-event me-2"></i>View Training Programmes
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold px-4" id="groups-tab" data-bs-toggle="tab" data-bs-target="#groups-view" type="button" role="tab" aria-controls="groups-view" aria-selected="false">
                            <i class="bi bi-tags-fill me-2"></i>View Target Groups
                        </button>
                    </li>
                </ul>
            </div>
            
            <div class="card-body">
                <div class="tab-content" id="ledgerTabsContent">
                    
                    <div class="tab-pane fade show active" id="programs-view" role="tabpanel" aria-labelledby="programs-tab">
                        <div class="table-responsive">
                            <table id="trainingProgramTable" class="table table-hover align-middle table-striped border row-border w-100">
                                <thead class="table-light text-secondary">
                                    <tr>
                                        <th style="width: 10%;">ID</th>
                                        <th style="width: 25%;">Target Group Alignment</th>
                                        <th style="width: 35%;">Allocation Details & Venue</th>
                                        <th class="text-center" style="width: 10%;">Duration</th>
                                        <th class="text-center" style="width: 10%;">Farmers Count</th>
                                        <th class="text-center" style="width: 10%;">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="font-monospace fw-bold text-muted">#0041</td>
                                        <td>
                                            <div class="fw-bold text-dark">Ampara Dairy Operators</div>
                                            <small class="text-muted font-monospace bg-light px-1.5 py-0.5 rounded border" style="font-size: 11px;">TG-041</small>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark">Modern Milking Hygiene &amp; Quality Parameters</div>
                                            <small class="text-muted d-block mt-0.5"><i class="bi bi-geo-alt-fill text-danger me-1"></i>Hall A, Main Range Center</small>
                                        </td>
                                        <td class="text-center"><span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3">3 Days</span></td>
                                        <td class="text-center font-monospace fw-bold text-dark">25</td>
                                        <td class="text-center">
                                            <span class="badge rounded-pill px-2.5 py-1" style="background-color: #370709; color: #ffffff;">Completed</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="font-monospace fw-bold text-muted">#0098</td>
                                        <td>
                                            <div class="fw-bold text-dark">Trincomalee Poultry Holders</div>
                                            <small class="text-muted font-monospace bg-light px-1.5 py-0.5 rounded border" style="font-size: 11px;">TG-098</small>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark">Bio-security Measures &amp; Outbreak Control Basics</div>
                                            <small class="text-muted d-block mt-0.5"><i class="bi bi-geo-alt-fill text-danger me-1"></i>Trincomalee Field Office</small>
                                        </td>
                                        <td class="text-center"><span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3">1 Day</span></td>
                                        <td class="text-center font-monospace fw-bold text-dark">40</td>
                                        <td class="text-center">
                                            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning-subtle rounded-pill px-2.5 py-1">Ongoing</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div class="tab-pane fade" id="groups-view" role="tabpanel" aria-labelledby="groups-tab">
                        <div class="table-responsive">
                            <table id="targetGroupTable" class="table table-hover align-middle table-striped border row-border w-100">
                                <thead class="table-light text-secondary">
                                    <tr>
                                        <th style="width: 15%;">Group Code</th>
                                        <th style="width: 30%;">Target Group Name</th>
                                        <th style="width: 40%;">Description Block / Field Context</th>
                                        <th class="text-center" style="width: 15%;">Registered Programs</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="font-monospace fw-bold text-primary">TG-041</td>
                                        <td class="fw-bold text-dark">Ampara Dairy Operators</td>
                                        <td class="text-muted small">Sustained technical livestock operators specializing in veterinary management operations within the Ampara Range.</td>
                                        <td class="text-center font-monospace"><span class="badge bg-secondary rounded-pill px-2.5">26 Runs</span></td>
                                    </tr>
                                    <tr>
                                        <td class="font-monospace fw-bold text-primary">TG-098</td>
                                        <td class="fw-bold text-dark">Trincomalee Poultry Holders</td>
                                        <td class="text-muted small">Poultry holders registered for operational bio-security tracking and regional outbreak mitigation training.</td>
                                        <td class="text-center font-monospace"><span class="badge bg-secondary rounded-pill px-2.5">14 Runs</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </main>
</div>

<?php include './models/add_training_programme.php'; ?>
<?php include './models/add_target_group.php'; ?>

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
        // Shared configuration object for standardizing DataTables instances
        const dataTableConfig = {
            "order": [[0, "desc"]],
            "dom": '<"d-flex justify-content-between align-items-center mb-3"Bf>rt<"d-flex justify-content-between align-items-center mt-3"ip>',
            "language": {
                "search": "_INPUT_",
                "searchPlaceholder": "Search ledger rows..."
            },
            "buttons": [
                { extend: 'csv', text: '<i class="bi bi-filetype-csv"></i> CSV', className: 'btn btn-sm btn-success me-1 rounded shadow-sm' },
                { extend: 'pdf', text: '<i class="bi bi-file-earmark-pdf"></i> PDF', className: 'btn btn-sm btn-danger me-1 rounded shadow-sm' },
                { extend: 'print', text: '<i class="bi bi-printer"></i> Print', className: 'btn btn-sm btn-warning text-dark rounded shadow-sm' }
            ]
        };

        // Initialize separate instances matching structural definitions
        $('#trainingProgramTable').DataTable(dataTableConfig);
        $('#targetGroupTable').DataTable({
            ...dataTableConfig,
            "order": [[0, "asc"]] // Order categories by code ordering ascendingly
        });

        // Auto-recalculating columns adjustments when switching between navigation pills
        $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
            $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
        });
    });
</script>

<?php require_once '../../../includes/footer.php'; ?>