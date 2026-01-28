<?php
require_once '../../../includes/header.php';
if ($_SESSION['role'] !== 'district_dd') die("Access denied");

// Demo submitted entries from staff
$submitted = [
    ['id' => 1, 'staff' => 'Dr. Rizwan (VS)', 'role' => 'veterinary_surgeon', 'date' => '2026-01-12', 'title' => 'FMD Vaccination Campaign Report', 'notes' => 'Vaccinated 450 cattle in Pottuvil. No adverse reactions noted...', 'status' => 'Submitted'],
    ['id' => 2, 'staff' => 'Mr. Perera (LDO)', 'role' => 'livestock_development_officer', 'date' => '2026-01-10', 'title' => 'Farmer Training on Fodder', 'notes' => 'Conducted session for 40 farmers. Distributed 200 kg improved grass seeds...', 'status' => 'Submitted'],
    ['id' => 3, 'staff' => 'Dr. Kumari (VS)', 'role' => 'veterinary_surgeon', 'date' => '2026-01-08', 'title' => 'Mastitis Outbreak Response', 'notes' => 'Treated 15 cows in Sainthamaruthu. Advised hygiene protocol...', 'status' => 'Submitted'],
];

// Handle approve/reject (demo - updates local array)
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['entry_id'];
    $action = $_POST['action'] ?? '';

    foreach ($submitted as &$entry) {
        if ($entry['id'] === $id) {
            if ($action === 'approve') {
                $entry['status'] = 'Approved';
                $message = '<div class="alert alert-success">Entry approved!</div>';
            } elseif ($action === 'reject') {
                $entry['status'] = 'Rejected';
                $message = '<div class="alert alert-warning">Entry rejected.</div>';
            }
            break;
        }
    }
}
?>

<?php require_once '../../../includes/sidebar.php'; ?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        <h2 class="mb-4">Approval of Diaries & Advance Programmes</h2>

        <?= $message ?>

        <!-- Quick Stats -->
        <div class="row g-4 mb-5">
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">Pending Approvals</h6>
                    <h2 class="text-warning"><?= count(array_filter($submitted, fn($e) => $e['status'] === 'Submitted')) ?></h2>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">From Veterinary Surgeons</h6>
                    <h2 class="text-info"><?= count(array_filter($submitted, fn($e) => $e['role'] === 'veterinary_surgeon')) ?></h2>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">From LDOs</h6>
                    <h2 class="text-success"><?= count(array_filter($submitted, fn($e) => $e['role'] === 'livestock_development_officer')) ?></h2>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">Last Submission</h6>
                    <h2 class="text-primary">
                        <?= $submitted ? date('d M Y', strtotime($submitted[0]['date'])) : 'N/A' ?>
                    </h2>
                </div>
            </div>
        </div>

        <!-- Submitted Entries Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 style="color: white;" class="mb-0">Pending Staff Submissions</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Staff Name</th>
                                <th>Role</th>
                                <th>Date</th>
                                <th>Title</th>
                                <th>Notes (Preview)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($submitted)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">No pending submissions</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($submitted as $entry): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($entry['staff']) ?></strong></td>
                                        <td><?= ucwords(str_replace('_', ' ', $entry['role'])) ?></td>
                                        <td><?= date('d M Y', strtotime($entry['date'])) ?></td>
                                        <td><?= htmlspecialchars($entry['title']) ?></td>
                                        <td><?= htmlspecialchars(substr($entry['notes'], 0, 80)) ?>...</td>

                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<?php require_once '../../../includes/footer.php'; ?>