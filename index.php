<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Department of Animal Production & Health - Eastern Province</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>

    </style>
</head>
<body class="gov-login">

<div class="container-fluid px-0">


    <!-- Main Login Area -->
    <div class="login-wrapper">
        
        <!-- Department Header with Logos -->
        <div class="text-center mb-5">
        
            <img src="assets/img/logo.png" alt="DAPH Logo" height="90" class="ms-4">
        </div>

        <!-- Login Form -->
        <div class="login-box">
            <form action="auth/login_process.php" method="POST">
                <div class="mb-3">
                    <input type="text" name="username" class="form-control form-control-lg" 
                           placeholder="Username" required autofocus>
                </div>
                <div class="mb-4">
                    <input type="password" name="password" class="form-control form-control-lg" 
                           placeholder="Password" required>
                </div>
                <button type="submit" class="btn-login w-100">
                    Log In
                </button>
            </form>
        </div>

        <!-- Footer -->
        <div class="login-footer">
            © 2025 Copyright SLTDIGITAL | All Rights Reserved
        </div>
    </div>
</div>

</body>
</html>