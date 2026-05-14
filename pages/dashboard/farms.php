<?php
// pages/dashboard/farms.php
if ($_SESSION['role'] !== 'farms_dd') die("Access denied");
require_once './includes/header.php';
require_once './includes/sidebar.php';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 pt-4">
        <h2 class="mb-5 text-dark">Farms Operations Dashboard</h2>

        <!-- 4 Key Cards -->
        <div class="row g-4 mb-5">
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4">
                    <h6 class="text-muted mb-3">Parent Stock Population</h6>
                    <h2 class="text-primary mb-2">8,450</h2>
                    <small class="text-success"><i class="bi bi-arrow-up"></i> 6.2% Up from yesterday</small>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4">
                    <h6 class="text-muted mb-3">Today's Egg Collection</h6>
                    <h2 class="text-info mb-2">12,300</h2>
                    <small class="text-success"><i class="bi bi-arrow-up"></i> 4.8% Up from yesterday</small>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4">
                    <h6 class="text-muted mb-3">Average Hatchability</h6>
                    <h2 class="text-warning mb-2">89%</h2>
                    <small class="text-danger"><i class="bi bi-arrow-down"></i> 2.1% Down from yesterday</small>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card border-0 shadow-sm h-100 p-4">
                    <h6 class="text-muted mb-3">Outlet Sales Revenue</h6>
                    <h2 class="text-success mb-2">LKR 2.1 M</h2>
                    <small class="text-success"><i class="bi bi-arrow-up"></i> 15.3% Up from last month</small>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <!-- 1. Stock Categorization (Donut Chart) -->
            <div class="col-xl-5">
                <div class="card border-0 shadow-sm h-100" style="border-radius: 14px;">
                    <div class="card-header bg-white border-0 pt-4 pb-3 px-4 d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-0 fw-semibold text-dark" style="font-weight: bold !important; letter-spacing: .01em;">Stock Categorization</h4>
                            <small class="text-muted">Farm inventory breakdown</small>
                        </div>
                        <i class="bi bi-pie-chart text-primary" style="font-size: 24px; opacity: 0.7;"></i>
                    </div>
                    <div class="card-body px-4 pb-4 d-flex flex-column align-items-center">
                        <div class="position-relative mx-auto mb-4" style="width:200px; height:200px;">
                            <canvas id="stockPieChart"></canvas>
                            <div class="position-absolute top-50 start-50 translate-middle text-center">
                                <div class="fw-bold" style="font-size:24px; line-height:1.1; color:#1e293b;">12.5K</div>
                                <div class="text-muted fw-500" style="font-size:12px;">total stock</div>
                            </div>
                        </div>
                        <!-- Legend -->
                        <div class="w-100 d-flex flex-column gap-3 mt-2">
                            <div class="d-flex justify-content-between align-items-center px-2">
                                <span class="d-flex align-items-center gap-2" style="font-size:13px; color:#475569; font-weight:500;">
                                    <span class="rounded-circle d-inline-block" style="width:10px;height:10px;background:#3b82f6;"></span>Breeders
                                </span>
                                <span class="fw-semibold" style="font-size:13px; color:#1e293b;">4,500</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center px-2">
                                <span class="d-flex align-items-center gap-2" style="font-size:13px; color:#475569; font-weight:500;">
                                    <span class="rounded-circle d-inline-block" style="width:10px;height:10px;background:#10b981;"></span>Layers
                                </span>
                                <span class="fw-semibold" style="font-size:13px; color:#1e293b;">3,500</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center px-2">
                                <span class="d-flex align-items-center gap-2" style="font-size:13px; color:#475569; font-weight:500;">
                                    <span class="rounded-circle d-inline-block" style="width:10px;height:10px;background:#f59e0b;"></span>Chicks
                                </span>
                                <span class="fw-semibold" style="font-size:13px; color:#1e293b;">4,500</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Commercial Production Records Table -->
            <div class="col-xl-7">
                <div class="card border-0 shadow-sm h-100" style="border-radius: 14px;">
                    <div class="card-header bg-white border-0 pt-3 pb-2 px-4 d-flex justify-content-between align-items-center">
                        <h4 class="mb-0 fw-semibold text-dark" style="font-weight: bold !important; letter-spacing: .01em;">Commercial Production Records</h4>
                        <div class="input-group input-group-sm" style="width: 200px;">
                            <span class="input-group-text border-0 bg-light text-muted"><i class="bi bi-search" style="font-size:12px;"></i></span>
                            <input type="text" class="form-control border-0 bg-light" placeholder="Search records..." style="font-size:12px;">
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table mb-0 align-middle">
                            <thead>
                                <tr style="background:#f8fafc;">
                                    <th class="px-4 py-3 text-uppercase fw-semibold" style="font-size:11px; color:#94a3b8; letter-spacing:.05em;">Region</th>
                                    <th class="py-3 text-uppercase fw-semibold" style="font-size:11px; color:#94a3b8; letter-spacing:.05em;">Farm ID</th>
                                    <th class="py-3 text-uppercase fw-semibold" style="font-size:11px; color:#94a3b8; letter-spacing:.05em;">Product</th>
                                    <th class="py-3 text-uppercase fw-semibold" style="font-size:11px; color:#94a3b8; letter-spacing:.05em;">Qty</th>
                                    <th class="py-3 text-uppercase fw-semibold" style="font-size:11px; color:#94a3b8; letter-spacing:.05em;">Grade</th>
                                    <th class="py-3 text-uppercase fw-semibold" style="font-size:11px; color:#94a3b8; letter-spacing:.05em;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr style="border-top: 1px solid #f1f5f9;">
                                    <td class="px-4 py-3 fw-medium" style="font-size:13px;">Sathurukondan</td>
                                    <td style="font-size:12px; font-family:monospace; color:#94a3b8;">#001</td>
                                    <td style="font-size:13px;">Poultry</td>
                                    <td style="font-size:13px;">700</td>
                                    <td><span style="font-size:12px; font-weight:600; color:#1d4ed8;">Grade A</span></td>
                                    <td>
                                        <span class="d-inline-flex align-items-center gap-1 px-3 py-1 rounded-pill" style="background:#dcfce7; color:#15803d; font-size:11px; font-weight:500;">
                                            <span class="rounded-circle" style="width:5px;height:5px;background:currentColor;display:inline-block;"></span>Approved
                                        </span>
                                    </td>
                                </tr>
                                <tr style="border-top: 1px solid #f1f5f9;">
                                    <td class="px-4 py-3 fw-medium" style="font-size:13px;">Thampalakamam</td>
                                    <td style="font-size:12px; font-family:monospace; color:#94a3b8;">#002</td>
                                    <td style="font-size:13px;">Poultry</td>
                                    <td style="font-size:13px;">700</td>
                                    <td><span style="font-size:12px; font-weight:600; color:#1d4ed8;">Grade A</span></td>
                                    <td>
                                        <span class="d-inline-flex align-items-center gap-1 px-3 py-1 rounded-pill" style="background:#dcfce7; color:#15803d; font-size:11px; font-weight:500;">
                                            <span class="rounded-circle" style="width:5px;height:5px;background:currentColor;display:inline-block;"></span>Approved
                                        </span>
                                    </td>
                                </tr>
                                <tr style="border-top: 1px solid #f1f5f9;">
                                    <td class="px-4 py-3 fw-medium" style="font-size:13px;">Thirukkovil</td>
                                    <td style="font-size:12px; font-family:monospace; color:#94a3b8;">#003</td>
                                    <td style="font-size:13px;">Poultry</td>
                                    <td style="font-size:13px;">350</td>
                                    <td><span style="font-size:12px; font-weight:600; color:#6d28d9;">Grade B</span></td>
                                    <td>
                                        <span class="d-inline-flex align-items-center gap-1 px-3 py-1 rounded-pill" style="background:#dcfce7; color:#15803d; font-size:11px; font-weight:500;">
                                            <span class="rounded-circle" style="width:5px;height:5px;background:currentColor;display:inline-block;"></span>Approved
                                        </span>
                                    </td>
                                </tr>
                                <tr style="border-top: 1px solid #f1f5f9;">
                                    <td class="px-4 py-3 fw-medium" style="font-size:13px;">Morawewa</td>
                                    <td style="font-size:12px; font-family:monospace; color:#94a3b8;">#005</td>
                                    <td style="font-size:13px;">Livestock</td>
                                    <td style="font-size:13px;">420</td>
                                    <td><span style="font-size:12px; font-weight:600; color:#1d4ed8;">Grade A</span></td>
                                    <td>
                                        <span class="d-inline-flex align-items-center gap-1 px-3 py-1 rounded-pill" style="background:#fef9c3; color:#a16207; font-size:11px; font-weight:500;">
                                            <span class="rounded-circle" style="width:5px;height:5px;background:currentColor;display:inline-block;"></span>Pending
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- 3. Sales Outlet Stock Levels -->
            <div class="col-xl-5">
                <div class="card border-0 shadow-sm h-100" style="border-radius: 14px;">
                    <div class="card-header bg-white border-0 pt-4 pb-3 px-4 d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-0 fw-semibold text-dark" style="font-weight: bold !important; letter-spacing: .01em;">Outlet Stock &amp; Revenue</h4>
                            <small class="text-muted">Sales performance by location</small>
                        </div>
                        <i class="bi bi-shop text-primary" style="font-size: 24px; opacity: 0.7;"></i>
                    </div>
                    <div class="card-body px-4 pb-4">
                        <?php
                        $outlets = [
                            ['name' => 'Colombo', 'rev' => 'LKR 3.3M', 'pct' => 75, 'color' => '#3b82f6'],
                            ['name' => 'Kandy',   'rev' => 'LKR 2.1M', 'pct' => 50, 'color' => '#10b981'],
                            ['name' => 'Ampara',  'rev' => 'LKR 1.8M', 'pct' => 40, 'color' => '#f59e0b'],
                        ];
                        foreach ($outlets as $o): ?>
                            <div class="mb-4">
                                <div class="d-flex justify-content-between align-items-baseline mb-2">
                                    <span class="fw-semibold" style="font-size:13px; color:#1e293b;"><?= $o['name'] ?></span>
                                    <span style="font-size:13px; font-family:monospace; color:#64748b; font-weight:500;"><?= $o['rev'] ?></span>
                                </div>
                                <div class="rounded-pill overflow-hidden" style="height:8px; background:#f1f5f9;">
                                    <div class="rounded-pill" style="height:100%; width:<?= $o['pct'] ?>%; background:<?= $o['color'] ?>;"></div>
                                </div>
                                <div style="font-size:12px; color:#94a3b8; margin-top:4px;"><?= $o['pct'] ?>% capacity</div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- 4. Revenue Generation Chart -->
            <div class="col-xl-7">
                <div class="card border-0 shadow-sm h-100" style="border-radius: 14px;">
                    <div class="card-header bg-white border-0 pt-4 pb-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h4 class="mb-0 fw-semibold text-dark" style="font-weight: bold !important; letter-spacing: .01em;">Total Generated Revenue</h4>
                            <small class="text-muted">Jan – Jun 2025</small>
                        </div>
                        <span class="px-3 py-1 rounded-pill" style="font-size:11px; font-weight:500; background:#dcfce7; color:#15803d;">
                            <i class="bi bi-arrow-up"></i> 15.3%
                        </span>
                    </div>
                    <div class="card-body px-4 pb-4">
                        <canvas id="revenueLineChart" height="130"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<style>
    .bg-success-soft {
        background-color: #dcfce7;
    }

    .bg-warning-soft {
        background-color: #fef9c3;
    }
