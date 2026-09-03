<?php
// add_user.php  →  Keep this file forever (protect it later with .htaccess)
require_once 'config/db_connect.php';

function ensure_schema_updates($mysqli) {
    // 1. Ensure columns exist
    $checks = [
        'training_center_id' => 'INT NULL',
        'training_center_location' => 'VARCHAR(255) NULL',
        'district_id' => 'INT NULL'
    ];

    foreach ($checks as $column => $definition) {
        $result = $mysqli->query("SHOW COLUMNS FROM users LIKE '" . $mysqli->real_escape_string($column) . "'");
        if ($result && $result->num_rows === 0) {
            $mysqli->query("ALTER TABLE users ADD COLUMN `" . $mysqli->real_escape_string($column) . "` " . $definition);
        }
    }

    // 2. Ensure role enum includes new Deputy Director roles and Range Office user roles
    $role_col_res = $mysqli->query("SHOW COLUMNS FROM users LIKE 'role'");
    if ($role_col_res && $row = $role_col_res->fetch_assoc()) {
        $type = $row['Type'];
        if (strpos($type, 'government_veterinary_surgeon') === false || strpos($type, 'additional_veterinary_surgeon') === false || strpos($type, 'livestock_development_officer') === false) {
            $mysqli->query("ALTER TABLE users MODIFY COLUMN role ENUM('provincial_director','district_dd','veterinary_surgeon','training_officer','sms','farms_dd','finance_admin','planning_officer','administrator','data_entry','employee','deputy_director_hq_1','deputy_director_hq_2','government_veterinary_surgeon','additional_veterinary_surgeon','livestock_development_officer','development_officer','driver','dispensary_assistant','department_laborer','night_watcher') NOT NULL");
        }
    }
}

ensure_schema_updates($mysqli);

// Fetch active farms for select dropdown
$farms = [];
$farms_res = $mysqli->query("SELECT id, farm_name, location FROM regional_farms WHERE is_active = 1 ORDER BY farm_name");
if ($farms_res) {
    while ($row = $farms_res->fetch_assoc()) {
        $farms[] = $row;
    }
}

$training_centers = [];
$training_center_res = $mysqli->query("SELECT id, center_name, location FROM training_centers WHERE is_active = 1 ORDER BY location ASC, center_name ASC");
if ($training_center_res) {
    while ($row = $training_center_res->fetch_assoc()) {
        $training_centers[] = $row;
    }
}

