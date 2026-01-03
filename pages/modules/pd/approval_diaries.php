<?php
require_once '../../../includes/header.php';
if ($_SESSION['role'] !== 'provincial_director') die("Access denied");

// Demo pending approvals
$pending_approvals = [
    ['officer' => 'District DD - Amparai', 'type' => 'Diary', 'date' => '2025-12-22', 'summary' => 'Field visits and farmer meetings'],
    ['officer' => 'SMS Officer', 'type' => 'Advance Programme', 'date' => '2025-12-25', 'summary' => 'Epidemiology training schedule'],
    ['officer' => 'Farms DD', 'type' => 'Diary', 'date' => '2025-12-21', 'summary' => 'Hatchery operations review'],
];
?>

<?php require_once '../../../includes/sidebar.php'; ?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        <h2 class="mb-4">Approval of Diaries & Advance Programmes</h2>

        <div class="card shadow-sm">
            <div class="card-header bg-warning text-white">
                <h5>Pending Approvals (<?= count($pending_approvals) ?>)</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Officer</th>
                                <th>Type</th>
                                <th>Date</th>
                                <th>Summary</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending_approvals as $item): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($item['officer']) ?></strong></td>
                                <td><?= $item['type'] ?></td>
                                <td><?= date('d M Y', strtotime($item['date'])) ?></td>
                                <td><?= htmlspecialchars($item['summary']) ?></td>
                                <td>
                                    <button class="btn btn-sm btn-success me-1" disabled>Approve</button>
                                    <button class="btn btn-sm btn-danger" disabled>Return for Revision</button>
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