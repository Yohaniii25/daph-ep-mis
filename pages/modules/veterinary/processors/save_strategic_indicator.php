<?php
session_start();
require_once '../../../../config/db_connect.php';

// 1. Authorization Verification Guard
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['role']) || $_SESSION['role'] !== 'veterinary_surgeon') {
    header("Location: ../strategic_indicators.php?status=error&msg=Unauthorized");
    exit();
}

// 2. Extract Data Values
$range_id       = intval($_POST['range_id']);
$year           = intval($_POST['year']);
$strategy_pillar = trim($_POST['strategy_pillar']);
$sub_activity   = trim($_POST['sub_activity']);
$target_count   = intval($_POST['target_count']);
$achieved_count = intval($_POST['achieved_count']);

// 3. Fallback Validation Check for Core Elements
if (empty($strategy_pillar) || empty($sub_activity) || empty($range_id) || empty($year)) {
    $_SESSION['msg'] = "Required entry parameters evaluated to empty fields. Please try again.";
    $_SESSION['msg_type'] = "danger";
    header("Location: ../strategic_indicators.php?year=" . $year);
    exit();
}

// 4. Persistence Compilation Logic
$stmt = $mysqli->prepare("INSERT INTO strategic_action_indicators (year, range_id, strategy_pillar, sub_activity, target_count, achieved_count) VALUES (?, ?, ?, ?, ?, ?)");

if ($stmt) {
    $stmt->bind_param("iisssi", $year, $range_id, $strategy_pillar, $sub_activity, $target_count, $achieved_count);
    
    if ($stmt->execute()) {
        $_SESSION['msg'] = "Strategic action target criteria successfully appended.";
        $_SESSION['msg_type'] = "success";
    } else {
        $_SESSION['msg'] = "Database engine insert failed: " . htmlspecialchars($stmt->error);
        $_SESSION['msg_type'] = "danger";
    }
    $stmt->close();
} else {
    $_SESSION['msg'] = "Database engine execution initialization error: " . htmlspecialchars($mysqli->error);
    $_SESSION['msg_type'] = "danger";
}

// 5. Clean Return Link Context
header("Location: ../strategic_indicators.php?year=" . $year);
$mysqli->close();
exit();