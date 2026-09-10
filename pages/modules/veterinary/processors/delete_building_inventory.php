<?php
/**
 * pages/modules/veterinary/processors/delete_building_inventory.php
 * Direct deletion disabled in compliance with statutory audit standards.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

echo json_encode([
    'success' => false,
    'message' => 'Direct deletion of inventory items is strictly prohibited. To remove items from circulation, execute the formal "Board of Survey" removal procedure with authorized documentation.'
]);
exit();
