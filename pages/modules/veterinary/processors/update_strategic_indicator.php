<?php
session_start();
require_once '../../../../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['role']) || $_SESSION['role'] !== 'veterinary_surgeon') {
    header("Location: ../strategic_indicators.php?status=error&msg=Unauthorized");
    exit();
}

$id              = intval($_POST['id'] ?? 0);
$year            = intval($_POST['year'] ?? 2026);
$range_id        = $_SESSION['range_id'] ?? null;
$strategy_pillar = trim($_POST['strategy_pillar'] ?? '');
$sub_activity    = trim($_POST['sub_activity'] ?? '');
$target_count    = intval($_POST['target_count'] ?? 0);
$achieved_count  = intval($_POST['achieved_count'] ?? 0);

if ($id <= 0 || empty($strategy_pillar) || empty($sub_activity) || empty($range_id)) {
    $_SESSION['msg'] = "Required entry parameters evaluated to empty fields. Please try again.";
    $_SESSION['msg_type'] = "danger";
    header("Location: ../strategic_indicators.php?year=" . $year);
    exit();
}

$stmt = $mysqli->prepare("UPDATE strategic_action_indicators SET strategy_pillar = ?, sub_activity = ?, target_count = ?, achieved_count = ? WHERE id = ? AND range_id = ?");

if ($stmt) {
    $stmt->bind_param("ssiiii", $strategy_pillar, $sub_activity, $target_count, $achieved_count, $id, $range_id);
    
    if ($stmt->execute()) {
        $_SESSION['msg'] = "Strategic action indicator successfully updated.";
        $_SESSION['msg_type'] = "success";
    } else {
        $_SESSION['msg'] = "Database engine update failed: " . htmlspecialchars($stmt->error);
        $_SESSION['msg_type'] = "danger";
    }
    $stmt->close();
} else {
    $_SESSION['msg'] = "Database engine execution initialization error: " . htmlspecialchars($mysqli->error);
    $_SESSION['msg_type'] = "danger";
}

header("Location: ../strategic_indicators.php?year=" . $year);
$mysqli->close();
exit();
