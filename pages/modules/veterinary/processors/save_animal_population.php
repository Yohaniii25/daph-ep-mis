<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../../../config/db_connect.php';

// Allowed roles
$allowed_roles = [
    'veterinary_surgeon',
    'district_dd',
    'deputy_director_district',
    'administrator',
    'provincial_director',
    'deputy_director_hq_1',
    'deputy_director_hq_2',
    'admin'
];

$action = $_POST['action'] ?? $_GET['action'] ?? null;

// Determine range_id (from request or session)
$range_id = isset($_REQUEST['range_id']) && intval($_REQUEST['range_id']) > 0 
    ? intval($_REQUEST['range_id']) 
    : intval($_SESSION['range_id'] ?? 0);

// If action is specified, treat as JSON AJAX endpoint
if ($action !== null) {
    header('Content-Type: application/json');

    if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'] ?? '', $allowed_roles, true)) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
        exit();
    }

    if ($range_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'No Veterinary Range specified or assigned.']);
        exit();
    }

    $valid_animals = ['Cow', 'Buffalo', 'Goat', 'Sheep', 'Chicken', 'Pig', 'Others'];

    // 1. Get List Action
    if ($action === 'get_list') {
        $stmt = $mysqli->prepare("
            SELECT year, animal_type, quantity 
            FROM animal_populations 
            WHERE range_id = ? 
            ORDER BY year DESC, animal_type ASC
        ");
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Query preparation error: ' . $mysqli->error]);
            exit();
        }
        $stmt->bind_param("i", $range_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $grouped = [];
        while ($row = $result->fetch_assoc()) {
            $yr = intval($row['year']);
            if (!isset($grouped[$yr])) {
                $grouped[$yr] = [
                    'year'    => $yr,
                    'Cow'     => 0,
                    'Buffalo' => 0,
                    'Goat'    => 0,
                    'Sheep'   => 0,
                    'Chicken' => 0,
                    'Pig'     => 0,
                    'Others'  => 0,
                    'total'   => 0
                ];
            }
            $animal = $row['animal_type'];
            $count = intval($row['quantity']);
            if (isset($grouped[$yr][$animal])) {
                $grouped[$yr][$animal] = $count;
            }
            $grouped[$yr]['total'] += $count;
        }
        $stmt->close();

        // Sort descending by year
        krsort($grouped);

        echo json_encode(['success' => true, 'data' => array_values($grouped)]);
        exit();
    }

    // 2. Get Data for a Specific Year
    if ($action === 'get_year_data') {
        $year = intval($_GET['year'] ?? 0);
        if ($year <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid year specified.']);
            exit();
        }

        $stmt = $mysqli->prepare("
            SELECT animal_type, quantity 
            FROM animal_populations 
            WHERE range_id = ? AND year = ?
        ");
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Query error: ' . $mysqli->error]);
            exit();
        }
        $stmt->bind_param("ii", $range_id, $year);
        $stmt->execute();
        $result = $stmt->get_result();

        $counts = [
            'Cow'     => 0,
            'Buffalo' => 0,
            'Goat'    => 0,
            'Sheep'   => 0,
            'Chicken' => 0,
            'Pig'     => 0,
            'Others'  => 0
        ];
        while ($row = $result->fetch_assoc()) {
            $counts[$row['animal_type']] = intval($row['quantity']);
        }
        $stmt->close();

        echo json_encode(['success' => true, 'data' => $counts, 'year' => $year]);
        exit();
    }

    // 3. Delete Action
    if ($action === 'delete') {
        $year = intval($_POST['year'] ?? 0);
        $animal_type = trim($_POST['animal_type'] ?? '');

        if ($year <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid year for deletion.']);
            exit();
        }

        if (!empty($animal_type) && in_array($animal_type, $valid_animals, true)) {
            $stmt = $mysqli->prepare("DELETE FROM animal_populations WHERE range_id = ? AND year = ? AND animal_type = ?");
            if (!$stmt) {
                echo json_encode(['success' => false, 'message' => 'Database error: ' . $mysqli->error]);
                exit();
            }
            $stmt->bind_param("iis", $range_id, $year, $animal_type);
            $msg = "Animal population for {$animal_type} ({$year}) deleted successfully.";
        } else {
            $stmt = $mysqli->prepare("DELETE FROM animal_populations WHERE range_id = ? AND year = ?");
            if (!$stmt) {
                echo json_encode(['success' => false, 'message' => 'Database error: ' . $mysqli->error]);
                exit();
            }
            $stmt->bind_param("ii", $range_id, $year);
            $msg = "All livestock population records for year {$year} deleted successfully.";
        }

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => $msg, 'year' => $year]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Delete failed: ' . $stmt->error]);
        }
        $stmt->close();
        exit();
    }

    // 4. Save Action (Batch species counts or single species)
    if ($action === 'save') {
        $year = intval($_POST['year'] ?? 0);
        if ($year < 2000 || $year > 2100) {
            echo json_encode(['success' => false, 'message' => 'Please provide a valid year between 2000 and 2100.']);
            exit();
        }

        $stmt = $mysqli->prepare("
            INSERT INTO animal_populations (range_id, year, animal_type, quantity) 
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE quantity = VALUES(quantity)
        ");
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Query prep failed: ' . $mysqli->error]);
            exit();
        }

        $mysqli->begin_transaction();
        try {
            if (isset($_POST['counts']) && is_array($_POST['counts'])) {
                foreach ($valid_animals as $animal) {
                    $qty = max(0, intval($_POST['counts'][$animal] ?? 0));
                    $stmt->bind_param("iisi", $range_id, $year, $animal, $qty);
                    $stmt->execute();
                }
            } elseif (isset($_POST['animal_type'])) {
                $animal = trim($_POST['animal_type']);
                if (!in_array($animal, $valid_animals, true)) {
                    throw new Exception("Invalid animal category: {$animal}");
                }
                $qty = max(0, intval($_POST['quantity'] ?? 0));
                $stmt->bind_param("iisi", $range_id, $year, $animal, $qty);
                $stmt->execute();
            } else {
                throw new Exception("No population counts provided.");
            }

            $mysqli->commit();
            $stmt->close();
            echo json_encode([
                'success' => true,
                'message' => "Animal population data for year {$year} saved successfully.",
                'year'    => $year
            ]);
        } catch (Exception $e) {
            $mysqli->rollback();
            $stmt->close();
            echo json_encode(['success' => false, 'message' => 'Save error: ' . $e->getMessage()]);
        }
        exit();
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action requested.']);
    exit();
}

