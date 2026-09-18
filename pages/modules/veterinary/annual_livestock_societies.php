<?php
session_start();
require_once __DIR__ . '/../../../config/db_connect.php';

/** @var mysqli $mysqli */
global $mysqli;

$allowed_roles = [
    'veterinary_surgeon',

];

if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'] ?? '', $allowed_roles, true)) {
    header("Location: ../../../index.php");
    exit();
}

$user_id = $_SESSION['user_id'] ?? null;
$requested_range_id = isset($_GET['range_id']) && is_numeric($_GET['range_id']) ? (int)$_GET['range_id'] : null;
$range_id = $requested_range_id ?: ($_SESSION['range_id'] ?? null);

$range_name = 'Your Range';
$district_name = 'Your District';
$district_id = null;

if (!empty($range_id)) {
    $details_sql = "
        SELECT vr.name AS range_name, d.name AS district_name, d.id AS district_id
        FROM veterinary_ranges vr
        LEFT JOIN districts d ON vr.district_id = d.id
        WHERE vr.id = ?
    ";
    $details_query = $mysqli->prepare($details_sql);
    if ($details_query) {
        $details_query->bind_param("i", $range_id);
        $details_query->execute();
        $details_result = $details_query->get_result();
        if ($data = $details_result->fetch_assoc()) {
            $range_name = $data['range_name'];
            $district_name = $data['district_name'];
            $district_id = $data['district_id'];
        }
        $details_query->close();
    }
}

$range_query_param = $requested_range_id ? '&range_id=' . $requested_range_id : '';
$range_query_string = $requested_range_id ? '?range_id=' . $requested_range_id : '';