$ranges = [];
$ranges_res = $mysqli->query("SELECT id, name, district_id FROM veterinary_ranges WHERE is_active = 1 ORDER BY name ASC");
if ($ranges_res) {
    while ($row = $ranges_res->fetch_assoc()) {
        $ranges[] = $row;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username   = trim($_POST['username']);
    $email      = trim($_POST['email']);
    $password   = $_POST['password'];                    // plain text
    $full_name  = trim($_POST['full_name']);
    $role       = $_POST['role'];
    $district   = $_POST['district'] ?? 'Provincial';
    $training_center_id = isset($_POST['training_center_id']) && $_POST['training_center_id'] !== '' ? intval($_POST['training_center_id']) : null;
    $training_center_location = isset($_POST['training_center_location']) ? trim($_POST['training_center_location']) : '';
    $range_id   = isset($_POST['range_id']) && $_POST['range_id'] !== '' ? intval($_POST['range_id']) : null;

    $range_roles_designations = [
        'government_veterinary_surgeon' => 'Government Veterinary Surgeon (GVS)',
        'additional_veterinary_surgeon' => 'Additional Veterinary Surgeon (AVS)',
        'livestock_development_officer' => 'Livestock Development Officer (or Instructor)',
        'development_officer'           => 'Development Officer (DO)',
        'driver'                        => 'Driver',
        'dispensary_assistant'          => 'Dispensary Assistant',
        'department_laborer'            => 'Department Laborer',
        'night_watcher'                 => 'Night Watcher',
        'veterinary_surgeon'            => 'Government Veterinary Surgeon (GVS)'
    ];

    $designation = trim($_POST['designation'] ?? ($range_roles_designations[$role] ?? ''));

    $farm_id = null;
    $district_id = null;
    $validation_failed = false;

    // Role-specific district assignment logic
    if (in_array($role, ['deputy_director_hq_1', 'deputy_director_hq_2', 'provincial_director'])) {
        $district = 'Provincial';
        $district_id = null;
    } elseif ($role === 'district_dd') {
        if (empty($district) || $district === 'Provincial') {
            echo '<div class="alert alert-danger">Error: Please select a specific District for District Deputy Director.</div>';
            $validation_failed = true;
        }
    }

    // Map District string to district_id
    if ($district === 'Amparai' || $district === 'Ampara') {
        $district_id = 1;
    } elseif ($district === 'Batticaloa') {
        $district_id = 2;
    } elseif ($district === 'Trincomalee') {
        $district_id = 3;
    }

    if ($role === 'training_officer') {
        if (is_null($training_center_id) || $training_center_location === '') {
            echo '<div class="alert alert-danger">Error: Training center and training center location are required for this role.</div>';
            $validation_failed = true;
        } else {
            $center_check = $mysqli->prepare("SELECT id, location FROM training_centers WHERE id = ? AND is_active = 1 LIMIT 1");
            if ($center_check) {
                $center_check->bind_param("i", $training_center_id);
                $center_check->execute();
                $center_result = $center_check->get_result();
                if ($center_result->num_rows === 0) {
                    echo '<div class="alert alert-danger">Error: Invalid Training Center selection.</div>';
                    $validation_failed = true;
                } else {
                    $center_data = $center_result->fetch_assoc();
                    if (strcasecmp(trim($center_data['location'] ?? ''), $training_center_location) !== 0) {
                        echo '<div class="alert alert-danger">Error: The selected training center does not match the chosen location.</div>';
                        $validation_failed = true;
                    }
                }
                $center_check->close();
            }

            if (!$validation_failed) {
                $existing_training_user = $mysqli->prepare("SELECT id FROM users WHERE role = 'training_officer' AND training_center_id = ? AND is_active = 1 LIMIT 1");
                if ($existing_training_user) {
                    $existing_training_user->bind_param("i", $training_center_id);
                    $existing_training_user->execute();
                    $existing_training_user_result = $existing_training_user->get_result();
                    if ($existing_training_user_result->num_rows > 0) {
                        echo '<div class="alert alert-danger">Error: A training officer is already assigned to this training center location.</div>';
                        $validation_failed = true;
                    }
                    $existing_training_user->close();
                }
            }
        }
    }

    // Validation for Farms Officer (farms_dd)
    if ($role === 'farms_dd') {
        $farm_id = isset($_POST['farm_id']) && $_POST['farm_id'] !== '' ? intval($_POST['farm_id']) : null;
        if (is_null($farm_id)) {
            echo '<div class="alert alert-danger">Error: Farm assignment is required for Deputy Director (Farms Operation) role.</div>';
            $validation_failed = true;
        } else {
            // Verify if farm_id exists in regional_farms
            $farm_check = $mysqli->prepare("SELECT id FROM regional_farms WHERE id = ?");
            if ($farm_check) {
                $farm_check->bind_param("i", $farm_id);
                $farm_check->execute();
                $farm_res = $farm_check->get_result();
                if ($farm_res->num_rows === 0) {
                    echo '<div class="alert alert-danger">Error: Invalid Farm selection.</div>';
                    $validation_failed = true;
                }
                $farm_check->close();
            }
        }
    }

    if (!$validation_failed) {
        // Auto hash the password
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $is_active = 1;

        if ($role === 'training_officer') {
            $stmt = $mysqli->prepare("INSERT INTO users 
                (username, email, password, full_name, role, designation, district, district_id, farm_id, training_center_id, training_center_location, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            $stmt->bind_param("sssssssiisis", $username, $email, $hash, $full_name, $role, $designation, $district, $district_id, $farm_id, $training_center_id, $training_center_location, $is_active);
        } else {
            $stmt = $mysqli->prepare("INSERT INTO users 
                (username, email, password, full_name, role, designation, district, district_id, farm_id, range_id, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            $stmt->bind_param("sssssssiisi", $username, $email, $hash, $full_name, $role, $designation, $district, $district_id, $farm_id, $range_id, $is_active);
        }

        if ($stmt->execute()) {
            echo '<div class="alert alert-success">✅ User <b>' . htmlspecialchars($username) . 
                 '</b> created successfully!<br>Password: <b>' . htmlspecialchars($password) . '</b></div>';
        } else {
            echo '<div class="alert alert-danger">Error: ' . $stmt->error . '</div>';
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add New User</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="card shadow">
        <div class="card-header bg-primary text-white"><h4>Add New User</h4></div>
        <div class="card-body">
            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label>Username</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label>Password (plain)</label>
                        <input type="text" name="password" class="form-control" value="123yoh" required>
                    </div>
                    <div class="col-md-6">
                        <label>Full Name</label>
                        <input type="text" name="full_name" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label>Role</label>
                        <select name="role" id="roleSelect" class="form-select" required>
                            <optgroup label="Range Office Roles">
                                <option value="government_veterinary_surgeon">Government Veterinary Surgeon</option>
                                <option value="additional_veterinary_surgeon">Additional Veterinary Surgeon</option>
                                <option value="livestock_development_officer">Livestock Development Officer (or Instructor)</option>
                                <option value="development_officer">Development Officer</option>
                                <option value="driver">Driver</option>
                                <option value="dispensary_assistant">Dispensary Assistant</option>
                                <option value="department_laborer">Department Laborer</option>
                                <option value="night_watcher">Night Watcher</option>
                            </optgroup>
                            <optgroup label="Headquarters & District Roles">
                                <option value="district_dd">District Deputy Director</option>
                                <option value="deputy_director_hq_1">Deputy Director H/Q1</option>
                                <option value="deputy_director_hq_2">Deputy Director H/Q2</option>
                                <option value="provincial_director">Provincial Director</option>
                                <option value="training_officer">Training Officer</option>
                                <option value="sms">Subject Matter Specialist</option>
                                <option value="administrator">Administrator</option>
                                <option value="finance_admin">Finance Admin</option>
                                <option value="planning_officer">Planning Officer</option>
                                <option value="farms_dd">Deputy Director (Farms Operation)</option>
                                <option value="veterinary_surgeon">Veterinary Surgeon (Legacy)</option>
                                <option value="employee">Employee (General)</option>
                            </optgroup>
                        </select>
                    </div>
                    <div class="col-md-6" id="districtContainer">
                        <label id="districtLabel">District</label>
                        <select name="district" id="districtSelect" class="form-select" required>
                            <option value="Amparai">Amparai</option>
                            <option value="Batticaloa">Batticaloa</option>
                            <option value="Trincomalee">Trincomalee</option>
                            <option value="Provincial" id="districtOptionProvincial">Provincial</option>
                        </select>
                    </div>

                    <!-- Range Office Selection dynamically toggled -->
                    <div class="col-md-6" id="range_container" style="display: none;">
                        <label>Veterinary Range Office</label>
                        <select name="range_id" id="range_id" class="form-select">
                            <option value="">-- Select Range Office --</option>
                            <?php foreach ($ranges as $range): ?>
                                <option value="<?= $range['id'] ?>" data-district-id="<?= $range['district_id'] ?>"><?= htmlspecialchars($range['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6" id="training_center_location_container" style="display: none;">
                        <label>Training Center Location</label>
                        <select name="training_center_location" id="training_center_location" class="form-select">
                            <option value="">-- Select Location --</option>
                            <?php foreach ($training_centers as $center): ?>
                                <option value="<?= htmlspecialchars($center['location']) ?>"><?= htmlspecialchars($center['location']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6" id="training_center_container" style="display: none;">
                        <label>Training Center</label>
                        <select name="training_center_id" id="training_center_id" class="form-select">
                            <option value="">-- Select Training Center --</option>
                            <?php foreach ($training_centers as $center): ?>
                                <option value="<?= $center['id'] ?>" data-location="<?= htmlspecialchars($center['location']) ?>"><?= htmlspecialchars($center['center_name']) ?> (<?= htmlspecialchars($center['location']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <!-- Farm selection container dynamically toggled -->
                    <div class="col-md-6" id="farm_container" style="display: none;">
                        <label>Farm Assignment</label>
                        <select name="farm_id" id="farm_id" class="form-select">
                            <option value="">-- Select Farm --</option>
                            <?php foreach ($farms as $farm): ?>
                                <option value="<?= $farm['id'] ?>"><?= htmlspecialchars($farm['farm_name']) ?> (<?= htmlspecialchars($farm['location']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <hr>
                <button type="submit" class="btn btn-success btn-lg">Create User</button>
                <a href="index.php" class="btn btn-secondary btn-lg">Back to Login</a>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    var roleSelect = document.getElementById('roleSelect');
    var districtContainer = document.getElementById('districtContainer');
    var districtSelect = document.getElementById('districtSelect');
    var provincialOption = document.getElementById('districtOptionProvincial');
    var farmContainer = document.getElementById('farm_container');
    var farmSelect = document.getElementById('farm_id');
    var rangeContainer = document.getElementById('range_container');
    var rangeSelect = document.getElementById('range_id');
    var trainingCenterLocationContainer = document.getElementById('training_center_location_container');
    var trainingCenterLocationSelect = document.getElementById('training_center_location');
    var trainingCenterContainer = document.getElementById('training_center_container');
    var trainingCenterSelect = document.getElementById('training_center_id');

    var rangeRoles = [
        'government_veterinary_surgeon',
        'additional_veterinary_surgeon',
        'livestock_development_officer',
        'development_officer',
        'driver',
        'dispensary_assistant',
        'department_laborer',
        'night_watcher',
        'veterinary_surgeon'
    ];

    function filterRangesByDistrict() {
        var district = districtSelect.value;
        var distIdMap = {'Amparai': '1', 'Ampara': '1', 'Batticaloa': '2', 'Trincomalee': '3'};
        var activeDistId = distIdMap[district] || '';

        var options = rangeSelect.querySelectorAll('option');
        options.forEach(function(opt) {
            if (!opt.value) {
                opt.style.display = '';
                return;
            }
            if (activeDistId && opt.getAttribute('data-district-id') === activeDistId) {
                opt.style.display = '';
            } else if (!activeDistId) {
                opt.style.display = '';
            } else {
                opt.style.display = 'none';
            }
        });
    }

    function toggleContextualFields() {
        var role = roleSelect.value;
        var isFarmRole = (role === 'farms_dd');
        var isTrainingOfficer = (role === 'training_officer');
        var isDistrictDD = (role === 'district_dd');
        var isHQRole = (role === 'deputy_director_hq_1' || role === 'deputy_director_hq_2' || role === 'provincial_director');
        var isRangeRole = rangeRoles.includes(role);

        // Dynamic Form Logic for District selection
        if (isDistrictDD || isRangeRole) {
            districtContainer.style.display = 'block';
            districtSelect.setAttribute('required', 'required');
            if (provincialOption) provincialOption.style.display = 'none';
            if (districtSelect.value === 'Provincial' || !districtSelect.value) {
                districtSelect.value = 'Amparai';
            }
        } else if (isHQRole) {
            // Deputy Director H/Q1 & H/Q2: District dropdown is hidden (province-wide)
            districtContainer.style.display = 'none';
            districtSelect.removeAttribute('required');
            districtSelect.value = 'Provincial';
        } else {
            districtContainer.style.display = 'block';
            districtSelect.setAttribute('required', 'required');
            if (provincialOption) provincialOption.style.display = '';
        }

        if (isRangeRole) {
            rangeContainer.style.display = 'block';
            filterRangesByDistrict();
        } else {
            rangeContainer.style.display = 'none';
            rangeSelect.value = '';
        }

        if (isFarmRole) {
            farmContainer.style.display = 'block';
            farmSelect.setAttribute('required', 'required');
        } else {
            farmContainer.style.display = 'none';
            farmSelect.removeAttribute('required');
            farmSelect.value = '';
        }

        if (isTrainingOfficer) {
            trainingCenterLocationContainer.style.display = 'block';
            trainingCenterLocationSelect.setAttribute('required', 'required');
            trainingCenterContainer.style.display = 'block';
            trainingCenterSelect.setAttribute('required', 'required');
        } else {
            trainingCenterLocationContainer.style.display = 'none';
            trainingCenterLocationSelect.removeAttribute('required');
            trainingCenterLocationSelect.value = '';
            trainingCenterContainer.style.display = 'none';
            trainingCenterSelect.removeAttribute('required');
            trainingCenterSelect.value = '';
        }
    }

    trainingCenterSelect.addEventListener('change', function() {
        var selected = this.options[this.selectedIndex];
        if (selected && selected.dataset.location) {
            trainingCenterLocationSelect.value = selected.dataset.location;
        }
    });

    districtSelect.addEventListener('change', function() {
        if (rangeRoles.includes(roleSelect.value)) {
            filterRangesByDistrict();
        }
    });

    roleSelect.addEventListener('change', toggleContextualFields);
    toggleContextualFields();
});
</script>
</body>
</html>
