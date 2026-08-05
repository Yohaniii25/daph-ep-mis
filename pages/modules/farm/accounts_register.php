<?php
// pages/modules/farm/accounts_register.php -> Farm Accounts Register Module
require_once '../../../includes/header.php';
require_once '../../../config/db_connect.php';

if (!isset($_SESSION['role'])) {
    header("Location: ../../../index.php");
    exit();
}


$accounts_entries = [];
$total_income = 0.00;
$total_expense = 0.00;

$res = $mysqli->query("SELECT * FROM farm_accounts ORDER BY transaction_date DESC, id DESC");
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $accounts_entries[] = $r;
        if ($r['transaction_type'] === 'Income') {
            $total_income += floatval($r['amount']);
        } else {
            $total_expense += floatval($r['amount']);
        }
    }
}
$net_surplus = $total_income - $total_expense;
?>

<link rel="stylesheet" href="../../../assets/css/farm.css">

<!-- Header Section -->
<div class="row align-items-center mb-4">
    <div class="col-md-8">
        <h3 class="fw-bold text-dark m-0">
            <i class="bi bi-wallet2 me-2" style="color: #820100;"></i>Farm Accounts & Financial Register
        </h3>
        <p class="text-muted mb-0 small">Financial tracking ledger for farm revenue, expenditure, and cash book balance.</p>
    </div>
    <div class="col-md-4 text-end">
        <button class="btn btn-log-feed fw-bold px-4 text-light shadow-sm" style="background-color: #820100;" onclick="alert('Feature to log financial voucher initiated.')">
            <i class="bi bi-plus-lg me-1"></i>New Financial Entry
        </button>
    </div>
</div>

<!-- KPI SUMMARY CARDS -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm p-3 bg-white card-kpi-opening" style="border-radius: 12px;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted fw-bold uppercase d-block">Total Revenue Realized</small>
                    <span class="fs-3 fw-bold text-success">LKR <?= number_format($total_income, 2) ?></span>
                    <small class="text-muted d-block mt-1">Farm Sales & Receipts</small>
                </div>
                <div class="p-3 rounded-circle bg-success-subtle text-success">
                    <i class="bi bi-arrow-down-left-circle-fill fs-3"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm p-3 bg-white card-kpi-consumption" style="border-radius: 12px;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted fw-bold uppercase d-block">Total Operational Expenses</small>
                    <span class="fs-3 fw-bold text-danger">LKR <?= number_format($total_expense, 2) ?></span>
                    <small class="text-muted d-block mt-1">Procurement & Fuel Costs</small>
                </div>
                <div class="p-3 rounded-circle bg-danger-subtle text-danger">
                    <i class="bi bi-arrow-up-right-circle-fill fs-3"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm p-3 bg-white card-kpi-needed" style="border-radius: 12px;">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <small class="text-muted fw-bold uppercase d-block">Net Operating Surplus</small>
                    <span class="fs-3 fw-bold <?= ($net_surplus >= 0) ? 'text-primary' : 'text-danger' ?>">
                        LKR <?= number_format($net_surplus, 2) ?>
                    </span>
                    <small class="text-muted d-block mt-1">Net Financial Position</small>
                </div>
                <div class="p-3 rounded-circle bg-color-c3-light">
                    <i class="bi bi-bank fs-3"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ACCOUNTS TABLE CARD -->
<div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
    <div class="card-header bg-white py-3 border-0">
        <h5 class="fw-bold text-dark m-0">
            <i class="bi bi-receipt-cutoff me-2" style="color: #820100;"></i>Farm Financial Cash Book Ledger
        </h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="accountsRegisterTable" class="table table-bordered table-hover align-middle text-center" style="width:100%">
                <thead class="table-header-dark">
                    <tr>
                        <th style="width: 12%;">Date</th>
                        <th style="width: 14%;">Voucher / Ref No</th>
                        <th style="width: 18%;">Account Category</th>
                        <th style="width: 10%;">Type</th>
                        <th style="width: 24%;">Description / Particulars</th>
                        <th style="width: 12%;">Cash Book Ref</th>
                        <th style="width: 10%;">Amount (LKR)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($accounts_entries as $e): ?>
                        <tr>
                            <td class="fw-bold text-nowrap"><?= date('Y-m-d', strtotime($e['transaction_date'])) ?></td>
                            <td><span class="badge bg-light text-dark border px-2"><?= htmlspecialchars($e['voucher_no']) ?></span></td>
                            <td class="fw-bold text-dark"><?= htmlspecialchars($e['account_category']) ?></td>
                            <td>
                                <?php if ($e['transaction_type'] === 'Income'): ?>
                                    <span class="badge bg-success-subtle text-success border px-2"><i class="bi bi-plus-circle me-1"></i>Income</span>
                                <?php else: ?>
                                    <span class="badge bg-danger-subtle text-danger border px-2"><i class="bi bi-dash-circle me-1"></i>Expense</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-start small"><?= htmlspecialchars($e['description']) ?></td>
                            <td><span class="badge bg-light text-muted border px-2"><?= htmlspecialchars($e['cash_book_ref'] ?: '-') ?></span></td>
                            <td class="fw-bold <?= ($e['transaction_type'] === 'Income') ? 'text-success' : 'text-danger' ?>">
                                <?= ($e['transaction_type'] === 'Income' ? '+' : '-') ?> LKR <?= number_format($e['amount'], 2) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="tfoot-summary fw-bold">
                    <tr>
                        <td colspan="4" class="text-start">NET FINANCIAL BALANCE SUMMARY</td>
                        <td class="text-success">Total Income: LKR <?= number_format($total_income, 2) ?></td>
                        <td class="text-danger">Total Expenses: LKR <?= number_format($total_expense, 2) ?></td>
                        <td class="fs-6 text-primary">LKR <?= number_format($net_surplus, 2) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<?php require_once '../../../includes/footer.php'; ?>
