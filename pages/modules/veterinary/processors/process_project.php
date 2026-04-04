<?php
session_start();
require_once '../../../../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['logged_in'])) {
    
    $range_id        = $_SESSION['range_id'];
    $project_type    = $_POST['project_type'];
    $project_name    = $_POST['project_name'];
    $location        = $_POST['location'];
    $priority        = $_POST['priority'];
    $start_date      = $_POST['start_date'];
    $end_date        = $_POST['end_date'];
    $summary         = $_POST['summary'];
    $assigned_staff  = $_POST['officers'] ?? []; 

    $query = "INSERT INTO projects_progress (range_id, project_type, project_name, summary, location, start_date, end_date, priority, status, progress_percent) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'In Progress', 0)";
    
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("isssssss", $range_id, $project_type, $project_name, $summary, $location, $start_date, $end_date, $priority);

    if ($stmt->execute()) {
        $new_project_id = $mysqli->insert_id; // Get the ID of the project we just created

        // link Assigned Officers
        if (!empty($assigned_staff)) {
            $link_query = "INSERT INTO project_assignments (project_id, officer_id) VALUES (?, ?)";
            $link_stmt = $mysqli->prepare($link_query);

            foreach ($assigned_staff as $officer_id) {
                $link_stmt->bind_param("ii", $new_project_id, $officer_id);
                $link_stmt->execute();
            }
            $link_stmt->close();
        }

        $_SESSION['success'] = "Project '$project_name' has been registered successfully!";
    } else {
        $_SESSION['error'] = "Database error: Could not save project.";
    }

    $stmt->close();
    header("Location: ../projects_progress.php");
    exit();
}