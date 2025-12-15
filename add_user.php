<?php
// add_user.php  →  Keep this file forever (protect it later)
require_once 'config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username  = trim($_POST['username']);
    $email     = trim($_POST['email']);
    $password  = $_POST['password'];                    // plain text
    $full_name = trim($_POST['full_name']);
    $role      = $_POST['role'];
    $district  = $_POST['district'];

    // Auto hash the password
    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $mysqli->prepare("INSERT INTO users 
        (username, email, password, full_name, role, district, status) 
        VALUES (?, ?, ?, ?, ?, ?, 'active')");

    $stmt->bind_param("ssssss", $username, $email, $hash, $full_name, $role, $district);

    if ($stmt->execute()) {
        echo '<div class="alert alert-success">User <b>' . htmlspecialchars($username) . 
             '</b> created successfully!<br>Password: <b>' . htmlspecialchars($password) . '</b></div>';
    } else {
        echo '<div class="alert alert-danger">Error: ' . $stmt->error . '</div>';
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
                            <option value="provincial_director">Provincial Director</option>
                            <option value="district_dd">District Deputy Director</option>
                            <option value="veterinary_surgeon">Veterinary Surgeon</option>
                            <option value="ldo">Livestock Development Officer</option>
                            <option value="sms">Subject Matter Specialist</option>
                            <option value="administrator">administrator</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label>District</label>
                        <select name="district" class="form-select" required>
                            <option>Provincial</option>
                            <option>Amparai</option>
                            <option>Batticaloa</option>
                            <option>Trincomalee</option>
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
</body>
</html>