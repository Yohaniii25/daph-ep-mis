<?php
require_once '../../../includes/header.php';

if ($_SESSION['role'] !== 'administrator') {
    die("Access denied");
}

// No DB needed — demo data only
$message = '';

// Demo RTI Requests
$rti_requests = [
    [
        'request_no' => 'RTI-2025-001',
        'date_received' => '2025-12-15',
        'applicant' => 'Citizen A (Amparai)',
        'subject' => 'Details of PSDG project expenditure 2025',
        'status' => 'Processing',
        'response_due' => '2025-12-30'
    ],
    [
        'request_no' => 'RTI-2025-002',
        'date_received' => '2025-12-18',
        'applicant' => 'NGO Representative',
        'subject' => 'List of registered livestock farms in Batticaloa',
        'status' => 'Responded',
        'response_due' => '2026-01-02'
    ],
    [
        'request_no' => 'RTI-2025-003',
        'date_received' => '2025-12-20',
        'applicant' => 'Journalist',
        'subject' => 'Revenue from veterinary services 2024-2025',
        'status' => 'Processing',
        'response_due' => '2026-01-04'
    ],
    [
        'request_no' => 'RTI-2025-004',
        'date_received' => '2025-12-10',
        'applicant' => 'Farmer Association',
        'subject' => 'Stock levels of vaccines in Trincomalee',
        'status' => 'Responded',
        'response_due' => '2025-12-25'
    ],
    [
        'request_no' => 'RTI-2025-005',
        'date_received' => '2025-12-22',
        'applicant' => 'Citizen B',
        'subject' => 'Staff leave records of Provincial Office',
        'status' => 'Rejected',
        'response_due' => '2026-01-06'
    ]
];
?>

<?php require_once '../../../includes/sidebar.php'; ?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        <h2 class="mb-4">Right to Information (RTI) Activities Management</h2>

        <?= $message ?>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">RTI Requests Overview</h5>
                <div>
                    <button class="btn btn-light me-2" disabled>Log New Request</button>
                    <button class="btn btn-outline-light" disabled>Export Report</button>
                </div>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-3">
                        <h4 class="text-primary">5</h4>
                        <p class="mb-0">Total Requests</p>
                    </div>
                    <div class="col-md-3">
                        <h4 class="text-warning">2</h4>
                        <p class="mb-0">Processing</p>
                    </div>
                    <div class="col-md-3">
                        <h4 class="text-success">2</h4>
                        <p class="mb-0">Responded</p>
                    </div>
                    <div class="col-md-3">
                        <h4 class="text-danger">1</h4>
                        <p class="mb-0">Rejected</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- RTI Requests Table -->
        <div class="card shadow-sm">
            <div class="card-header">
                <h5>RTI Requests Log</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>REQUEST NO.</th>
                                <th>DATE RECEIVED</th>
                                <th>APPLICANT</th>
                                <th>SUBJECT</th>
                                <th>STATUS</th>
                                <th>RESPONSE DUE</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rti_requests as $rti): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($rti['request_no']) ?></strong></td>
                                <td><?= date('d M Y', strtotime($rti['date_received'])) ?></td>
                                <td><?= htmlspecialchars($rti['applicant']) ?></td>
                                <td><?= htmlspecialchars($rti['subject']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $rti['status'] === 'Responded' ? 'success' : ($rti['status'] === 'Rejected' ? 'danger' : 'warning') ?>">
                                        <?= $rti['status'] ?>
                                    </span>
                                </td>
                                <td><?= date('d M Y', strtotime($rti['response_due'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="mt-4 text-center text-muted">
            <small>* Demo mode - Real RTI logging and response tracking will be implemented in Phase 2</small>
        </div>
    </main>
</div>

<?php require_once '../../../includes/footer.php'; ?>