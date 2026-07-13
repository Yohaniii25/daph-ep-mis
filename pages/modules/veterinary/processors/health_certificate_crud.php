<?php
session_start();
if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'], ['veterinary_surgeon', 'sms'])) {
    die("Access denied: Invalid authentication clearance profile.");
}

require_once '../../../../config/db_connect.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Fetch active coordinator profile context metrics
$user_id = $_SESSION['user_id'] ?? null;
$range_id = $_SESSION['range_id'] ?? null;

// Query to get district_id from veterinary_ranges
$district_id = null;
if (!empty($range_id)) {
    $r_query = $mysqli->prepare("SELECT district_id FROM veterinary_ranges WHERE id = ?");
    if ($r_query) {
        $r_query->bind_param("i", $range_id);
        $r_query->execute();
        $r_result = $r_query->get_result();
        if ($r_row = $r_result->fetch_assoc()) {
            $district_id = $r_row['district_id'];
        }
        $r_query->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    $report_year = intval($_POST['report_year'] ?? date('Y'));
    $report_month = intval($_POST['report_month'] ?? date('m'));
    
    $health_certificate_no = trim($_POST['health_certificate_no'] ?? '');
    $applicant_name_address = trim($_POST['applicant_name_address'] ?? '');
    $farm_registration_no = trim($_POST['farm_registration_no'] ?? '');
    $date_of_issue = trim($_POST['date_of_issue'] ?? '');
    $species = trim($_POST['species'] ?? '');
    $animal_details_male = intval($_POST['animal_details_male'] ?? 0);
    $animal_details_female = intval($_POST['animal_details_female'] ?? 0);
    $vehicle_fitness_certificate_no = trim($_POST['vehicle_fitness_certificate_no'] ?? '');
    $purpose = trim($_POST['purpose'] ?? '');

    if (empty($report_year) || empty($report_month) || empty($health_certificate_no) || empty($applicant_name_address) || empty($date_of_issue)) {
        header("Location: ../health_certificate.php?status=error&msg=Missing+required+fields");
        exit();
    }

    if ($action === 'create') {
        if (empty($district_id) || empty($range_id)) {
            header("Location: ../health_certificate.php?status=error&msg=Invalid+surgeon+range+context");
            exit();
        }

        $stmt = $mysqli->prepare("INSERT INTO `health_certificate_issues` 
            (district_id, range_id, report_year, report_month, health_certificate_no, applicant_name_address, farm_registration_no, date_of_issue, species, animal_details_male, animal_details_female, vehicle_fitness_certificate_no, purpose, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            header("Location: ../health_certificate.php?status=error&msg=" . urlencode($mysqli->error));
            exit();
        }
        $stmt->bind_param("iiiisssssiisss", $district_id, $range_id, $report_year, $report_month, $health_certificate_no, $applicant_name_address, $farm_registration_no, $date_of_issue, $species, $animal_details_male, $animal_details_female, $vehicle_fitness_certificate_no, $purpose, $user_id);
        
        if ($stmt->execute()) {
            header("Location: ../health_certificate.php?status=success&msg=Record+Created+Successfully");
            exit();
        } else {
            header("Location: ../health_certificate.php?status=error&msg=" . urlencode($stmt->error));
            exit();
        }

    } elseif ($action === 'update' && $id > 0) {
        $stmt = $mysqli->prepare("UPDATE `health_certificate_issues` SET 
            report_year = ?, 
            report_month = ?, 
            health_certificate_no = ?, 
            applicant_name_address = ?, 
            farm_registration_no = ?, 
            date_of_issue = ?, 
            species = ?, 
            animal_details_male = ?, 
            animal_details_female = ?, 
            vehicle_fitness_certificate_no = ?, 
            purpose = ? 
            WHERE id = ? AND range_id = ?");
        if (!$stmt) {
            header("Location: ../health_certificate.php?status=error&msg=" . urlencode($mysqli->error));
            exit();
        }
        $stmt->bind_param("iisssssiissii", $report_year, $report_month, $health_certificate_no, $applicant_name_address, $farm_registration_no, $date_of_issue, $species, $animal_details_male, $animal_details_female, $vehicle_fitness_certificate_no, $purpose, $id, $range_id);
        
        if ($stmt->execute()) {
            header("Location: ../health_certificate.php?status=success&msg=Record+Updated+Successfully");
            exit();
        } else {
            header("Location: ../health_certificate.php?status=error&msg=" . urlencode($stmt->error));
            exit();
        }
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'delete') {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($id <= 0) {
        header("Location: ../health_certificate.php?status=error&msg=Invalid+Record+ID");
        exit();
    }

    $stmt = $mysqli->prepare("DELETE FROM `health_certificate_issues` WHERE id = ? AND range_id = ?");
    if (!$stmt) {
        header("Location: ../health_certificate.php?status=error&msg=" . urlencode($mysqli->error));
        exit();
    }
    $stmt->bind_param("ii", $id, $range_id);
    
    if ($stmt->execute()) {
        header("Location: ../health_certificate.php?status=success&msg=Record+Deleted");
        exit();
    } else {
        header("Location: ../health_certificate.php?status=error&msg=" . urlencode($stmt->error));
        exit();
    }
}

header("Location: ../health_certificate.php");
exit();
?>
