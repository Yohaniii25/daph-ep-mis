<?php
// add_user.php  →  Keep this file forever (protect it later with .htaccess)
require_once 'config/db_connect.php';

// Fetch active farms for select dropdown
$farms = [];
$farms_res = $mysqli->query("SELECT id, farm_name, location FROM regional_farms WHERE is_active = 1 ORDER BY farm_name");
if ($farms_res) {
    while ($row = $farms_res->fetch_assoc()) {
        $farms[] = $row;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username   = trim($_POST['username']);
    $email      = trim($_POST['email']);
    $password   = $_POST['password'];                    // plain text
    $full_name  = trim($_POST['full_name']);
    $role       = $_POST['role'];
    $district   = $_POST['district'];

    $farm_id = null;
    $validation_failed = false;

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

        // FIXED - declare variable first
        $is_active = 1;

        // === FIXED INSERT (matches your actual users table including farm_id) ===
        $stmt = $mysqli->prepare("INSERT INTO users 
            (username, email, password, full_name, role, district, farm_id, is_active) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->bind_param("ssssssii", $username, $email, $hash, $full_name, $role, $district, $farm_id, $is_active);

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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
                        <select name="role" class="form-select" required>
                            <option value="veterinary_surgeon">Veterinary Surgeon</option>
                            <option value="district_dd">District Deputy Director</option>
                            <option value="provincial_director">Provincial Director</option>
                            <option value="training_officer">Training Officer</option>
                            <option value="sms">Subject Matter Specialist</option>
                            <option value="administrator">Administrator</option>
                            <option value="finance_admin">Finance Admin</option>
                            <option value="planning_officer">Planning Officer</option>
                            <option value="farms_dd">Deputy Director (Farms Operation)</option>
                            <option value="employee">Employee</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label>District</label>
                        <select name="district" class="form-select" required>
                            <option value="Amparai">Amparai</option>
                            <option value="Batticaloa">Batticaloa</option>
                            <option value="Trincomalee">Trincomalee</option>
                            <option value="Provincial">Provincial</option>
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
    var roleSelect = document.querySelector('select[name="role"]');
    var farmContainer = document.getElementById('farm_container');
    var farmSelect = document.getElementById('farm_id');

    function toggleFarmField() {
        if (roleSelect.value === 'farms_dd') {
            farmContainer.style.display = 'block';
            farmSelect.setAttribute('required', 'required');
        } else {
            farmContainer.style.display = 'none';
            farmSelect.removeAttribute('required');
            farmSelect.value = '';
        }
    }

    roleSelect.addEventListener('change', toggleFarmField);
    toggleFarmField(); // run once on load
});
</script>
</body>
</html>