</style>

<script>
    // 1. Stock Categorization Pie Chart
    new Chart(document.getElementById('stockPieChart'), {
        type: 'doughnut',
        data: {
            labels: ['Breeders', 'Layers', 'Chicks'],
            datasets: [{
                data: [4500, 3500, 4500],
                backgroundColor: ['#3b82f6', '#10b981', '#f59e0b'],
                borderColor: '#ffffff',
                borderWidth: 3,
                cutout: '65%'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    titleFont: { size: 13, weight: 'bold' },
                    bodyFont: { size: 12 },
                    callbacks: {
                        label: function(context) {
                            return context.label + ': ' + context.parsed + ' units';
                        }
                    }
                }
            }
        }
    });

    // 2. Revenue Line Chart
    new Chart(document.getElementById('revenueLineChart'), {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            datasets: [{
                label: 'Revenue (M)',
                data: [1.2, 1.5, 1.1, 1.8, 2.1, 1.9],
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.08)',
                fill: true,
                tension: 0.4,
                pointRadius: 5,
                pointBackgroundColor: '#3b82f6',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    },
                    ticks: {
                        font: { size: 12 }
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: { size: 12 }
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    titleFont: { size: 13, weight: 'bold' },
                    bodyFont: { size: 12 }
                }
            }
        }
    });
</script>

<?php require_once './includes/footer.php'; ?>