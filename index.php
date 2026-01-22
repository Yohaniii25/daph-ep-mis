<?php

// If ALREADY logged in → go straight to dashboard (no loop)
if (isset($_SESSION['user_id']) && $_SESSION['logged_in']) {
    header("Location: dashboard.php");
    exit();
}

$login_error = $_SESSION['login_error'] ?? '';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DAPH Eastern Province | Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="./assets/css/style.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="assets/img/favicon.png"> 

</head>
<body class="gov-login">

<div class="login-wrapper">

    <!-- Logo -->
    <div class="text-center mb-4">
        <img src="assets/img/logo.png" alt="DAPH Logo" class="logo-img">
    </div>

    <!-- Login Box -->
    <div class="login-box">

        <?php if ($login_error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= htmlspecialchars($login_error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form action="auth/login_process.php" method="POST" autocomplete="off">
            <div class="mb-4">
                <input type="text" name="login_id" class="form-control" 
                       placeholder="Username or Email" required autofocus>
            </div>

            <div class="mb-4">
                <input type="password" name="password" class="form-control" 
                       placeholder="Password" required>
            </div>

            <button type="submit" class="btn btn-login w-100">
                Log In
            </button>
        </form>
    </div>

    <!-- Footer -->
    <div class="login-footer">
        © 2025 Copyright SLTDIGITAL | All Rights Reserved
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>