// Helper function to handle PDF upload
function handlePdfUpload($file_input_name) {
    if (isset($_FILES[$file_input_name]) && $_FILES[$file_input_name]['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES[$file_input_name]['tmp_name'];
        $file_name = $_FILES[$file_input_name]['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        if ($file_ext === 'pdf') {
            $target_dir = __DIR__ . '/../../../assets/uploads/code_of_conduct/';
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            $new_filename = 'coc_' . time() . '_' . bin2hex(random_bytes(4)) . '.pdf';
            if (move_uploaded_file($file_tmp, $target_dir . $new_filename)) {
                return 'assets/uploads/code_of_conduct/' . $new_filename;
            }
        }
    }
    return null;
}

// -------------------------------------------------------------
// AJAX ENDPOINTS FOR MEETINGS & COMMITTEE MANAGEMENT
// -------------------------------------------------------------
$ajax_action = $_POST['ajax_action'] ?? $_GET['ajax_action'] ?? null;

if ($ajax_action) {
    header('Content-Type: application/json; charset=utf-8');

    if ($ajax_action === 'get_society_data') {
        $society_id = (int)($_GET['society_id'] ?? $_POST['society_id'] ?? 0);
        if ($society_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid society ID.']);
            exit();
        }

        // Fetch society details
        $s_stmt = $mysqli->prepare("SELECT id, vs_range, gn_division, name_address, total_members, reg_no FROM livestock_societies WHERE id = ?");
        $society = null;
        if ($s_stmt) {
            $s_stmt->bind_param("i", $society_id);
            $s_stmt->execute();
            $society = $s_stmt->get_result()->fetch_assoc();
            $s_stmt->close();
        }

        if (!$society) {
            echo json_encode(['success' => false, 'message' => 'Society not found.']);
            exit();
        }

        // Fetch meetings
        $meetings = [];
        $m_stmt = $mysqli->prepare("SELECT * FROM livestock_society_meetings WHERE society_id = ? ORDER BY meeting_date DESC, id DESC");
        if ($m_stmt) {
            $m_stmt->bind_param("i", $society_id);
            $m_stmt->execute();
            $res = $m_stmt->get_result();
            while ($m = $res->fetch_assoc()) {
                $meetings[] = $m;
            }
            $m_stmt->close();
        }

        // Fetch committee members sorted by order_rank, then id
        $committee = [];
        $c_stmt = $mysqli->prepare("SELECT * FROM livestock_society_committee WHERE society_id = ? ORDER BY order_rank ASC, id ASC");
        if ($c_stmt) {
            $c_stmt->bind_param("i", $society_id);
            $c_stmt->execute();
            $res = $c_stmt->get_result();
            while ($c = $res->fetch_assoc()) {
                $committee[] = $c;
            }
            $c_stmt->close();
        }

        echo json_encode([
            'success' => true,
            'society' => $society,
            'meetings' => $meetings,
            'committee' => $committee
        ]);
        exit();
    }

    if ($ajax_action === 'log_meeting') {
        $society_id = (int)($_POST['society_id'] ?? 0);
        $meeting_date = trim($_POST['meeting_date'] ?? '');
        $meeting_type = trim($_POST['meeting_type'] ?? 'General Meeting');
        $decisions_made = trim($_POST['decisions_made'] ?? '');
        $attendees_count = !empty($_POST['attendees_count']) ? (int)$_POST['attendees_count'] : null;
        $notes = trim($_POST['notes'] ?? '');

        if ($society_id <= 0 || empty($meeting_date) || empty($decisions_made)) {
            echo json_encode(['success' => false, 'message' => 'Please provide the meeting date and main decisions made.']);
            exit();
        }

        $stmt = $mysqli->prepare("INSERT INTO livestock_society_meetings (society_id, meeting_date, meeting_type, decisions_made, attendees_count, notes) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("isssis", $society_id, $meeting_date, $meeting_type, $decisions_made, $attendees_count, $notes);
            if ($stmt->execute()) {
                $new_id = $stmt->insert_id;
                $stmt->close();

                // Get new count
                $cnt_stmt = $mysqli->prepare("SELECT COUNT(*) as cnt FROM livestock_society_meetings WHERE society_id = ?");
                $cnt_stmt->bind_param("i", $society_id);
                $cnt_stmt->execute();
                $count = $cnt_stmt->get_result()->fetch_assoc()['cnt'];
                $cnt_stmt->close();

                echo json_encode([
                    'success' => true,
                    'message' => 'Meeting logged successfully.',
                    'meeting_id' => $new_id,
                    'total_meetings' => (int)$count
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
                $stmt->close();
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Database prepare failed: ' . $mysqli->error]);
        }
        exit();
    }

    if ($ajax_action === 'delete_meeting') {
        $meeting_id = (int)($_POST['meeting_id'] ?? 0);
        $society_id = (int)($_POST['society_id'] ?? 0);
        if ($meeting_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid meeting ID.']);
            exit();
        }

        $stmt = $mysqli->prepare("DELETE FROM livestock_society_meetings WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $meeting_id);
            $stmt->execute();
            $stmt->close();

            // Get updated count
            $count = 0;
            if ($society_id > 0) {
                $cnt_stmt = $mysqli->prepare("SELECT COUNT(*) as cnt FROM livestock_society_meetings WHERE society_id = ?");
                $cnt_stmt->bind_param("i", $society_id);
                $cnt_stmt->execute();
                $count = $cnt_stmt->get_result()->fetch_assoc()['cnt'];
                $cnt_stmt->close();
            }

            echo json_encode([
                'success' => true,
                'message' => 'Meeting deleted successfully.',
                'total_meetings' => (int)$count
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete meeting.']);
        }
        exit();
    }

    if ($ajax_action === 'save_committee_member') {
        $member_id = (int)($_POST['member_id'] ?? 0);
        $society_id = (int)($_POST['society_id'] ?? 0);
        $position = trim($_POST['position'] ?? 'Committee Member');
        $member_name = trim($_POST['member_name'] ?? '');
        $nic = trim($_POST['nic'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $contact_no = trim($_POST['contact_no'] ?? '');

        if ($society_id <= 0 || empty($member_name) || empty($position)) {
            echo json_encode(['success' => false, 'message' => 'Society, member name, and position are required.']);
            exit();
        }

        // Determine default rank based on position
        $rank_map = [
            'President' => 1,
            'Chairperson' => 1,
            'Vice President' => 2,
            'Vice Chairperson' => 2,
            'Secretary' => 3,
            'Assistant Secretary' => 4,
            'Treasurer' => 5,
            'Assistant Treasurer' => 5,
            'Committee Member' => 6,
            'Auditor' => 7,
            'Advisor' => 8,
            'Other' => 9
        ];
        $order_rank = $rank_map[$position] ?? 6;

        if ($member_id > 0) {
            $stmt = $mysqli->prepare("UPDATE livestock_society_committee SET position = ?, member_name = ?, nic = ?, address = ?, contact_no = ?, order_rank = ? WHERE id = ? AND society_id = ?");
            if ($stmt) {
                $stmt->bind_param("sssssiii", $position, $member_name, $nic, $address, $contact_no, $order_rank, $member_id, $society_id);
                $stmt->execute();
                $stmt->close();
            }
        } else {
            $stmt = $mysqli->prepare("INSERT INTO livestock_society_committee (society_id, position, member_name, nic, address, contact_no, order_rank) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("isssssi", $society_id, $position, $member_name, $nic, $address, $contact_no, $order_rank);
                $stmt->execute();
                $member_id = $stmt->insert_id;
                $stmt->close();
            }
        }

        // Get updated count
        $cnt_stmt = $mysqli->prepare("SELECT COUNT(*) as cnt FROM livestock_society_committee WHERE society_id = ?");
        $cnt_stmt->bind_param("i", $society_id);
        $cnt_stmt->execute();
        $count = $cnt_stmt->get_result()->fetch_assoc()['cnt'];
        $cnt_stmt->close();

        echo json_encode([
            'success' => true,
            'message' => 'Committee member saved successfully.',
            'member_id' => $member_id,
            'total_committee' => (int)$count
        ]);
        exit();
    }

    if ($ajax_action === 'delete_committee_member') {
        $member_id = (int)($_POST['member_id'] ?? 0);
        $society_id = (int)($_POST['society_id'] ?? 0);
        if ($member_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid member ID.']);
            exit();
        }

        $stmt = $mysqli->prepare("DELETE FROM livestock_society_committee WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $member_id);
            $stmt->execute();
            $stmt->close();

            // Get updated count
            $count = 0;
            if ($society_id > 0) {
                $cnt_stmt = $mysqli->prepare("SELECT COUNT(*) as cnt FROM livestock_society_committee WHERE society_id = ?");
                $cnt_stmt->bind_param("i", $society_id);
                $cnt_stmt->execute();
                $count = $cnt_stmt->get_result()->fetch_assoc()['cnt'];
                $cnt_stmt->close();
            }

            echo json_encode([
                'success' => true,
                'message' => 'Committee member removed successfully.',
                'total_committee' => (int)$count
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to remove committee member.']);
        }
        exit();
    }

    if ($ajax_action === 'update_total_members') {
        $society_id = (int)($_POST['society_id'] ?? 0);
        $total_members = (int)($_POST['total_members'] ?? 0);

        if ($society_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid society ID.']);
            exit();
        }

        $stmt = $mysqli->prepare("UPDATE livestock_societies SET total_members = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("ii", $total_members, $society_id);
            $stmt->execute();
            $stmt->close();

            echo json_encode([
                'success' => true,
                'message' => 'Total membership count updated.',
                'total_members' => $total_members
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update total membership.']);
        }
        exit();
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action.']);
    exit();
}

// Inline CRUD actions:
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $vs_range = trim($_POST['vs_range']);
            $gn_division = trim($_POST['gn_division']);
            $name_address = trim($_POST['name_address']);
            $overall_objective = trim($_POST['overall_objective']);
            $total_members = intval($_POST['total_members']);
            $reg_no = trim($_POST['reg_no']);
            $reg_department = trim($_POST['reg_department']);
            $major_activities = trim($_POST['major_activities']);
            $financial_records_availability = trim($_POST['financial_records_availability']);
            $regulated_by = trim($_POST['regulated_by']);
            $tp_no = trim($_POST['tp_no']);
            $code_of_conduct_pdf = handlePdfUpload('code_of_conduct_pdf');

            $insert_query = "
                INSERT INTO livestock_societies 
                (vs_range, gn_division, name_address, overall_objective, total_members, 
                 reg_no, reg_department, major_activities, financial_records_availability, 
                 regulated_by, tp_no, code_of_conduct_pdf)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";
            $stmt = $mysqli->prepare($insert_query);
            if ($stmt) {
                $stmt->bind_param(
                    "ssssisssssss",
                    $vs_range,
                    $gn_division,
                    $name_address,
                    $overall_objective,
                    $total_members,
                    $reg_no,
                    $reg_department,
                    $major_activities,
                    $financial_records_availability,
                    $regulated_by,
                    $tp_no,
                    $code_of_conduct_pdf
                );
                if ($stmt->execute()) {
                    $new_society_id = $stmt->insert_id;
                    $stmt->close();

                    // Bind and save committee members if provided in the Add form
                    if (!empty($_POST['committee_positions']) && is_array($_POST['committee_positions'])) {
                        $c_stmt = $mysqli->prepare("INSERT INTO livestock_society_committee (society_id, position, member_name, nic, address, contact_no, order_rank) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        if ($c_stmt) {
                            $rank_map = [
                                'President' => 1,
                                'Vice President' => 2,
                                'Secretary' => 3,
                                'Assistant Secretary' => 4,
                                'Treasurer' => 5,
                                'Committee Member' => 6
                            ];
                            foreach ($_POST['committee_positions'] as $c_idx => $c_pos) {
                                $m_pos = trim($c_pos);
                                $m_name = trim($_POST['committee_names'][$c_idx] ?? '');
                                $m_nic = trim($_POST['committee_nics'][$c_idx] ?? '');
                                $m_addr = trim($_POST['committee_addresses'][$c_idx] ?? '');
                                $m_tel = trim($_POST['committee_contacts'][$c_idx] ?? '');
                                if (!empty($m_name) && !empty($m_pos)) {
                                    $m_rank = $rank_map[$m_pos] ?? 6;
                                    $c_stmt->bind_param("isssssi", $new_society_id, $m_pos, $m_name, $m_nic, $m_addr, $m_tel, $m_rank);
                                    $c_stmt->execute();
                                }
                            }
                            $c_stmt->close();
                        }
                    }

                    header("Location: annual_livestock_societies.php?status=success&msg=" . urlencode("Livestock society and committee members registered successfully.") . $range_query_param);
                    exit();
                } else {
                    $stmt->close();
                    header("Location: annual_livestock_societies.php?status=error&msg=" . urlencode("Failed to write to database: " . $mysqli->error) . $range_query_param);
                    exit();
                }
            } else {
                header("Location: annual_livestock_societies.php?status=error&msg=" . urlencode("Query preparation failed: " . $mysqli->error) . $range_query_param);
                exit();
            }
        } elseif ($_POST['action'] === 'edit') {
            $id = intval($_POST['id']);
            $vs_range = trim($_POST['vs_range']);
            $gn_division = trim($_POST['gn_division']);
            $name_address = trim($_POST['name_address']);
            $overall_objective = trim($_POST['overall_objective']);
            $total_members = intval($_POST['total_members']);
            $reg_no = trim($_POST['reg_no']);
            $reg_department = trim($_POST['reg_department']);
            $major_activities = trim($_POST['major_activities']);
            $financial_records_availability = trim($_POST['financial_records_availability']);
            $regulated_by = trim($_POST['regulated_by']);
            $tp_no = trim($_POST['tp_no']);

            $new_pdf = handlePdfUpload('code_of_conduct_pdf');
            if ($new_pdf !== null) {
                // Remove old PDF if exists
                $old_q = $mysqli->prepare("SELECT code_of_conduct_pdf FROM livestock_societies WHERE id = ?");
                if ($old_q) {
                    $old_q->bind_param("i", $id);
                    $old_q->execute();
                    $res = $old_q->get_result()->fetch_assoc();
                    if (!empty($res['code_of_conduct_pdf'])) {
                        $old_file = __DIR__ . '/../../../' . $res['code_of_conduct_pdf'];
                        if (file_exists($old_file)) {
                            @unlink($old_file);
                        }
                    }
                    $old_q->close();
                }

                $update_query = "
                    UPDATE livestock_societies 
                    SET vs_range = ?, gn_division = ?, name_address = ?, overall_objective = ?, total_members = ?, 
                        reg_no = ?, reg_department = ?, major_activities = ?, financial_records_availability = ?, 
                        regulated_by = ?, tp_no = ?, code_of_conduct_pdf = ?
                    WHERE id = ? AND vs_range = ?
                ";
                $stmt = $mysqli->prepare($update_query);
                if ($stmt) {
                    $stmt->bind_param(
                        "ssssisssssssis",
                        $vs_range,
                        $gn_division,
                        $name_address,
                        $overall_objective,
                        $total_members,
                        $reg_no,
                        $reg_department,
                        $major_activities,
                        $financial_records_availability,
                        $regulated_by,
                        $tp_no,
                        $new_pdf,
                        $id,
                        $range_name
                    );
                    $stmt->execute();
                    $stmt->close();
                }
            } else {
                $update_query = "
                    UPDATE livestock_societies 
                    SET vs_range = ?, gn_division = ?, name_address = ?, overall_objective = ?, total_members = ?, 
                        reg_no = ?, reg_department = ?, major_activities = ?, financial_records_availability = ?, 
                        regulated_by = ?, tp_no = ?
                    WHERE id = ? AND vs_range = ?
                ";
                $stmt = $mysqli->prepare($update_query);
                if ($stmt) {
                    $stmt->bind_param(
                        "ssssissssssis",
                        $vs_range,
                        $gn_division,
                        $name_address,
                        $overall_objective,
                        $total_members,
                        $reg_no,
                        $reg_department,
                        $major_activities,
                        $financial_records_availability,
                        $regulated_by,
                        $tp_no,
                        $id,
                        $range_name
                    );
                    $stmt->execute();
                    $stmt->close();
                }
            }

            header("Location: annual_livestock_societies.php?status=success&msg=" . urlencode("Livestock society updated successfully.") . $range_query_param);
            exit();
        }
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    // Unlink file if exists
    $old_q = $mysqli->prepare("SELECT code_of_conduct_pdf FROM livestock_societies WHERE id = ?");
    if ($old_q) {
        $old_q->bind_param("i", $id);
        $old_q->execute();
        $res = $old_q->get_result()->fetch_assoc();
        if (!empty($res['code_of_conduct_pdf'])) {
            $old_file = __DIR__ . '/../../../' . $res['code_of_conduct_pdf'];
            if (file_exists($old_file)) {
                @unlink($old_file);
            }
        }
        $old_q->close();
    }

    $stmt = $mysqli->prepare("DELETE FROM livestock_societies WHERE id = ? AND vs_range = ?");
    if ($stmt) {
        $stmt->bind_param("is", $id, $range_name);
        if ($stmt->execute()) {
            header("Location: annual_livestock_societies.php?status=success&msg=" . urlencode("Record deleted successfully.") . $range_query_param);
        } else {
            header("Location: annual_livestock_societies.php?status=error&msg=" . urlencode("Failed to delete record.") . $range_query_param);
        }
        $stmt->close();
    }
    exit();
}

// Fetch records matching VS range
$records = [];
$meeting_counts = [];
$committee_counts = [];

if (!empty($range_name)) {
    $stmt = $mysqli->prepare("SELECT * FROM livestock_societies WHERE vs_range = ? ORDER BY id DESC");
    if ($stmt) {
        $stmt->bind_param("s", $range_name);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $records[] = $row;
        }
        $stmt->close();
    }
}

// Pre-load counts for meetings and committee members
if (!empty($records)) {
    $society_ids = array_map(function($r) { return (int)$r['id']; }, $records);
    $in_ids = implode(',', $society_ids);

    $m_res = $mysqli->query("SELECT society_id, COUNT(*) as cnt FROM livestock_society_meetings WHERE society_id IN ($in_ids) GROUP BY society_id");
    if ($m_res) {
        while ($mr = $m_res->fetch_assoc()) {
            $meeting_counts[$mr['society_id']] = (int)$mr['cnt'];
        }
    }

    $c_res = $mysqli->query("SELECT society_id, COUNT(*) as cnt FROM livestock_society_committee WHERE society_id IN ($in_ids) GROUP BY society_id");
    if ($c_res) {
        while ($cr = $c_res->fetch_assoc()) {
            $committee_counts[$cr['society_id']] = (int)$cr['cnt'];
        }
    }
}

// Summary stats
$summary = [
    'soc_count' => count($records),
    'total_members' => 0,
    'total_meetings' => array_sum($meeting_counts),
    'total_committee' => array_sum($committee_counts)
];
foreach ($records as $r) {
    $summary['total_members'] += intval($r['total_members']);
}

require_once '../../../includes/header.php';
?>

<link rel="stylesheet" href="../../../assets/css/bootstrap-icons.min.css">
<link rel="stylesheet" href="../../../assets/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="../../../assets/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="../../../assets/css/sweetalert2.min.css">
<link rel="stylesheet" href="../../../assets/css/veterinary.css">

        <div class="mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h2 class="h4 fw-bold mb-1" style="color: #370709;">Details of Livestock Societies</h2>
                <p class="text-muted small mb-0">Record and monitor livestock cooperative societies for <strong class="text-dark"><?= htmlspecialchars($range_name) ?></strong> (<?= htmlspecialchars($district_name) ?> District)</p>
            </div>
            <div>
                <a href="range_statistics.php<?= $requested_range_id ? '?range_id=' . $requested_range_id : '' ?>" class="btn btn-light text-dark border fw-bold btn-sm shadow-sm">
                    <i class="bi bi-arrow-left-circle me-1"></i> Range Statistics
                </a>
            </div>
        </div>

        <!-- STATS CARD GROUP (4 CARDS) -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3">
                <div class="card shadow-sm border-0 border-start border-success border-4 text-center h-100">
                    <div class="card-body py-3">
                        <span class="text-muted small text-uppercase fw-bold"><i class="bi bi-building me-1"></i>Active Societies</span>
                        <h4 class="mb-0 fw-bold text-success mt-1" id="stat_active_societies"><?= number_format($summary['soc_count']) ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card shadow-sm border-0 border-start border-info border-4 text-center h-100">
                    <div class="card-body py-3">
                        <span class="text-muted small text-uppercase fw-bold"><i class="bi bi-people-fill me-1"></i>Total Members</span>
                        <h4 class="mb-0 fw-bold text-info mt-1" id="stat_total_members"><?= number_format($summary['total_members']) ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card shadow-sm border-0 border-start border-primary border-4 text-center h-100">
                    <div class="card-body py-3">
                        <span class="text-muted small text-uppercase fw-bold"><i class="bi bi-calendar2-check-fill me-1"></i>Logged Meetings</span>
                        <h4 class="mb-0 fw-bold text-primary mt-1" id="stat_logged_meetings"><?= number_format($summary['total_meetings']) ?></h4>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card shadow-sm border-0 border-start border-warning border-4 text-center h-100">
                    <div class="card-body py-3">
                        <span class="text-muted small text-uppercase fw-bold"><i class="bi bi-diagram-3-fill me-1"></i>Committee Appointees</span>
                        <h4 class="mb-0 fw-bold text-warning mt-1" id="stat_committee_members"><?= number_format($summary['total_committee']) ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3 border-0">
                        <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-grid-3x3-gap-fill me-2"></i>Quick Actions</h6>
                    </div>
                    <div class="card-body pt-0">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <button class="btn btn-primary w-100 py-3 text-light border-0 shadow-sm d-flex flex-column align-items-center justify-content-center" style="background-color: #820100; min-height: 105px;" data-bs-toggle="modal" data-bs-target="#addSocModal">
                                    <i class="bi bi-plus-circle fs-3 mb-1"></i>
                                    <span class="small fw-bold text-uppercase">Add Livestock Society</span>
                                </button>
                            </div>
                            <div class="col-md-3">
                                <a href="range_statistics.php<?= $requested_range_id ? '?range_id=' . $requested_range_id : '' ?>" class="btn btn-outline-secondary w-100 py-3 d-flex flex-column align-items-center justify-content-center" style="min-height: 105px;">
                                    <i class="bi bi-arrow-left-circle fs-3 mb-1"></i>
                                    <span class="small fw-bold text-uppercase">Back to Statistics</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- RECORDS LIST TABLE -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
                <h5 class="card-title mb-0 fw-bold text-dark"><i class="bi bi-table me-2"></i>Societies Log Directory</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="socTable" style="min-width: 1600px;">
                        <thead class="table-light text-secondary small uppercase">
                            <tr>
                                <th>S.no</th>
                                <th>VS Range</th>
                                <th>G N Division</th>
                                <th>Name & Address</th>
                                <th>Overall Objective</th>
                                <th class="text-end">Total Members</th>
                                <th>Reg. No</th>
                                <th>Reg. Department</th>
                                <th>Major Activities</th>
                                <th>Availability of Financial Records</th>
                                <th>Regulated By</th>
                                <th>T.P.No</th>
                                <th class="text-center">Code of Conduct</th>
                                <th class="text-center" style="width: 20%">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            <?php foreach ($records as $row): ?>
                                <tr
                                    data-id="<?= $row['id'] ?>"
                                    data-vs_range="<?= htmlspecialchars($row['vs_range']) ?>"
                                    data-gn_division="<?= htmlspecialchars($row['gn_division']) ?>"
                                    data-name_address="<?= htmlspecialchars($row['name_address']) ?>"
                                    data-overall_objective="<?= htmlspecialchars($row['overall_objective']) ?>"
                                    data-total_members="<?= htmlspecialchars($row['total_members']) ?>"
                                    data-reg_no="<?= htmlspecialchars($row['reg_no']) ?>"
                                    data-reg_department="<?= htmlspecialchars($row['reg_department']) ?>"
                                    data-major_activities="<?= htmlspecialchars($row['major_activities']) ?>"
                                    data-financial_records_availability="<?= htmlspecialchars($row['financial_records_availability']) ?>"
                                    data-regulated_by="<?= htmlspecialchars($row['regulated_by']) ?>"
                                    data-tp_no="<?= htmlspecialchars($row['tp_no']) ?>"
                                    data-code_of_conduct_pdf="<?= htmlspecialchars($row['code_of_conduct_pdf'] ?? '') ?>">
                                    <td class="fw-bold text-center"><?= htmlspecialchars($row['id']) ?></td>
                                    <td><?= htmlspecialchars($row['vs_range']) ?></td>
                                    <td><?= htmlspecialchars($row['gn_division']) ?></td>
                                    <td><?= nl2br(htmlspecialchars($row['name_address'])) ?></td>
                                    <td><?= nl2br(htmlspecialchars($row['overall_objective'])) ?></td>
                                    <td class="text-end font-monospace soc-total-members-<?= $row['id'] ?>"><?= number_format($row['total_members']) ?></td>
                                    <td><?= htmlspecialchars($row['reg_no']) ?></td>
                                    <td><?= htmlspecialchars($row['reg_department']) ?></td>
                                    <td><?= nl2br(htmlspecialchars($row['major_activities'])) ?></td>
                                    <td class="text-center">
                                        <span class="badge <?= $row['financial_records_availability'] === 'Yes' ? 'bg-success' : ($row['financial_records_availability'] === 'No' ? 'bg-danger' : 'bg-secondary') ?>">
                                             <?= htmlspecialchars($row['financial_records_availability']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($row['regulated_by']) ?></td>
                                    <td><?= htmlspecialchars($row['tp_no']) ?></td>
                                    <td class="text-center">
                                        <?php if (!empty($row['code_of_conduct_pdf'])): ?>
                                            <a href="../../../<?= htmlspecialchars($row['code_of_conduct_pdf']) ?>" target="_blank" class="btn btn-sm btn-outline-danger py-0 px-2 fw-bold" title="Open Code of Conduct PDF">
                                                <i class="bi bi-file-earmark-pdf-fill me-1"></i>PDF
                                            </a>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border">None</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center text-nowrap">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button class="btn btn-outline-info btn-view" title="View Details"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-outline-primary btn-edit" title="Edit Society"><i class="bi bi-pencil-square"></i></button>
                                            <button class="btn btn-success btn-meetings position-relative" data-id="<?= $row['id'] ?>" data-name="<?= htmlspecialchars($row['name_address']) ?>" title="Meetings & Resolutions Tracker">
                                                <i class="bi bi-calendar-check me-1"></i>Meetings
                                                <span class="badge bg-light text-success fw-bold ms-1 meeting-badge-<?= $row['id'] ?>"><?= $meeting_counts[$row['id']] ?? 0 ?></span>
                                            </button>
                                            <button class="btn btn-warning text-dark btn-committee position-relative" data-id="<?= $row['id'] ?>" data-name="<?= htmlspecialchars($row['name_address']) ?>" data-total="<?= (int)$row['total_members'] ?>" title="Committee & Org Chart">
                                                <i class="bi bi-diagram-3-fill me-1"></i>Committee
                                                <span class="badge bg-dark text-white ms-1 committee-badge-<?= $row['id'] ?>"><?= $committee_counts[$row['id']] ?? 0 ?></span>
                                            </button>
                                            <a href="annual_livestock_societies.php?action=delete&id=<?= $row['id'] ?><?= $range_query_param ?>" class="btn btn-outline-danger btn-delete" title="Delete Society"><i class="bi bi-trash"></i></a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </main>
</div>

<!-- Modal: Add Record -->
<div class="modal fade" id="addSocModal" tabindex="-1" aria-labelledby="addSocModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add">
            <div class="modal-content border-0 shadow">
                <div class="modal-header" style="background-color: #370709; color: white;">
                    <h5 class="modal-title fw-bold" id="addSocModalLabel"><i class="bi bi-plus-circle me-2"></i>Add Livestock Society</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="bi bi-building me-2 text-primary"></i>1. Society Details</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">VS Range <span class="text-danger">*</span></label>
                            <input type="text" name="vs_range" class="form-control form-control-sm" value="<?= htmlspecialchars($range_name) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">G N Division</label>
                            <input type="text" name="gn_division" class="form-control form-control-sm" placeholder="e.g. GN Division name">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Name & Address <span class="text-danger">*</span></label>
                            <textarea name="name_address" class="form-control form-control-sm" rows="2" placeholder="Society Name and Registered Address" required></textarea>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Overall Objective</label>
                            <textarea name="overall_objective" class="form-control form-control-sm" rows="2" placeholder="e.g. Elevating dairy production standards"></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Total Members Registered</label>
                            <input type="number" name="total_members" class="form-control form-control-sm" value="0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Reg. No</label>
                            <input type="text" name="reg_no" class="form-control form-control-sm" placeholder="Registration number">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Reg. Department</label>
                            <input type="text" name="reg_department" class="form-control form-control-sm" placeholder="e.g. Dept of Cooperatives">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Major Activities</label>
                            <textarea name="major_activities" class="form-control form-control-sm" rows="2" placeholder="Collection of Deposits, Granting Loans, member welfare, etc."></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Financial Records Availability</label>
                            <select name="financial_records_availability" class="form-select form-select-sm" required>
                                <option value="Yes">Yes</option>
                                <option value="No">No</option>
                                <option value="N/A" selected>N/A</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Regulated By</label>
                            <input type="text" name="regulated_by" class="form-control form-control-sm" placeholder="Regulating authority name">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">T.P. No</label>
                            <input type="text" name="tp_no" class="form-control form-control-sm" placeholder="Telephone Number">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold"><i class="bi bi-file-earmark-pdf-fill text-danger me-1"></i>Code of Conduct (PDF Document)</label>
                            <input type="file" name="code_of_conduct_pdf" class="form-control form-control-sm" accept="application/pdf">
                            <small class="text-muted">Attach PDF document representing this society's official Code of Conduct.</small>
                        </div>
                    </div>

                    <!-- DEDICATED COMMITTEE MEMBERS REGISTRATION SECTION -->
                    <div class="mt-4 pt-3 border-top">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-people-fill text-primary me-2"></i>2. Core Committee Members (Elected Officers & Appointees)</h6>
                                <small class="text-muted">Register key elected leadership and committee members for this society.</small>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary fw-bold" id="btnAddCommitteeRowAddModal">
                                <i class="bi bi-plus-circle me-1"></i>Add Member
                            </button>
                        </div>
                        <div class="table-responsive border rounded bg-white p-2">
                            <table class="table table-sm table-borderless align-middle mb-0">
                                <thead class="small text-secondary bg-light">
                                    <tr>
                                        <th style="width: 22%;">Position <span class="text-danger">*</span></th>
                                        <th style="width: 24%;">Member Name <span class="text-danger">*</span></th>
                                        <th style="width: 16%;">NIC</th>
                                        <th style="width: 22%;">Address</th>
                                        <th style="width: 14%;">Contact No</th>
                                        <th style="width: 2%;"></th>
                                    </tr>
                                </thead>
                                <tbody id="addModalCommitteeTbody">
                                    <tr>
                                        <td>
                                            <select name="committee_positions[]" class="form-select form-select-sm" required>
                                                <option value="President" selected>President</option>
                                                <option value="Vice President">Vice President</option>
                                                <option value="Secretary">Secretary</option>
                                                <option value="Assistant Secretary">Assistant Secretary</option>
                                                <option value="Treasurer">Treasurer</option>
                                                <option value="Committee Member">Committee Member</option>
                                            </select>
                                        </td>
                                        <td><input type="text" name="committee_names[]" class="form-control form-control-sm" placeholder="President Full Name" required></td>
                                        <td><input type="text" name="committee_nics[]" class="form-control form-control-sm" placeholder="NIC No"></td>
                                        <td><input type="text" name="committee_addresses[]" class="form-control form-control-sm" placeholder="Address"></td>
                                        <td><input type="text" name="committee_contacts[]" class="form-control form-control-sm" placeholder="Contact No"></td>
                                        <td class="text-center"><button type="button" class="btn btn-sm btn-link text-danger p-0 btn-remove-row" title="Remove"><i class="bi bi-x-circle-fill"></i></button></td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <select name="committee_positions[]" class="form-select form-select-sm" required>
                                                <option value="President">President</option>
                                                <option value="Vice President">Vice President</option>
                                                <option value="Secretary" selected>Secretary</option>
                                                <option value="Assistant Secretary">Assistant Secretary</option>
                                                <option value="Treasurer">Treasurer</option>
                                                <option value="Committee Member">Committee Member</option>
                                            </select>
                                        </td>
                                        <td><input type="text" name="committee_names[]" class="form-control form-control-sm" placeholder="Secretary Full Name" required></td>
                                        <td><input type="text" name="committee_nics[]" class="form-control form-control-sm" placeholder="NIC No"></td>
                                        <td><input type="text" name="committee_addresses[]" class="form-control form-control-sm" placeholder="Address"></td>
                                        <td><input type="text" name="committee_contacts[]" class="form-control form-control-sm" placeholder="Contact No"></td>
                                        <td class="text-center"><button type="button" class="btn btn-sm btn-link text-danger p-0 btn-remove-row" title="Remove"><i class="bi bi-x-circle-fill"></i></button></td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <select name="committee_positions[]" class="form-select form-select-sm" required>
                                                <option value="President">President</option>
                                                <option value="Vice President">Vice President</option>
                                                <option value="Secretary">Secretary</option>
                                                <option value="Assistant Secretary">Assistant Secretary</option>
                                                <option value="Treasurer" selected>Treasurer</option>
                                                <option value="Committee Member">Committee Member</option>
                                            </select>
                                        </td>
                                        <td><input type="text" name="committee_names[]" class="form-control form-control-sm" placeholder="Treasurer Full Name" required></td>
                                        <td><input type="text" name="committee_nics[]" class="form-control form-control-sm" placeholder="NIC No"></td>
                                        <td><input type="text" name="committee_addresses[]" class="form-control form-control-sm" placeholder="Address"></td>
                                        <td><input type="text" name="committee_contacts[]" class="form-control form-control-sm" placeholder="Contact No"></td>
                                        <td class="text-center"><button type="button" class="btn btn-sm btn-link text-danger p-0 btn-remove-row" title="Remove"><i class="bi bi-x-circle-fill"></i></button></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-sm fw-bold px-4">Save Society & Committee</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Record -->
<div class="modal fade" id="editSocModal" tabindex="-1" aria-labelledby="editSocModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            <div class="modal-content border-0 shadow">
                <div class="modal-header" style="background-color: #370709; color: white;">
                    <h5 class="modal-title fw-bold" id="editSocModalLabel"><i class="bi bi-pencil-square me-2"></i>Edit Livestock Society</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="bi bi-building me-2 text-primary"></i>1. Society Details</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">VS Range</label>
                            <input type="text" name="vs_range" id="edit_vs_range" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">G N Division</label>
                            <input type="text" name="gn_division" id="edit_gn_division" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Name & Address</label>
                            <textarea name="name_address" id="edit_name_address" class="form-control form-control-sm" rows="2" required></textarea>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Overall Objective</label>
                            <textarea name="overall_objective" id="edit_overall_objective" class="form-control form-control-sm" rows="2"></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Total Members Registered</label>
                            <input type="number" name="total_members" id="edit_total_members" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Reg. No</label>
                            <input type="text" name="reg_no" id="edit_reg_no" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Reg. Department</label>
                            <input type="text" name="reg_department" id="edit_reg_department" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Major Activities</label>
                            <textarea name="major_activities" id="edit_major_activities" class="form-control form-control-sm" rows="2"></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Financial Records Availability</label>
                            <select name="financial_records_availability" id="edit_financial_records_availability" class="form-select form-select-sm" required>
                                <option value="Yes">Yes</option>
                                <option value="No">No</option>
                                <option value="N/A">N/A</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Regulated By</label>
                            <input type="text" name="regulated_by" id="edit_regulated_by" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">T.P. No</label>
                            <input type="text" name="tp_no" id="edit_tp_no" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold"><i class="bi bi-file-earmark-pdf-fill text-danger me-1"></i>Code of Conduct (PDF Document)</label>
                            <input type="file" name="code_of_conduct_pdf" class="form-control form-control-sm" accept="application/pdf">
                            <div id="edit_code_of_conduct_preview" class="mt-1"></div>
                            <small class="text-muted">Attach new PDF to replace existing Code of Conduct, or leave empty to retain current.</small>
                        </div>
                    </div>

                    <!-- DEDICATED COMMITTEE SECTION IN EDIT MODAL -->
                    <div class="mt-4 pt-3 border-top">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-people-fill text-primary me-2"></i>2. Core Committee Members</h6>
                                <small class="text-muted">Manage committee officers and appointees for this society.</small>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary fw-bold" id="btnToggleEditAddMember">
                                <i class="bi bi-person-plus-fill me-1"></i>Add Committee Member
                            </button>
                        </div>

                        <!-- Inline Add Sub-Form -->
                        <div id="editModalAddMemberCard" class="card card-body bg-light border mb-2 d-none">
                            <h6 class="small fw-bold text-primary mb-2"><i class="bi bi-plus-circle me-1"></i>Add Committee Member</h6>
                            <div class="row g-2">
                                <div class="col-md-3">
                                    <label class="small fw-bold text-secondary">Position *</label>
                                    <select id="inline_mem_position" class="form-select form-select-sm">
                                        <option value="President">President</option>
                                        <option value="Vice President">Vice President</option>
                                        <option value="Secretary">Secretary</option>
                                        <option value="Assistant Secretary">Assistant Secretary</option>
                                        <option value="Treasurer">Treasurer</option>
                                        <option value="Committee Member" selected>Committee Member</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="small fw-bold text-secondary">Member Name *</label>
                                    <input type="text" id="inline_mem_name" class="form-control form-control-sm" placeholder="Full name">
                                </div>
                                <div class="col-md-3">
                                    <label class="small fw-bold text-secondary">NIC</label>
                                    <input type="text" id="inline_mem_nic" class="form-control form-control-sm" placeholder="NIC No">
                                </div>
                                <div class="col-md-3">
                                    <label class="small fw-bold text-secondary">Contact No</label>
                                    <input type="text" id="inline_mem_contact" class="form-control form-control-sm" placeholder="Phone No">
                                </div>
                                <div class="col-md-9">
                                    <label class="small fw-bold text-secondary">Address</label>
                                    <input type="text" id="inline_mem_address" class="form-control form-control-sm" placeholder="Residential / Postal Address">
                                </div>
                                <div class="col-md-3 d-flex align-items-end gap-1">
                                    <button type="button" class="btn btn-sm btn-success w-100 fw-bold" id="btnSaveInlineMember"><i class="bi bi-check me-1"></i>Save</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btnCancelInlineMember">✕</button>
                                </div>
                            </div>
                        </div>

                        <!-- Committee Members Table -->
                        <div class="table-responsive border rounded bg-white" style="max-height: 250px;">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="table-light small">
                                    <tr>
                                        <th>Position</th>
                                        <th>Name</th>
                                        <th>NIC</th>
                                        <th>Address</th>
                                        <th>Contact</th>
                                        <th class="text-center" style="width: 70px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="editModalCommitteeTbody" class="small">
                                    <!-- Loaded dynamically via AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm fw-bold px-4">Update Society</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ================================================================= -->
<!-- MODAL: SOCIETY PROFILE & GOVERNANCE (VIEW, MEETINGS, ORG CHART) -->
<!-- ================================================================= -->
<div class="modal fade" id="viewSocModal" tabindex="-1" aria-labelledby="viewSocModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-white py-3" style="background-color: #370709;">
                <div>
                    <h5 class="modal-title fw-bold mb-0" id="viewSocModalLabel">
                        <i class="bi bi-building me-2"></i><span id="profile_society_name">Society Profile</span>
                    </h5>
                    <small class="opacity-75" id="profile_society_range">Livestock Cooperative Profile & Governance</small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Society Profile Tabs Navigation -->
            <div class="bg-light px-4 pt-3 border-bottom">
                <ul class="nav nav-tabs border-bottom-0" id="societyProfileTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold text-dark" id="soc-info-tab" data-bs-toggle="tab" data-bs-target="#soc-info-pane" type="button" role="tab">
                            <i class="bi bi-info-circle-fill me-1 text-primary"></i>Society Details
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold text-dark position-relative" id="soc-meetings-tab" data-bs-toggle="tab" data-bs-target="#soc-meetings-pane" type="button" role="tab">
                            <i class="bi bi-calendar2-check-fill me-1 text-success"></i>Society Meetings Tracker
                            <span class="badge bg-success rounded-pill ms-1" id="profile_meetings_badge">0</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold text-dark position-relative" id="soc-committee-tab" data-bs-toggle="tab" data-bs-target="#soc-committee-pane" type="button" role="tab">
                            <i class="bi bi-diagram-3-fill me-1 text-warning"></i>Committee & Org Chart
                            <span class="badge bg-dark rounded-pill ms-1" id="profile_committee_badge">0</span>
                        </button>
                    </li>
                </ul>
            </div>

            <div class="modal-body p-4 bg-light">
                <div class="tab-content" id="societyProfileTabContent">
                    
                    <!-- TAB 1: GENERAL PROFILE DETAILS -->
                    <div class="tab-pane fade show active" id="soc-info-pane" role="tabpanel">
                        <div class="card border-0 shadow-sm bg-white p-3">
                            <table class="table table-bordered table-striped mb-0">
                                <tbody>
                                    <tr>
                                        <th style="width: 32%;">VS Range</th>
                                        <td id="view_vs_range"></td>
                                    </tr>
                                    <tr>
                                        <th>G N Division</th>
                                        <td id="view_gn_division"></td>
                                    </tr>
                                    <tr>
                                        <th>Name & Address</th>
                                        <td id="view_name_address"></td>
                                    </tr>
                                    <tr>
                                        <th>Overall Objective</th>
                                        <td id="view_overall_objective"></td>
                                    </tr>
                                    <tr>
                                        <th>Total Registered Members</th>
                                        <td id="view_total_members" class="fw-bold font-monospace text-primary"></td>
                                    </tr>
                                    <tr>
                                        <th>Reg. No</th>
                                        <td id="view_reg_no"></td>
                                    </tr>
                                    <tr>
                                        <th>Reg. Department</th>
                                        <td id="view_reg_department"></td>
                                    </tr>
                                    <tr>
                                        <th>Major Activities</th>
                                        <td id="view_major_activities"></td>
                                    </tr>
                                    <tr>
                                        <th>Availability of Financial Records</th>
                                        <td id="view_financial_records_availability"></td>
                                    </tr>
                                    <tr>
                                        <th>Regulated By</th>
                                        <td id="view_regulated_by"></td>
                                    </tr>
                                    <tr>
                                        <th>T.P. No</th>
                                        <td id="view_tp_no"></td>
                                    </tr>
                                    <tr>
                                        <th><i class="bi bi-file-earmark-pdf-fill text-danger me-1"></i>Code of Conduct</th>
                                        <td id="view_code_of_conduct"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- TAB 2: SOCIETY MEETINGS TRACKER -->
                    <div class="tab-pane fade" id="soc-meetings-pane" role="tabpanel">
                        <div class="row g-4">
                            <!-- Left: Log Meeting Form -->
                            <div class="col-lg-5">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-header bg-white py-3 border-bottom">
                                        <h6 class="fw-bold mb-0 text-success">
                                            <i class="bi bi-plus-circle-fill me-1"></i>Log Society Meeting
                                        </h6>
                                    </div>
                                    <div class="card-body p-3">
                                        <form id="formLogMeetingProfile">
                                            <input type="hidden" name="ajax_action" value="log_meeting">
                                            <input type="hidden" name="society_id" id="profile_meeting_society_id" value="">
                                            
                                            <div class="mb-3">
                                                <label class="form-label small fw-bold text-secondary">Meeting Date <span class="text-danger">*</span></label>
                                                <input type="date" name="meeting_date" id="profile_meeting_date" class="form-control form-control-sm" required value="<?= date('Y-m-d') ?>">
                                            </div>

                                            <div class="row g-2 mb-3">
                                                <div class="col-sm-7">
                                                    <label class="form-label small fw-bold text-secondary">Meeting Type</label>
                                                    <select name="meeting_type" id="profile_meeting_type" class="form-select form-select-sm">
                                                        <option value="Annual General Meeting (AGM)">Annual General Meeting (AGM)</option>
                                                        <option value="Monthly General Meeting" selected>Monthly General Meeting</option>
                                                        <option value="Executive Committee Meeting">Executive Committee Meeting</option>
                                                        <option value="Special / Emergency Meeting">Special / Emergency Meeting</option>
                                                        <option value="Farmer Awareness Session">Farmer Awareness Session</option>
                                                        <option value="Financial Review Meeting">Financial Review Meeting</option>
                                                    </select>
                                                </div>
                                                <div class="col-sm-5">
                                                    <label class="form-label small fw-bold text-secondary">Attendees Count</label>
                                                    <input type="number" name="attendees_count" id="profile_meeting_attendees" class="form-control form-control-sm" min="0" placeholder="e.g. 45">
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label small fw-bold text-secondary">Decisions Made <span class="text-danger">*</span></label>
                                                <textarea name="decisions_made" id="profile_meeting_decisions" rows="4" class="form-control form-control-sm" placeholder="Summarize key resolutions, decisions passed, loan/fund approvals, milk procurement prices, management decisions..." required></textarea>
                                                <div class="form-text x-small">Document the core resolutions, approvals, and decisions agreed during this meeting.</div>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label small fw-bold text-secondary">Additional Notes</label>
                                                <textarea name="notes" id="profile_meeting_notes" rows="2" class="form-control form-control-sm" placeholder="Optional notes, next target date, officer follow-ups..."></textarea>
                                            </div>

                                            <button type="submit" class="btn btn-success btn-sm w-100 fw-bold py-2 shadow-sm" id="btnSubmitMeetingProfile">
                                                <i class="bi bi-check-circle-fill me-1"></i>Save & Log Meeting
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Right: Meeting History Timeline -->
                            <div class="col-lg-7">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                                        <h6 class="fw-bold mb-0 text-dark">
                                            <i class="bi bi-clock-history me-1 text-success"></i>Meetings History & Decisions
                                        </h6>
                                        <span class="badge bg-success rounded-pill px-3" id="profile_meetings_list_count">0 Meetings</span>
                                    </div>
                                    <div class="card-body p-3 overflow-auto" style="max-height: 520px;" id="profile_meetings_container">
                                        <!-- Loaded dynamically -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 3: COMMITTEE & VISUAL ORG CHART -->
                    <div class="tab-pane fade" id="soc-committee-pane" role="tabpanel">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div>
                                    <h6 class="fw-bold mb-0 text-dark">
                                        <i class="bi bi-diagram-3-fill me-1 text-warning"></i>Executive Leadership Hierarchy
                                    </h6>
                                    <small class="text-muted">Visual organizational structure for this cooperative society</small>
                                </div>
                                <div>
                                    <button type="button" class="btn btn-outline-dark btn-sm" onclick="window.printOrgChart()">
                                        <i class="bi bi-printer me-1"></i>Print Org Chart
                                    </button>
                                </div>
                            </div>
                            <div class="card-body p-4 bg-white" id="orgchart_print_area">
                                <div id="orgchart_container" class="org-tree-wrapper">
                                    <!-- Loaded dynamically by renderOrgChart() -->
                                </div>
                            </div>
                        </div>

                        <!-- Committee Roster List -->
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-card-checklist me-1 text-primary"></i>Committee Roster</h6>
                                <span class="badge bg-secondary rounded-pill px-3" id="profile_committee_table_count">0 Members</span>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light small">
                                        <tr>
                                            <th style="width: 40px;">#</th>
                                            <th>Position</th>
                                            <th>Member Name</th>
                                            <th>NIC</th>
                                            <th>Address</th>
                                            <th>Contact</th>
                                            <th class="text-center" style="width: 80px;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="small" id="profile_committee_tbody">
                                        <!-- Loaded dynamically -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            <div class="modal-footer bg-white border-top py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php
ob_start();
?>
<style>
/* -------------------------------------------------------------
   ORGANIZATIONAL CHART & TIMELINE STYLES
   ------------------------------------------------------------- */
.org-tree-wrapper {
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 100%;
    overflow-x: auto;
    padding: 25px 15px;
    background: #f8fafc;
    border-radius: 8px;
}
.org-tier {
    display: flex;
    justify-content: center;
    align-items: stretch;
    flex-wrap: wrap;
    gap: 16px;
    position: relative;
    width: 100%;
    margin-bottom: 20px;
}
.org-card {
    background: #ffffff;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 10px rgba(0,0,0,0.04);
    padding: 14px 16px;
    min-width: 230px;
    max-width: 280px;
    text-align: center;
    transition: all 0.2s ease-in-out;
    position: relative;
}
.org-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.08);
}
.org-card.president-card {
    border-top: 5px solid #820100;
    background: linear-gradient(180deg, #fffefe 0%, #ffffff 100%);
    box-shadow: 0 6px 16px rgba(130, 1, 0, 0.12);
    min-width: 270px;
}
.org-card.officer-card {
    border-top: 4px solid #0d6efd;
}
.org-card.treasurer-card {
    border-top: 4px solid #198754;
}
.org-card.member-card {
    border-top: 4px solid #64748b;
    min-width: 210px;
}
.org-role-badge {
    display: inline-block;
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    padding: 4px 10px;
    border-radius: 20px;
    margin-bottom: 8px;
}
.org-avatar {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 10px auto;
    font-size: 1.25rem;
}
.org-card-title {
    font-size: 0.95rem;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 4px;
    line-height: 1.3;
}
.org-card-meta {
    font-size: 0.78rem;
    color: #64748b;
    line-height: 1.4;
}
.org-connector-v {
    width: 2px;
    height: 26px;
    background: #cbd5e1;
    margin: -10px auto 16px auto;
}
.timeline-item {
    position: relative;
    padding-left: 28px;
    margin-bottom: 22px;
    border-left: 2px solid #e2e8f0;
}
.timeline-item:last-child {
    border-left-color: transparent;
    margin-bottom: 0;
}
.timeline-dot {
    position: absolute;
    left: -7px;
    top: 4px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #198754;
    border: 2px solid #fff;
    box-shadow: 0 0 0 2px rgba(25,135,84,0.3);
}

@media print {
    body * {
        visibility: hidden;
    }
    #orgchart_print_area, #orgchart_print_area * {
        visibility: visible;
    }
    #orgchart_print_area {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    // Initialize Societies DataTable
    $("#socTable").DataTable({
        "order": [[0, "asc"]],
        "pageLength": 10,
        "dom": "Bfrtip",
        "language": {
            "emptyTable": "No records located for this range."
        },
        "buttons": [
            {
                extend: "csv",
                text: "<i class=\"bi bi-file-earmark-spreadsheet\"></i> CSV",
                className: "btn btn-sm btn-success me-2"
            },
            {
                extend: "pdf",
                text: "<i class=\"bi bi-file-pdf\"></i> PDF",
                className: "btn btn-sm btn-danger me-2"
            },
            {
                extend: "print",
                text: "<i class=\"bi bi-printer\"></i> Print",
                className: "btn btn-sm btn-dark"
            }
        ]
    });

    // Handle URL status messages
    var urlParams = new URLSearchParams(window.location.search);
    var status = urlParams.get('status');
    var msg = urlParams.get('msg') || '';

    if (status === 'success') {
        Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: msg ? msg : 'Operation completed successfully.',
            confirmButtonColor: '#370709'
        });
        window.history.replaceState({}, document.title, window.location.pathname + (urlParams.get('range_id') ? '?range_id=' + urlParams.get('range_id') : ''));
    } else if (status === 'error') {
        Swal.fire({
            icon: 'error',
            title: 'Operation Failed',
            text: msg ? msg : 'Could not process database action.',
            confirmButtonColor: '#370709'
        });
        window.history.replaceState({}, document.title, window.location.pathname + (urlParams.get('range_id') ? '?range_id=' + urlParams.get('range_id') : ''));
    }

    // Dynamic row addition for Add Society Modal
    $('#btnAddCommitteeRowAddModal').on('click', function() {
        var rowHtml = '<tr>' +
            '<td><select name="committee_positions[]" class="form-select form-select-sm" required>' +
                '<option value="President">President</option>' +
                '<option value="Vice President">Vice President</option>' +
                '<option value="Secretary">Secretary</option>' +
                '<option value="Assistant Secretary">Assistant Secretary</option>' +
                '<option value="Treasurer">Treasurer</option>' +
                '<option value="Committee Member" selected>Committee Member</option>' +
            '</select></td>' +
            '<td><input type="text" name="committee_names[]" class="form-control form-control-sm" placeholder="Member Full Name" required></td>' +
            '<td><input type="text" name="committee_nics[]" class="form-control form-control-sm" placeholder="NIC No"></td>' +
            '<td><input type="text" name="committee_addresses[]" class="form-control form-control-sm" placeholder="Address"></td>' +
            '<td><input type="text" name="committee_contacts[]" class="form-control form-control-sm" placeholder="Contact No"></td>' +
            '<td class="text-center"><button type="button" class="btn btn-sm btn-link text-danger p-0 btn-remove-row" title="Remove"><i class="bi bi-x-circle-fill"></i></button></td>' +
        '</tr>';
        $('#addModalCommitteeTbody').append(rowHtml);
    });

    $(document).on('click', '.btn-remove-row', function() {
        $(this).closest('tr').remove();
    });

    // Active society context for Profile Modal
    var activeSocietyId = null;
    var cachedCommittee = [];
    var cachedMeetings = [];

    // Open Profile Modal on Tab 1 (Details)
    $(document).on('click', '.btn-view', function() {
        var $row = $(this).closest('tr');
        var id = $row.data('id');
        activeSocietyId = id;
        populateProfileFields($row);
        var tab = new bootstrap.Tab(document.querySelector('#soc-info-tab'));
        tab.show();
        loadSocietyData(id);
        new bootstrap.Modal(document.getElementById('viewSocModal')).show();
    });

    // Open Profile Modal on Tab 2 (Meetings Tracker)
    $(document).on('click', '.btn-meetings', function() {
        var id = $(this).data('id');
        var $row = $(this).closest('tr');
        activeSocietyId = id;
        populateProfileFields($row);
        var tab = new bootstrap.Tab(document.querySelector('#soc-meetings-tab'));
        tab.show();
        loadSocietyData(id);
        new bootstrap.Modal(document.getElementById('viewSocModal')).show();
    });

    // Open Profile Modal on Tab 3 (Committee & Org Chart)
    $(document).on('click', '.btn-committee', function() {
        var id = $(this).data('id');
        var $row = $(this).closest('tr');
        activeSocietyId = id;
        populateProfileFields($row);
        var tab = new bootstrap.Tab(document.querySelector('#soc-committee-tab'));
        tab.show();
        loadSocietyData(id);
        new bootstrap.Modal(document.getElementById('viewSocModal')).show();
    });

    function populateProfileFields($row) {
        var name = $row.data('name_address') || 'Society #' + $row.data('id');
        $('#profile_society_name').text(name);
        $('#profile_society_range').text($row.data('vs_range') + ' Range' + ($row.data('gn_division') ? ' | ' + $row.data('gn_division') : ''));
        $('#view_vs_range').text($row.data('vs_range'));
        $('#view_gn_division').text($row.data('gn_division') || 'N/A');
        $('#view_name_address').html(($row.data('name_address') || '').replace(/\n/g, '<br>'));
        $('#view_overall_objective').html(($row.data('overall_objective') || '').replace(/\n/g, '<br>'));
        $('#view_total_members').text($row.data('total_members'));
        $('#view_reg_no').text($row.data('reg_no') || 'N/A');
        $('#view_reg_department').text($row.data('reg_department') || 'N/A');
        $('#view_major_activities').html(($row.data('major_activities') || '').replace(/\n/g, '<br>'));
        $('#view_financial_records_availability').text($row.data('financial_records_availability'));
        $('#view_regulated_by').text($row.data('regulated_by') || 'N/A');
        $('#view_tp_no').text($row.data('tp_no') || 'N/A');

        var coc = $row.data('code_of_conduct_pdf');
        if (coc) {
            $('#view_code_of_conduct').html('<a href="../../../' + coc + '" target="_blank" class="btn btn-sm btn-danger fw-bold"><i class="bi bi-file-earmark-pdf-fill me-1"></i>View Code of Conduct Document</a>');
        } else {
            $('#view_code_of_conduct').html('<span class="badge bg-light text-muted border">No PDF Document Attached</span>');
        }

        $('#profile_meeting_society_id').val($row.data('id'));
    }

    function loadSocietyData(societyId) {
        $('#profile_meetings_container').html('<div class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm text-success me-2"></div>Loading meetings...</div>');
        $('#profile_committee_tbody').html('<tr><td colspan="7" class="text-center py-3 text-muted"><div class="spinner-border spinner-border-sm text-primary me-2"></div>Loading roster...</td></tr>');
        $('#orgchart_container').html('<div class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm text-warning me-2"></div>Loading organizational chart...</div>');

        $.ajax({
            url: 'annual_livestock_societies.php',
            type: 'GET',
            data: { ajax_action: 'get_society_data', society_id: societyId },
            dataType: 'json',
            success: function(res) {
                if (!res.success) {
                    $('#profile_meetings_container').html('<div class="alert alert-danger py-2 small">' + res.message + '</div>');
                    return;
                }

                cachedMeetings = res.meetings || [];
                cachedCommittee = res.committee || [];

                // Badges
                $('#profile_meetings_badge').text(cachedMeetings.length);
                $('#profile_meetings_list_count').text(cachedMeetings.length + ' Meetings');
                $('.meeting-badge-' + societyId).text(cachedMeetings.length);

                $('#profile_committee_badge').text(cachedCommittee.length);
                $('#profile_committee_table_count').text(cachedCommittee.length + ' Members');
                $('.committee-badge-' + societyId).text(cachedCommittee.length);

                renderMeetingsTimeline(cachedMeetings);
                renderCommitteeRoster(cachedCommittee);
                renderOrgChart(cachedCommittee, res.society);
            }
        });
    }

    function renderMeetingsTimeline(meetings) {
        if (!meetings || meetings.length === 0) {
            $('#profile_meetings_container').html(
                '<div class="text-center py-5 text-muted">' +
                '<i class="bi bi-calendar-x fs-1 text-secondary opacity-50 mb-2 d-block"></i>' +
                '<p class="mb-1 fw-bold">No meetings logged yet for this society.</p>' +
                '<p class="small">Use the form on the left to capture meeting dates and decisions.</p>' +
                '</div>'
            );
            return;
        }

        var html = '';
        meetings.forEach(function(m) {
            html += '<div class="timeline-item">';
            html += '  <div class="timeline-dot"></div>';
            html += '  <div class="card border-0 shadow-sm p-3">';
            html += '    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">';
            html += '      <div>';
            html += '        <h6 class="fw-bold mb-0 text-success"><i class="bi bi-calendar-event me-1"></i>' + escapeHtml(m.meeting_date) + '</h6>';
            html += '        <span class="badge bg-light text-dark border me-1">' + escapeHtml(m.meeting_type) + '</span>';
            if (m.attendees_count) {
                html += '        <span class="badge bg-info text-dark"><i class="bi bi-people-fill me-1"></i>' + escapeHtml(m.attendees_count) + ' Attendees</span>';
            }
            html += '      </div>';
            html += '      <button class="btn btn-sm btn-outline-danger btn-delete-meeting py-0 px-2" data-id="' + m.id + '" title="Delete Meeting Log"><i class="bi bi-trash"></i></button>';
            html += '    </div>';
            html += '    <div class="p-2 rounded bg-light mb-2">';
            html += '      <strong class="d-block small text-uppercase text-secondary mb-1"><i class="bi bi-check2-circle text-success me-1"></i>Main Decisions & Resolutions:</strong>';
            html += '      <p class="mb-0 small text-dark" style="white-space: pre-line;">' + escapeHtml(m.decisions_made) + '</p>';
            html += '    </div>';
            if (m.notes) {
                html += '    <small class="text-muted"><i class="bi bi-info-circle me-1"></i><strong>Notes:</strong> ' + escapeHtml(m.notes) + '</small>';
            }
            html += '  </div>';
            html += '</div>';
        });
        $('#profile_meetings_container').html(html);
    }

    function renderCommitteeRoster(committee) {
        if (!committee || committee.length === 0) {
            $('#profile_committee_tbody').html('<tr><td colspan="7" class="text-center py-4 text-muted"><i class="bi bi-info-circle me-1"></i>No committee members registered yet.</td></tr>');
            return;
        }

        var html = '';
        committee.forEach(function(mem, idx) {
            var badgeClass = 'bg-secondary';
            if (mem.position.indexOf('President') !== -1 || mem.position.indexOf('Chair') !== -1) badgeClass = 'bg-danger text-white';
            else if (mem.position.indexOf('Secretary') !== -1) badgeClass = 'bg-info text-dark';
            else if (mem.position.indexOf('Treasurer') !== -1) badgeClass = 'bg-success text-white';
            else if (mem.position.indexOf('Vice') !== -1) badgeClass = 'bg-primary text-white';

            html += '<tr>';
            html += '  <td class="text-center text-muted fw-bold">' + (idx + 1) + '</td>';
            html += '  <td><span class="badge ' + badgeClass + '">' + escapeHtml(mem.position) + '</span></td>';
            html += '  <td class="fw-bold text-dark">' + escapeHtml(mem.member_name) + '</td>';
            html += '  <td>' + (mem.nic ? '<span class="font-monospace">' + escapeHtml(mem.nic) + '</span>' : '<span class="text-muted">-</span>') + '</td>';
            html += '  <td>' + (mem.address ? escapeHtml(mem.address) : '<span class="text-muted">-</span>') + '</td>';
            html += '  <td>' + (mem.contact_no ? escapeHtml(mem.contact_no) : '<span class="text-muted">-</span>') + '</td>';
            html += '  <td class="text-center">';
            html += '    <button class="btn btn-sm btn-outline-danger btn-delete-committee-profile py-0 px-2" data-id="' + mem.id + '" title="Remove Member"><i class="bi bi-trash"></i></button>';
            html += '  </td>';
            html += '</tr>';
        });
        $('#profile_committee_tbody').html(html);
    }

    // Submit Meeting in Profile Modal
    $('#formLogMeetingProfile').on('submit', function(e) {
        e.preventDefault();
        var $btn = $('#btnSubmitMeetingProfile');
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Saving...');

        $.ajax({
            url: 'annual_livestock_societies.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(res) {
                $btn.prop('disabled', false).html('<i class="bi bi-check-circle-fill me-1"></i>Save & Log Meeting');
                if (res.success) {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: res.message,
                        showConfirmButton: false,
                        timer: 2500
                    });
                    $('#profile_meeting_decisions').val('');
                    $('#profile_meeting_notes').val('');
                    $('#profile_meeting_attendees').val('');
                    loadSocietyData(activeSocietyId);
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: res.message });
                }
            },
            error: function() {
                $btn.prop('disabled', false).html('<i class="bi bi-check-circle-fill me-1"></i>Save & Log Meeting');
                Swal.fire({ icon: 'error', title: 'Server Error', text: 'Could not connect to server.' });
            }
        });
    });

    // Delete Meeting
    $(document).on('click', '.btn-delete-meeting', function() {
        var meetingId = $(this).data('id');
        Swal.fire({
            icon: 'warning',
            title: 'Delete Meeting Record?',
            text: 'Are you sure you want to delete this meeting log and decisions?',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Delete'
        }).then(function(result) {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'annual_livestock_societies.php',
                    type: 'POST',
                    data: { ajax_action: 'delete_meeting', meeting_id: meetingId, society_id: activeSocietyId },
                    dataType: 'json',
                    success: function(res) {
                        if (res.success) {
                            loadSocietyData(activeSocietyId);
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error', text: res.message });
                        }
                    }
                });
            }
        });
    });

    // Delete Committee Member in Profile Modal
    $(document).on('click', '.btn-delete-committee-profile', function() {
        var memId = $(this).data('id');
        Swal.fire({
            icon: 'warning',
            title: 'Remove Member?',
            text: 'Are you sure you want to remove this member from the committee?',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Remove'
        }).then(function(result) {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'annual_livestock_societies.php',
                    type: 'POST',
                    data: { ajax_action: 'delete_committee_member', member_id: memId, society_id: activeSocietyId },
                    dataType: 'json',
                    success: function(res) {
                        if (res.success) {
                            loadSocietyData(activeSocietyId);
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error', text: res.message });
                        }
                    }
                });
            }
        });
    });

    // Edit Modal Open
    $(document).on('click', '.btn-edit', function() {
        var $row = $(this).closest('tr');
        var id = $row.data('id');
        $('#edit_id').val(id);
        $('#edit_vs_range').val($row.data('vs_range'));
        $('#edit_gn_division').val($row.data('gn_division'));
        $('#edit_name_address').val($row.data('name_address'));
        $('#edit_overall_objective').val($row.data('overall_objective'));
        $('#edit_total_members').val($row.data('total_members'));
        $('#edit_reg_no').val($row.data('reg_no'));
        $('#edit_reg_department').val($row.data('reg_department'));
        $('#edit_major_activities').val($row.data('major_activities'));
        $('#edit_financial_records_availability').val($row.data('financial_records_availability'));
        $('#edit_regulated_by').val($row.data('regulated_by'));
        $('#edit_tp_no').val($row.data('tp_no'));

        var coc = $row.data('code_of_conduct_pdf');
        if (coc) {
            $('#edit_code_of_conduct_preview').html('<span class="badge bg-light text-dark border me-2"><i class="bi bi-file-earmark-pdf-fill text-danger me-1"></i>Current PDF Attached</span><a href="../../../' + coc + '" target="_blank" class="btn btn-sm btn-outline-danger py-0 px-2 fw-bold">Open File</a>');
        } else {
            $('#edit_code_of_conduct_preview').html('<small class="text-muted fst-italic">No document currently attached.</small>');
        }

        // Hide add inline member sub-form
        $('#editModalAddMemberCard').addClass('d-none');
        loadEditModalCommittee(id);

        new bootstrap.Modal(document.getElementById('editSocModal')).show();
    });

    function loadEditModalCommittee(societyId) {
        $('#editModalCommitteeTbody').html('<tr><td colspan="6" class="text-center py-2 text-muted"><div class="spinner-border spinner-border-sm text-primary me-2"></div>Loading committee...</td></tr>');
        $.ajax({
            url: 'annual_livestock_societies.php',
            type: 'GET',
            data: { ajax_action: 'get_society_data', society_id: societyId },
            dataType: 'json',
            success: function(res) {
                if (!res.success) return;
                var list = res.committee || [];
                if (list.length === 0) {
                    $('#editModalCommitteeTbody').html('<tr><td colspan="6" class="text-center py-3 text-muted"><i class="bi bi-info-circle me-1"></i>No committee members logged. Click "Add Committee Member" to add officers.</td></tr>');
                    return;
                }
                var html = '';
                list.forEach(function(mem) {
                    html += '<tr>';
                    html += '  <td><span class="badge bg-light text-dark border fw-bold">' + escapeHtml(mem.position) + '</span></td>';
                    html += '  <td class="fw-bold text-dark">' + escapeHtml(mem.member_name) + '</td>';
                    html += '  <td>' + escapeHtml(mem.nic || '-') + '</td>';
                    html += '  <td>' + escapeHtml(mem.address || '-') + '</td>';
                    html += '  <td>' + escapeHtml(mem.contact_no || '-') + '</td>';
                    html += '  <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger btn-delete-inline-member py-0 px-2" data-id="' + mem.id + '" title="Remove"><i class="bi bi-trash"></i></button></td>';
                    html += '</tr>';
                });
                $('#editModalCommitteeTbody').html(html);
            }
        });
    }

    $('#btnToggleEditAddMember').on('click', function() {
        $('#editModalAddMemberCard').toggleClass('d-none');
    });

    $('#btnCancelInlineMember').on('click', function() {
        $('#editModalAddMemberCard').addClass('d-none');
    });

    $('#btnSaveInlineMember').on('click', function() {
        var societyId = $('#edit_id').val();
        var pos = $('#inline_mem_position').val();
        var name = $('#inline_mem_name').val().trim();
        var nic = $('#inline_mem_nic').val().trim();
        var addr = $('#inline_mem_address').val().trim();
        var contact = $('#inline_mem_contact').val().trim();

        if (!name) {
            Swal.fire({ icon: 'warning', title: 'Missing Name', text: 'Please enter the committee member name.' });
            return;
        }

        $.ajax({
            url: 'annual_livestock_societies.php',
            type: 'POST',
            data: {
                ajax_action: 'save_committee_member',
                society_id: societyId,
                position: pos,
                member_name: name,
                nic: nic,
                address: addr,
                contact_no: contact
            },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    $('#inline_mem_name').val('');
                    $('#inline_mem_nic').val('');
                    $('#inline_mem_address').val('');
                    $('#inline_mem_contact').val('');
                    $('#editModalAddMemberCard').addClass('d-none');
                    loadEditModalCommittee(societyId);
                    $('.committee-badge-' + societyId).text(res.total_committee);
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: res.message });
                }
            }
        });
    });

    $(document).on('click', '.btn-delete-inline-member', function() {
        var memId = $(this).data('id');
        var societyId = $('#edit_id').val();
        Swal.fire({
            icon: 'warning',
            title: 'Remove Member?',
            text: 'Are you sure you want to remove this committee member?',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Remove'
        }).then(function(result) {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'annual_livestock_societies.php',
                    type: 'POST',
                    data: { ajax_action: 'delete_committee_member', member_id: memId, society_id: societyId },
                    dataType: 'json',
                    success: function(res) {
                        if (res.success) {
                            loadEditModalCommittee(societyId);
                            $('.committee-badge-' + societyId).text(res.total_committee);
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error', text: res.message });
                        }
                    }
                });
            }
        });
    });

    // Delete Record
    $(document).on('click', '.btn-delete', function(e) {
        e.preventDefault();
        var deleteUrl = $(this).attr('href');
        var $row = $(this).closest('tr');
        var id = $row.data('id');

        Swal.fire({
            icon: 'warning',
            title: 'Delete Society Record?',
            html: 'Are you sure you want to permanently delete the society record <strong>#' + id + '</strong>?<br>This action cannot be undone.',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Delete',
            cancelButtonText: 'Cancel'
        }).then(function(result) {
            if (result.isConfirmed) {
                window.location.href = deleteUrl;
            }
        });
    });

    // Render Org Chart
    function renderOrgChart(committee, society) {
        var $chart = $('#orgchart_container');
        if (!committee || committee.length === 0) {
            $chart.html(
                '<div class="text-center py-5 text-muted">' +
                '<i class="bi bi-diagram-3 fs-1 text-warning opacity-50 mb-3 d-block"></i>' +
                '<h5 class="fw-bold text-dark mb-1">No Committee Members Registered</h5>' +
                '<p class="small text-muted mb-0">Register elected leaders to generate the visual hierarchy.</p>' +
                '</div>'
            );
            return;
        }

        var tier1 = [];
        var tier2 = [];
        var tier3 = [];

        committee.forEach(function(mem) {
            var pos = (mem.position || '').toLowerCase();
            if (pos.indexOf('president') !== -1 || pos.indexOf('chairperson') !== -1 || pos.indexOf('chairman') !== -1) {
                tier1.push(mem);
            } else if (pos.indexOf('vice') !== -1 || pos.indexOf('secretary') !== -1 || pos.indexOf('treasurer') !== -1) {
                tier2.push(mem);
            } else {
                tier3.push(mem);
            }
        });

        if (tier1.length === 0 && committee.length > 0) {
            tier1.push(committee[0]);
            var rest = committee.slice(1);
            tier2 = [];
            tier3 = [];
            rest.forEach(function(m) {
                var p = (m.position || '').toLowerCase();
                if (p.indexOf('vice') !== -1 || p.indexOf('secretary') !== -1 || p.indexOf('treasurer') !== -1) {
                    tier2.push(m);
                } else {
                    tier3.push(m);
                }
            });
        }

        var chartHtml = '';

        // TIER 1: PRESIDENT / CHAIRPERSON
        chartHtml += '<div class="org-tier">';
        tier1.forEach(function(p) {
            chartHtml += '<div class="org-card president-card">';
            chartHtml += '  <div class="org-avatar bg-danger bg-opacity-10 text-danger"><i class="bi bi-person-badge-fill"></i></div>';
            chartHtml += '  <span class="org-role-badge bg-danger text-white"><i class="bi bi-award-fill me-1"></i>' + escapeHtml(p.position) + '</span>';
            chartHtml += '  <div class="org-card-title">' + escapeHtml(p.member_name) + '</div>';
            if (p.nic) chartHtml += '<div class="org-card-meta"><i class="bi bi-card-heading me-1"></i>NIC: ' + escapeHtml(p.nic) + '</div>';
            if (p.contact_no) chartHtml += '<div class="org-card-meta"><i class="bi bi-telephone-fill me-1"></i>' + escapeHtml(p.contact_no) + '</div>';
            if (p.address) chartHtml += '<div class="org-card-meta text-truncate" title="' + escapeHtml(p.address) + '"><i class="bi bi-geo-alt-fill me-1"></i>' + escapeHtml(p.address) + '</div>';
            chartHtml += '</div>';
        });
        chartHtml += '</div>';

        // Connector down to Tier 2
        if (tier2.length > 0 || tier3.length > 0) {
            chartHtml += '<div class="org-connector-v"></div>';
        }

        // TIER 2: EXECUTIVE OFFICERS
        if (tier2.length > 0) {
            chartHtml += '<div class="org-tier">';
            tier2.forEach(function(o) {
                var isTreasurer = (o.position || '').toLowerCase().indexOf('treasurer') !== -1;
                var cardClass = isTreasurer ? 'treasurer-card' : 'officer-card';
                var badgeClass = isTreasurer ? 'bg-success text-white' : 'bg-primary text-white';
                var avatarColor = isTreasurer ? 'bg-success bg-opacity-10 text-success' : 'bg-primary bg-opacity-10 text-primary';

                chartHtml += '<div class="org-card ' + cardClass + '">';
                chartHtml += '  <div class="org-avatar ' + avatarColor + '"><i class="bi bi-person-fill"></i></div>';
                chartHtml += '  <span class="org-role-badge ' + badgeClass + '">' + escapeHtml(o.position) + '</span>';
                chartHtml += '  <div class="org-card-title">' + escapeHtml(o.member_name) + '</div>';
                if (o.nic) chartHtml += '<div class="org-card-meta"><i class="bi bi-card-heading me-1"></i>NIC: ' + escapeHtml(o.nic) + '</div>';
                if (o.contact_no) chartHtml += '<div class="org-card-meta"><i class="bi bi-telephone me-1"></i>' + escapeHtml(o.contact_no) + '</div>';
                chartHtml += '</div>';
            });
            chartHtml += '</div>';
        }

        // Connector down to Tier 3
        if (tier3.length > 0) {
            chartHtml += '<div class="org-connector-v"></div>';
            chartHtml += '<div class="w-100 text-center mb-2"><span class="badge bg-light text-secondary border px-3 py-1 text-uppercase fw-bold" style="letter-spacing: 0.5px;">Elected Committee Members & Appointees</span></div>';
            
            chartHtml += '<div class="org-tier">';
            tier3.forEach(function(m) {
                chartHtml += '<div class="org-card member-card">';
                chartHtml += '  <div class="org-avatar bg-secondary bg-opacity-10 text-secondary"><i class="bi bi-person"></i></div>';
                chartHtml += '  <span class="org-role-badge bg-secondary text-white">' + escapeHtml(m.position) + '</span>';
                chartHtml += '  <div class="org-card-title">' + escapeHtml(m.member_name) + '</div>';
                if (m.nic) chartHtml += '<div class="org-card-meta"><i class="bi bi-card-heading me-1"></i>NIC: ' + escapeHtml(m.nic) + '</div>';
                if (m.contact_no) chartHtml += '<div class="org-card-meta"><i class="bi bi-telephone me-1"></i>' + escapeHtml(m.contact_no) + '</div>';
                if (m.address) chartHtml += '<div class="org-card-meta text-truncate" title="' + escapeHtml(m.address) + '"><i class="bi bi-geo-alt me-1"></i>' + escapeHtml(m.address) + '</div>';
                chartHtml += '</div>';
            });
            chartHtml += '</div>';
        }

        $chart.html(chartHtml);
    }

    window.printOrgChart = function() {
        window.print();
    };

    function escapeHtml(text) {
        if (!text) return '';
        return String(text)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
});
</script>
<?php
$pageScripts = ob_get_clean();
require_once '../../../includes/footer.php';
?>