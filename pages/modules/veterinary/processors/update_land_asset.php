<?php
session_start();
require_once '../../../../config/db_connect.php';
require_once '../../../../includes/approval_helper.php';

header('Content-Type: application/json');

$allowed_roles = ['veterinary_surgeon', 'government_veterinary_surgeon', 'additional_veterinary_surgeon', 'provincial_director'];
if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'], $allowed_roles)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized entry request.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id               = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $district_id      = $_SESSION['district_id'] ?? null;
    $range_id         = $_SESSION['range_id'] ?? null;

    $property_name    = trim(filter_input(INPUT_POST, 'property_name', FILTER_SANITIZE_SPECIAL_CHARS));
    $land_extent      = trim(filter_input(INPUT_POST, 'land_extent', FILTER_SANITIZE_SPECIAL_CHARS));
    $building_area    = trim(filter_input(INPUT_POST, 'building_area', FILTER_SANITIZE_SPECIAL_CHARS));
    $land_status      = trim(filter_input(INPUT_POST, 'land_status', FILTER_SANITIZE_SPECIAL_CHARS));
    $deed_reference   = trim(filter_input(INPUT_POST, 'deed_reference', FILTER_SANITIZE_SPECIAL_CHARS));
    $deed_description = trim(filter_input(INPUT_POST, 'deed_description', FILTER_SANITIZE_SPECIAL_CHARS));

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

    $new_data = [
        'property_name'    => $property_name,
        'land_extent'      => $land_extent,
        'building_area'    => $building_area,
        'land_status'      => $land_status,
        'deed_reference'   => $deed_reference,
        'deed_description' => $deed_description
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
            'message' => 'Edit submitted successfully. Changes are pending authorization by the Provincial Director.'
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
            deed_description = ?
        WHERE id = ? AND district_id = ? AND range_id = ?
    ");

    if ($stmt) {
        $stmt->bind_param("ssssssiii", $property_name, $land_extent, $building_area, $land_status, $deed_reference, $deed_description, $id, $district_id, $range_id);
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
