<?php

$range_name = "Trincomalee"; // Dynamic from session
$records = [
    ['date' => '2026-03-15', 'farmer' => 'A.M. Perera', 'reg_no' => 'TR/VET/882', 'animal_type' => 'Cattle', 'brand_mark' => 'EP/TR/22', 'qty' => 12],
    ['date' => '2026-03-20', 'farmer' => 'K. Selvam', 'reg_no' => 'TR/VET/405', 'animal_type' => 'Buffalo', 'brand_mark' => 'EP/TR/45', 'qty' => 05]
];
require_once '../../../includes/header.php';
require_once '../../../includes/sidebar.php';
?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        <div class="d-flex justify-content-between mb-4">
            <h2 class="h4 fw-bold text-dark">Animal Branding & ID Registry</h2>
            <button class="btn btn-primary btn-sm"><i class="bi bi-plus-circle me-1"></i> Register New Brand</button>
        </div>
        
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <table class="table table-hover datatable align-middle">
                    <thead class="table-light small text-uppercase">
                        <tr>
                            <th>Date</th>
                            <th>Farmer / Owner</th>
                            <th>Registration No</th>
                            <th>Animal Category</th>
                            <th>Assigned Brand Mark</th>
                            <th class="text-center">Qty</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($records as $r): ?>
                        <tr>
                            <td><?= $r['date'] ?></td>
                            <td><strong><?= $r['farmer'] ?></strong></td>
                            <td><span class="badge bg-light text-dark border"><?= $r['reg_no'] ?></span></td>
                            <td><?= $r['animal_type'] ?></td>
                            <td class="font-monospace fw-bold text-primary"><?= $r['brand_mark'] ?></td>
                            <td class="text-center"><?= $r['qty'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>