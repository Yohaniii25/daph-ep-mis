<?php
require_once '../../../includes/header.php';
if ($_SESSION['role'] !== 'training_officer') die("Access denied");

// Demo farmer participation data
$participation = [
    ['training_id' => 'TR-001', 'topic' => 'Modern Dairy Farming', 'farmer_name' => 'Mr. Silva', 'nic' => '198512345678', 'contact' => '071-2345678', 'attendance' => 'Present', 'feedback' => 'Very useful'],
    ['training_id' => 'TR-001', 'topic' => 'Modern Dairy Farming', 'farmer_name' => 'Ms. Perera', 'nic' => '199012345678', 'contact' => '077-3456789', 'attendance' => 'Present', 'feedback' => 'Good'],
    ['training_id' => 'TR-002', 'topic' => 'Animal Health Management', 'farmer_name' => 'Mr. Fernando', 'nic' => '197812345678', 'contact' => '076-4567890', 'attendance' => 'Absent', 'feedback' => '-'],
    ['training_id' => 'TR-003', 'topic' => 'Fodder Cultivation', 'farmer_name' => 'Mrs. Kumari', 'nic' => '196712345678', 'contact' => '075-5678901', 'attendance' => 'Present', 'feedback' => 'Excellent'],
];

$total_participants = count($participation);
$present = count(array_filter($participation, fn($p) => $p['attendance'] === 'Present'));
?>

<?php require_once '../../../includes/sidebar.php'; ?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        <h2 class="mb-4">Farmer Participation</h2>

        <!-- Quick Stats -->
        <div class="row g-4 mb-5">
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">Total Participants Recorded</h6>
                    <h2 class="text-primary"><?= $total_participants ?></h2>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">Present</h6>
                    <h2 class="text-success"><?= $present ?></h2>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">Absent</h6>
                    <h2 class="text-danger"><?= $total_participants - $present ?></h2>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4 text-center">
                    <h6 class="text-muted">Trainings Covered</h6>
                    <h2 class="text-info">4</h2>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h5>Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <button class="btn btn-success w-100 py-3" data-bs-toggle="modal" data-bs-target="#addParticipationModal">
                            <i class="bi bi-person-plus"></i><br>
                            Add Participation
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-primary w-100 py-3" >
                            <i class="bi bi-search"></i><br>
                            Search Farmers
                        </button>
                    </div>


                </div>
            </div>
        </div>

        <!-- Farmer Participation Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-dark">
                <h5 style="color: white;" class="mb-0">Farmer Participation Records</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Training ID</th>
                                <th>Topic</th>
                                <th>Farmer Name</th>
                                <th>NIC</th>
                                <th>Contact</th>
                                <th>Attendance</th>
                                <th>Feedback</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($participation as $p): ?>
                            <tr>
                                <td><strong><?= $p['training_id'] ?></strong></td>
                                <td><?= htmlspecialchars($p['topic']) ?></td>
                                <td><?= htmlspecialchars($p['farmer_name']) ?></td>
                                <td><?= $p['nic'] ?></td>
                                <td><?= $p['contact'] ?></td>
                                <td>
                                    <span class="badge bg-<?= $p['attendance'] === 'Present' ? 'success' : 'danger' ?>">
                                        <?= $p['attendance'] ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($p['feedback']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Add Participation Modal (Demo) -->
<div class="modal fade" id="addParticipationModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Add Farmer Participation</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">

                <form>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Training ID</label>
                            <select class="form-select">
                                <option>TR-001 - Modern Dairy Farming</option>
                                <option>TR-002 - Animal Health Management</option>
                                <option>TR-003 - Fodder Cultivation</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Farmer Name</label>
                            <input type="text" class="form-control" placeholder="e.g., Mr. Silva">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">NIC</label>
                            <input type="text" class="form-control" placeholder="e.g., 198512345678">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contact Number</label>
                            <input type="text" class="form-control" placeholder="e.g., 071-2345678">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Attendance</label>
                            <select class="form-select">
                                <option>Present</option>
                                <option>Absent</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Feedback</label>
                            <input type="text" class="form-control" placeholder="e.g., Very useful">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" >Save Participation</button>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../../includes/footer.php'; ?>