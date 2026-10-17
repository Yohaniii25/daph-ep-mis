<?php
require_once __DIR__ . '/../config/db_connect.php';
global $mysqli;

$res = $mysqli->query("SELECT id, clinic_type, entry_date, species, tentative_diagnosis, revenue_drugs, revenue_total, cash_receipt_no FROM clinical_opd_records ORDER BY id DESC LIMIT 10");
echo "Count of records: " . $res->num_rows . "\n";
while ($r = $res->fetch_assoc()) {
    echo "ID {$r['id']} | Type: {$r['clinic_type']} | Date: {$r['entry_date']} | Species: {$r['species']} | Diag: {$r['tentative_diagnosis']} | DrugRev: {$r['revenue_drugs']} | TotalRev: {$r['revenue_total']} | Receipt: {$r['cash_receipt_no']}\n";
}
