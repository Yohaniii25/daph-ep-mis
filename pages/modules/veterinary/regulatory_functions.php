<?php
session_start();

// 1. SECURITY & SESSION
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'veterinary_surgeon') {
    header("Location: ../../../../index.php");
    exit();
}

require_once '../../../config/db_connect.php';
$range_name = $_SESSION['range_name'] ?? 'Eastern Province Range';

// --- MOCK DATA FOR THE DASHBOARD (REPLACE WITH SQL SUMS LATER) ---
$stats = [
    'pending_certs' => 12,
    'expired_vehicles' => 4,
    'active_brands' => 156,
    'unpaid_fines' => '45,000.00'
];

require_once '../../../includes/header.php';
?>


        
        <div class="mb-4">
            <h2 class="h4 fw-bold text-dark">Regulatory & Enforcement Dashboard</h2>
            <p class="text-muted small">Managing Legal Compliance & Veterinary Oversight for <strong><?= htmlspecialchars($range_name) ?></strong></p>
        </div>
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h6 class="mb-0 fw-bold">Quick Actions</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                    <a href="animal_branding.php" class="btn btn-primary w-100 py-3">
                            <i class="bi bi-search fs-3"></i><br>
                            Animal Branding
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="health_certificates.php" class="btn btn-secondary w-100 py-3">
                            <i class="bi bi-search fs-3"></i><br>
                            Health Certificates
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="vehicle_fitness.php" class="btn btn-success w-100 py-3">
                            <i class="bi bi-search fs-3"></i><br>
                            Vehicle Fitness
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="regulatory_compounding.php" class="btn btn-warning w-100 py-3">
                            <i class="bi bi-search fs-3"></i><br>
                            Compounding
                        </a>
                    </div>

                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 border-0">
                <h6 class="mb-0 fw-bold"><i class="bi bi-clock-history me-2"></i>Recent Regulatory Activities</h6>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="small text-muted text-uppercase">
                            <tr>
                                <th>Category</th>
                                <th>Reference</th>
                                <th>Entity / Person</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            <tr>
                                <td><span class="text-primary fw-bold">Branding</span></td>
                                <td>#BR-9902</td>
                                <td>W.A. Kumara</td>
                                <td>2026-03-28</td>
                                <td><span class="badge bg-success-subtle text-success">Completed</span></td>
                                <td class="text-end"><button class="btn btn-link btn-sm p-0"><i class="bi bi-eye"></i></button></td>
                            </tr>
                            <tr>
                                <td><span class="text-success fw-bold">Certificate</span></td>
                                <td>#HC-4410</td>
                                <td>Inter-District Transport</td>
                                <td>2026-03-27</td>
                                <td><span class="badge bg-warning-subtle text-warning text-dark">Processing</span></td>
                                <td class="text-end"><button class="btn btn-link btn-sm p-0"><i class="bi bi-eye"></i></button></td>
                            </tr>
                            <tr>
                                <td><span class="text-danger fw-bold">Penalty</span></td>
                                <td>#CMP-201</td>
                                <td>M.N. Fazeer</td>
                                <td>2026-03-25</td>
                                <td><span class="badge bg-danger-subtle text-danger">Unpaid</span></td>
                                <td class="text-end"><button class="btn btn-link btn-sm p-0"><i class="bi bi-eye"></i></button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <?php require_once '../../../includes/footer.php'; ?>