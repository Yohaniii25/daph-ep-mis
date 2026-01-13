<?php
require_once '../../../includes/header.php';
if ($_SESSION['role'] !== 'veterinary_surgeon') die("Access denied");

// Demo farmers
$farmers = [
    ['id' => 'F001', 'name' => 'Mr. Silva', 'nic' => '198512345678', 'contact' => '071-2345678', 'farm_type' => 'Dairy', 'animals' => 25, 'registered' => '2024-05-10'],
    ['id' => 'F002', 'name' => 'Ms. Perera', 'nic' => '199012345678', 'contact' => '077-3456789', 'farm_type' => 'Poultry', 'animals' => 500, 'registered' => '2023-11-15'],
    ['id' => 'F003', 'name' => 'Mr. Fernando', 'nic' => '197812345678', 'contact' => '076-4567890', 'farm_type' => 'Mixed', 'animals' => 40, 'registered' => '2025-01-05'],
    ['id' => 'F004', 'name' => 'Mrs. Kumari', 'nic' => '196712345678', 'contact' => '075-5678901', 'farm_type' => 'Dairy', 'animals' => 30, 'registered' => '2024-09-20'],
];

// Filter logic (demo)
$filtered_farmers = $farmers;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $search = trim($_GET['search'] ?? '');
    $farm_type = $_GET['farm_type'] ?? '';

    $filtered_farmers = array_filter($farmers, function ($f) use ($search, $farm_type) {
        $search_match = !$search || stripos($f['name'], $search) !== false || stripos($f['nic'], $search) !== false;
        $type_match = !$farm_type || $f['farm_type'] === $farm_type;
        return $search_match && $type_match;
    });
}
?>

<?php require_once '../../../includes/sidebar.php'; ?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        <h2 class="mb-4">Farmer List</h2>

        <!-- Filters -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0">Filters</h5>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Search (Name / NIC)</label>
                        <input type="text" name="search" class="form-control" placeholder="Type name or NIC..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Farm Type</label>
                        <select name="farm_type" class="form-select">
                            <option value="">All</option>
                            <option value="Dairy" <?= ($_GET['farm_type'] ?? '') === 'Dairy' ? 'selected' : '' ?>>Dairy</option>
                            <option value="Poultry" <?= ($_GET['farm_type'] ?? '') === 'Poultry' ? 'selected' : '' ?>>Poultry</option>
                            <option value="Mixed" <?= ($_GET['farm_type'] ?? '') === 'Mixed' ? 'selected' : '' ?>>Mixed</option>
                        </select>
                    </div>
                    <div class="col-12 text-end">
                        <button type="submit" class="btn btn-primary me-2">Apply Filters</button>
                        <a href="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Farmer Table -->
        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 style="color: white;" class="mb-0">Registered Farmers</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Farmer ID</th>
                                <th>Name</th>
                                <th>NIC</th>
                                <th>Contact</th>
                                <th>Farm Type</th>
                                <th>Animals</th>
                                <th>Registered Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($filtered_farmers)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">No farmers found matching the filters</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($filtered_farmers as $f): ?>
                                    <tr>
                                        <td><strong><?= $f['id'] ?></strong></td>
                                        <td><?= htmlspecialchars($f['name']) ?></td>
                                        <td><?= $f['nic'] ?></td>
                                        <td><?= $f['contact'] ?></td>
                                        <td><?= $f['farm_type'] ?></td>
                                        <td><?= $f['animals'] ?></td>
                                        <td><?= date('d M Y', strtotime($f['registered'])) ?></td>
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