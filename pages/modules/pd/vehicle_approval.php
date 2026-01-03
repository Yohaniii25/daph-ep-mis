<?php
require_once '../../../includes/header.php';
if ($_SESSION['role'] !== 'provincial_director') die("Access denied");

// Demo requests
$vehicle_requests = [
    ['req_no' => 'VREQ-2025-045', 'officer' => 'District DD - Batticaloa', 'vehicle' => 'Double Cab DC-1234', 'purpose' => 'Field visit to dairy farms', 'date' => '2025-12-28', 'status' => 'Pending'],
    ['req_no' => 'VREQ-2025-044', 'officer' => 'SMS Officer', 'vehicle' => 'Van VN-5678', 'purpose' => 'Disease investigation - Trincomalee', 'date' => '2025-12-26', 'status' => 'Approved'],
    ['req_no' => 'VREQ-2025-043', 'officer' => 'Farms DD', 'vehicle' => 'Lorry LR-9012', 'purpose' => 'Fodder transport', 'date' => '2025-12-24', 'status' => 'Pending'],
];
?>

<?php require_once '../../../includes/sidebar.php'; ?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        <h2 class="mb-4">Approval of Taking Vehicles Out of the District</h2>

        <div class="card shadow-sm">
            <div class="card-header bg-danger text-white">
                <h5>Vehicle Movement Requests</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Request No.</th>
                                <th>Officer</th>
                                <th>Vehicle</th>
                                <th>Purpose</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($vehicle_requests as $req): ?>
                            <tr>
                                <td><strong><?= $req['req_no'] ?></strong></td>
                                <td><?= htmlspecialchars($req['officer']) ?></td>
                                <td><?= $req['vehicle'] ?></td>
                                <td><?= htmlspecialchars($req['purpose']) ?></td>
                                <td><?= date('d M Y', strtotime($req['date'])) ?></td>
                                <td>
                                    <span class="badge bg-<?= $req['status'] === 'Approved' ? 'success' : ($req['status'] === 'Rejected' ? 'danger' : 'warning') ?>">
                                        <?= $req['status'] ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($req['status'] === 'Pending'): ?>
                                    <button class="btn btn-sm btn-success me-1" disabled>Approve</button>
                                    <button class="btn btn-sm btn-danger" disabled>Reject</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<?php require_once '../../../includes/footer.php'; ?>