<?php
session_start();
require_once '../../../../config/db_connect.php';
require_once '../../../../includes/approval_helper.php';

header('Content-Type: application/json');

$allowed_roles = ['veterinary_surgeon', 'government_veterinary_surgeon', 'additional_veterinary_surgeon', 'provincial_director', 'district_dd', 'deputy_director_district'];
if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'], $allowed_roles)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized entry request.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id               = isset($_POST['id']) ? filter_var($_POST['id'], FILTER_VALIDATE_INT) : 0;
    $property_name    = isset($_POST['property_name']) ? trim(htmlspecialchars($_POST['property_name'])) : '';
    $land_extent      = isset($_POST['land_extent']) ? trim(htmlspecialchars($_POST['land_extent'])) : '';
    $building_area    = isset($_POST['building_area']) ? trim(htmlspecialchars($_POST['building_area'])) : '';
    $land_status      = isset($_POST['land_status']) ? trim(htmlspecialchars($_POST['land_status'])) : '';
    $deed_reference   = isset($_POST['deed_reference']) ? trim(htmlspecialchars($_POST['deed_reference'])) : '';
    $deed_description = isset($_POST['deed_description']) ? trim(htmlspecialchars($_POST['deed_description'])) : '';
    $unit             = isset($_POST['unit']) ? trim(htmlspecialchars($_POST['unit'])) : '';

    if (!$id || empty($property_name)) {
        echo json_encode(['success' => false, 'message' => 'Validation failed. Required fields missing.']);
        exit();
    }

    // Fetch existing live record snapshot
    $stmt_curr = $mysqli->prepare("SELECT * FROM land_assets WHERE id = ?");
    $stmt_curr->bind_param("i", $id);
    $stmt_curr->execute();
    $old_data = $stmt_curr->get_result()->fetch_assoc();
    $stmt_curr->close();

    if (!$old_data) {
        echo json_encode(['success' => false, 'message' => 'Record not found']);
        exit();
    }

    // Resolve unit fallback if not passed
    if ($unit === '' && isset($old_data['unit'])) {
        $unit = $old_data['unit'];
    }

    // Detect Inter-Departmental Transfer & Notify Provincial Director
    check_and_notify_unit_transfer(
        $mysqli, 
        $property_name, 
        $old_data['unit'] ?? '', 
        $unit, 
        'pages/modules/pd/pending_approvals.php'
    );

    // Resolve district and range
    $district_id = !empty($old_data['district_id']) ? intval($old_data['district_id']) : intval($_SESSION['district_id'] ?? 0);
    $range_id    = !empty($old_data['range_id']) ? intval($old_data['range_id']) : ($_SESSION['range_id'] ?? null);

    // Jurisdiction check for District DD
    if (in_array($_SESSION['role'], ['district_dd', 'deputy_director_district'])) {
        $user_dist = intval($_SESSION['district_id'] ?? 0);
        if ($user_dist > 0 && $district_id > 0 && $district_id !== $user_dist) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized: Record does not belong to your assigned district.']);
            exit();
        }
    }

    $new_data = [
        'property_name'    => $property_name,
        'land_extent'      => $land_extent,
        'building_area'    => $building_area,
        'land_status'      => $land_status,
        'deed_reference'   => $deed_reference,
        'deed_description' => $deed_description,
        'unit'             => $unit
    ];

    // Staging evaluation
    $staging_res = stage_or_apply_edit(
        $mysqli, 
        'inventory', 
        'land_assets', 
        $id, 
        $property_name, 
        $old_data ?: [], 
        $new_data, 
        $district_id, 
        $range_id
    );

    if (!empty($staging_res['is_staged'])) {
        echo json_encode([
            'success' => true,
            'staged'  => true,
            'message' => 'Changes submitted successfully. Awaiting final approval from the Provincial Director.'
        ]);
        exit();
    }

    // Direct update if pre-authorized
    $stmt = $mysqli->prepare("
        UPDATE land_assets SET 
            property_name = ?,
            land_extent = ?,
            building_area = ?,
            land_status = ?,
            deed_reference = ?,
            deed_description = ?,
            unit = ?
        WHERE id = ? AND district_id = ?
    ");

    if ($stmt) {
        $stmt->bind_param("sssssssii", $property_name, $land_extent, $building_area, $land_status, $deed_reference, $deed_description, $unit, $id, $district_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Property asset record updated successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database execution failed: ' . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to prepare update query statement.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
exit();