// ---------------------------------------------------------
// Legacy synchronous POST handler for vaccination_targets.php
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !in_array($_SESSION['role'] ?? '', $allowed_roles, true)) {
    header("Location: ../vaccination_targets.php?status=error&msg=Unauthorized");
    exit();
}

$year        = intval($_POST['year'] ?? 0);
$animal_type = mysqli_real_escape_string($mysqli, $_POST['animal_type'] ?? '');
$quantity    = intval($_POST['quantity'] ?? 0);

if (empty($animal_type)) {
    $_SESSION['msg'] = "Please select a valid animal type.";
    $_SESSION['msg_type'] = "danger";
    header("Location: ../vaccination_targets.php?year=" . $year);
    exit();
}

$stmt = $mysqli->prepare("
    INSERT INTO animal_populations (range_id, year, animal_type, quantity) 
    VALUES (?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE quantity = VALUES(quantity)
");

if ($stmt) {
    $stmt->bind_param("iisi", $range_id, $year, $animal_type, $quantity);
    if ($stmt->execute()) {
        $_SESSION['msg'] = "Population count for " . htmlspecialchars($animal_type) . " updated successfully to " . number_format($quantity) . ".";
        $_SESSION['msg_type'] = "success";
    } else {
        $_SESSION['msg'] = "Database error: " . htmlspecialchars($mysqli->error);
        $_SESSION['msg_type'] = "danger";
    }
    $stmt->close();
} else {
    $_SESSION['msg'] = "Statement prep error: " . htmlspecialchars($mysqli->error);
    $_SESSION['msg_type'] = "danger";
}

header("Location: ../vaccination_targets.php?year=" . $year);
$mysqli->close();
exit();
