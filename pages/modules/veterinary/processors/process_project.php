<?php
session_start();
require_once __DIR__ . '/../../../config/db_connect.php';

/** @var mysqli $mysqli */
global $mysqli;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['logged_in'])) {
    
    $range_id                          = intval($_SESSION['range_id'] ?? 1);
    $range_office_name                 = trim($_POST['range_office_name'] ?? '');
    $project_type                      = trim($_POST['project_type'] ?? 'PSDG');
    $project_name                      = trim($_POST['project_name'] ?? '');
    $activity                          = !empty($_POST['activity']) ? trim($_POST['activity']) : $project_name;
    $funding_agent                     = trim($_POST['funding_agent'] ?? '');
    $target_beneficiaries              = intval($_POST['target_beneficiaries'] ?? 0);
    $start_date                        = !empty($_POST['start_date']) ? $_POST['start_date'] : date('Y-m-d');
    $end_date                          = !empty($_POST['end_date']) ? $_POST['end_date'] : date('Y-m-d', strtotime('+6 months'));
    
    // Financial & Dual Contribution Tracking
    $dept_contribution_amount          = floatval($_POST['dept_contribution_amount'] ?? 0.00);
    $dept_subsidy_percentage           = floatval($_POST['dept_subsidy_percentage'] ?? 100.00);
    $dept_contribution_activity        = trim($_POST['dept_contribution_activity'] ?? '');
    
    $beneficiary_contribution_amount   = floatval($_POST['beneficiary_contribution_amount'] ?? 0.00);
    $beneficiary_contribution_activity = trim($_POST['beneficiary_contribution_activity'] ?? '');
    
    $summary                           = trim($_POST['summary'] ?? '');
    $priority                          = trim($_POST['priority'] ?? 'Medium');

    // Auto-populate funding agent if left empty based on project type
    if (empty($funding_agent)) {
        if ($project_type === 'PSDG') {
            $funding_agent = 'PSDG / Provincial Ministry of Agriculture';
        } elseif ($project_type === 'LMP') {
            $funding_agent = 'LMP / Livestock Development Programme';
        } elseif ($project_type === 'CBG') {
            $funding_agent = 'CBG / Community Based Grant';
        } elseif ($project_type === 'Special') {
            $funding_agent = 'Special Ministry Works';
        } else {
            $funding_agent = 'Department of Animal Production & Health';
        }
    }

    // Auto-populate range office name if empty
    if (empty($range_office_name) && !empty($range_id)) {
        $r_stmt = $mysqli->prepare("SELECT name FROM veterinary_ranges WHERE id = ?");
        if ($r_stmt) {
            $r_stmt->bind_param("i", $range_id);
            $r_stmt->execute();
            $r_res = $r_stmt->get_result()->fetch_assoc();
            if ($r_res) {
                $range_office_name = $r_res['name'];
            }
            $r_stmt->close();
        }
    }

    $query = "INSERT INTO projects_progress 
              (range_id, range_office_name, project_type, project_name, activity, funding_agent, 
               target_beneficiaries, start_date, end_date, 
               dept_contribution_amount, dept_subsidy_percentage, dept_contribution_activity, 
               beneficiary_contribution_amount, beneficiary_contribution_activity, 
               summary, priority, status, progress_percent) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'In Progress', 0)";
    
    $stmt = $mysqli->prepare($query);
    if ($stmt) {
        $stmt->bind_param(
            "isssssissddsdsss", 
            $range_id, 
            $range_office_name, 
            $project_type, 
            $project_name, 
            $activity, 
            $funding_agent, 
            $target_beneficiaries, 
            $start_date, 
            $end_date, 
            $dept_contribution_amount, 
            $dept_subsidy_percentage, 
            $dept_contribution_activity, 
            $beneficiary_contribution_amount, 
            $beneficiary_contribution_activity, 
            $summary, 
            $priority
        );

        if ($stmt->execute()) {
            $_SESSION['success'] = "Project '{$project_name}' has been registered successfully with dual contribution tracking!";
        } else {
            $_SESSION['error'] = "Database error: Could not save project. " . $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = "Database preparation error: " . $mysqli->error;
    }

    header("Location: ../projects_progress.php");
    exit();
}