<?php
session_start();
require_once '../../../../config/db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'sms') {
    die("Access denied");
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $vaccine_name  = trim($mysqli->real_escape_string($_POST['vaccine_name']));
    $target_input = (!empty($_POST['target_animal']))
        ? $_POST['target_animal']
        : ($_POST['target_animal_array'] ?? '');
    if (is_array($target_input)) {
        $sanitized_arr = array_map(function ($v) use ($mysqli) {
            return $mysqli->real_escape_string(trim($v));
        }, $target_input);
        $target_animal = implode(',', $sanitized_arr);
    } else {
        $target_animal = trim($mysqli->real_escape_string($target_input));
    }
    $description   = trim($mysqli->real_escape_string($_POST['description']));

    //Process Insert Execution Queries
    if ($action === 'create') {
        $check_stmt = $mysqli->prepare("SELECT id FROM vaccine_types WHERE vaccine_name = ? AND target_animal = ?");
        $check_stmt->bind_param("ss", $vaccine_name, $target_animal);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows > 0) {
            $check_stmt->close();
            header("Location: ../vaccine_types.php?status=error&msg=" . urlencode("This duplicate combination already exists."));
            exit;
        }
        $check_stmt->close();

        $stmt = $mysqli->prepare("INSERT INTO vaccine_types (vaccine_name, target_animal, description) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $vaccine_name, $target_animal, $description);
        $stmt->execute() ? header("Location: ../vaccine_types.php?status=success") : header("Location: ../vaccine_types.php?status=error");
        $stmt->close();
    }

    //Process Entry Modification Requests
    elseif ($action === 'update') {
        $id = intval($_POST['id']);

        $stmt = $mysqli->prepare("UPDATE vaccine_types SET vaccine_name = ?, target_animal = ?, description = ? WHERE id = ?");
        $stmt->bind_param("sssi", $vaccine_name, $target_animal, $description, $id);
        $stmt->execute() ? header("Location: ../vaccine_types.php?status=success") : header("Location: ../vaccine_types.php?status=error");
        $stmt->close();
    }
}

// Drop Structural Entries Securely via GET Handshake
elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'delete') {
    $id = intval($_GET['id']);

    // Confirm no historical batches require this identifier
    $dependency_check = $mysqli->prepare("SELECT id FROM vaccine_batches WHERE vaccine_type_id = ? LIMIT 1");
    $dependency_check->bind_param("i", $id);
    $dependency_check->execute();
    $dependency_check->store_result();

    if ($dependency_check->num_rows > 0) {
        $dependency_check->close();
        header("Location: ../vaccine_types.php?status=error&msg=" . urlencode("Cannot drop vaccine type. Active batches are mapped to this type."));
        exit;
    }
    $dependency_check->close();

    $stmt = $mysqli->prepare("DELETE FROM vaccine_types WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute() ? header("Location: ../vaccine_types.php?status=success") : header("Location: ../vaccine_types.php?status=error");
    $stmt->close();
}

$mysqli->close();
