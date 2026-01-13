<?php
require_once '../../../includes/header.php';
if ($_SESSION['role'] !== 'veterinary_surgeon') die("Access denied");

// Demo prices
$prices = [
    ['date' => '2026-01-12', 'item' => 'Beef', 'price' => '1200', 'unit' => 'per kg'],
    ['date' => '2026-01-12', 'item' => 'Chicken', 'price' => '850', 'unit' => 'per kg'],
    ['date' => '2026-01-12', 'item' => 'Milk', 'price' => '220', 'unit' => 'per litre'],
    ['date' => '2026-01-12', 'item' => 'Eggs', 'price' => '35', 'unit' => 'each'],
];
?>

<?php require_once '../../../includes/sidebar.php'; ?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        <h2 class="mb-4">Market Price Reporting</h2>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">Today's Market Prices (LKR)</h5>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <?php foreach ($prices as $p): ?>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm text-center p-4">
                            <h5><?= $p['item'] ?></h5>
                            <h2 class="text-primary">Rs <?= number_format($p['price']) ?></h2>
                            <small class="text-muted"><?= $p['unit'] ?></small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">Recent Price Updates</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Item</th>
                                <th>Price (LKR)</th>
                                <th>Unit</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($prices as $p): ?>
                            <tr>
                                <td><?= date('d M Y', strtotime($p['date'])) ?></td>
                                <td><strong><?= $p['item'] ?></strong></td>
                                <td>Rs <?= number_format($p['price']) ?></td>
                                <td><?= $p['unit'] ?></td>
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