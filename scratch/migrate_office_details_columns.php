<?php
require_once __DIR__ . '/../config/db_connect.php';

echo "Running migration for Office Details standardized tracking columns...\n";

$tables = [
    'furniture_assets' => [
        'issue_order_no' => 'VARCHAR(100) NULL',
        'received_from' => 'VARCHAR(255) NULL',
        'receipt_no' => 'VARCHAR(100) NULL',
        'specification' => 'TEXT NULL'
    ],
    'machinery_assets' => [
        'issue_order_no' => 'VARCHAR(100) NULL',
        'received_from' => 'VARCHAR(255) NULL',
        'receipt_no' => 'VARCHAR(100) NULL',
        'specification' => 'TEXT NULL'
    ],
    'instrument_assets' => [
        'issue_order_no' => 'VARCHAR(100) NULL',
        'received_from' => 'VARCHAR(255) NULL',
        'receipt_no' => 'VARCHAR(100) NULL',
        'specification' => 'TEXT NULL'
    ],
    'counterfoil_assets' => [
        'issue_order_no' => 'VARCHAR(100) NULL',
        'received_from' => 'VARCHAR(255) NULL',
        'receipt_no' => 'VARCHAR(100) NULL',
        'specification' => 'TEXT NULL'
    ],
    'registered_vehicles' => [
        'issue_order_no' => 'VARCHAR(100) NULL',
        'received_from' => 'VARCHAR(255) NULL',
        'receipt_no' => 'VARCHAR(100) NULL',
        'available_quantity' => 'INT(11) DEFAULT 1',
        'initial_count' => 'INT(11) DEFAULT 1',
        'specification' => 'TEXT NULL',
        'remarks' => 'TEXT NULL'
    ]
];

foreach ($tables as $table => $columns) {
    echo "Checking table: $table\n";
    foreach ($columns as $col => $type) {
        $chk = $mysqli->query("SHOW COLUMNS FROM `$table` LIKE '$col'");
        if ($chk && $chk->num_rows === 0) {
            $alter_sql = "ALTER TABLE `$table` ADD COLUMN `$col` $type";
            if ($mysqli->query($alter_sql)) {
                echo "  + Added column `$col` ($type)\n";
            } else {
                echo "  ! Error adding `$col`: " . $mysqli->error . "\n";
            }
        } else {
            echo "  = Column `$col` already exists\n";
        }
    }
}

echo "Migration completed successfully!\n";
