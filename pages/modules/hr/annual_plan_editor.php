<?php
session_start();
require_once '../../../config/db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'administrator') {
    header("Location: ../../../index.php");
    exit();
}

$current_user = $_SESSION['user_id'];

// Mock stats for the demo header
$total_targets = 5;
$overall_rating = "Above Average";

require_once '../../../includes/header.php';
require_once '../../../includes/sidebar.php';
?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold">Annual Performance Plan (APP)</h3>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb small">
                        <li class="breadcrumb-item"><a href="#">Dashboard</a></li>
                        <li class="breadcrumb-item active">Performance Editor</li>
                    </ol>
                </nav>
            </div>
            <div class="text-end">
                <span class="badge bg-success p-2 px-3 shadow-sm">
                    <i class="bi bi-shield-check me-1"></i> FINALIZED BY PD
                </span>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="text-muted small text-uppercase">Annual Targets</h6>
                        <h4 class="fw-bold mb-0">05</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm bg-primary text-white">
                    <div class="card-body">
                        <h6 class="text-white-50 small text-uppercase">Completion Rate</h6>
                        <h4 class="fw-bold mb-0">88.4%</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 border-0">
                <h6 class="mb-0 fw-bold"><i class="bi bi-list-stars me-2"></i>Target Achievement Matrix</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Target Description</th>
                                <th>Goal</th>
                                <th>Achieved</th>
                                <th style="width: 20%;">Progress</th>
                                <th>PD Status</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="ps-4">
                                    <span class="fw-bold">Vaccination Coverage - Zone A</span><br>
                                    <small class="text-muted">Amended from: #ORG-102</small>
                                </td>
                                <td>500</td>
                                <td class="fw-bold text-success">520</td>
                                <td>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-success" style="width: 100%"></div>
                                    </div>
                                </td>
                                <td><span class="badge bg-success rounded-pill px-3">Excellent</span></td>
                                <td class="text-end pe-4">
                                    <button class="btn btn-sm btn-outline-secondary" title="View PD Remarks"><i class="bi bi-chat-dots"></i></button>
                                </td>
                            </tr>

                            <tr>
                                <td class="ps-4">
                                    <span class="fw-bold">Field Staff Training</span><br>
                                    <small class="text-muted">Amended from: #ORG-105</small>
                                </td>
                                <td>12</td>
                                <td class="fw-bold text-primary">10</td>
                                <td>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-info" style="width: 83%"></div>
                                    </div>
                                </td>
                                <td><span class="badge bg-info text-dark rounded-pill px-3">Above Average</span></td>
                                <td class="text-end pe-4">
                                    <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-chat-dots"></i></button>
                                </td>
                            </tr>

                            <tr>
                                <td class="ps-4">
                                    <span class="fw-bold">Clinic Maintenance Repairs</span><br>
                                    <small class="text-muted">Amended from: #ORG-108</small>
                                </td>
                                <td>100</td>
                                <td class="fw-bold text-warning">75</td>
                                <td>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-warning" style="width: 75%"></div>
                                    </div>
                                </td>
                                <td><span class="badge bg-warning text-dark rounded-pill px-3">Average</span></td>
                                <td class="text-end pe-4">
                                    <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-chat-dots"></i></button>
                                </td>
                            </tr>

                            <tr>
                                <td class="ps-4">
                                    <span class="fw-bold">Revenue Target - Pharmacy</span><br>
                                    <small class="text-muted">Amended from: #ORG-110</small>
                                </td>
                                <td>250,000</td>
                                <td class="fw-bold text-danger">45,000</td>
                                <td>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-danger" style="width: 18%"></div>
                                    </div>
                                </td>
                                <td><span class="badge bg-danger rounded-pill px-3">Unsatisfied</span></td>
                                <td class="text-end pe-4">
                                    <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-chat-dots"></i></button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="alert alert-light border-0 mt-4 small shadow-sm">
            <i class="bi bi-info-circle me-2"></i> Performance ratings are locked after PD approval. If you need to request a re-evaluation, please contact the administrative office.
        </div>
    </main>
</div>


<?php require_once '../../../includes/footer.php'; ?>

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">

<script>
    $(document).ready(function() {
        var table = $('#advancedProgTable').DataTable({
            "order": [
                [0, "desc"]
            ],
            "responsive": true,
            "pageLength": 15,
            "dom": '<"d-flex justify-content-between align-items-center mb-3"Bf>rt<"d-flex justify-content-between align-items-center mt-3"ip>',
            "buttons": [{
                    extend: 'csv',
                    className: 'btn btn-sm btn-success me-2',
                    text: '<i class="bi bi-file-earmark-spreadsheet"></i> CSV'
                },
                {
                    extend: 'pdf',
                    className: 'btn btn-sm btn-danger me-2',
                    text: '<i class="bi bi-file-earmark-pdf"></i> PDF'
                },
                {
                    extend: 'print',
                    className: 'btn btn-sm btn-dark',
                    text: '<i class="bi bi-printer"></i> Print'
                }
            ],
            "language": {
                "search": "Search programmes:",
                "lengthMenu": "Show _MENU_ records"
            }
        });


    });
</script>

<?php
require_once 'models/add_amended_programme_modal.php';
?>