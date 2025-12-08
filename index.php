<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Department of Animal Production & Health - Eastern Province</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="login-body">

<div class="container">
    <div class="row justify-content-center align-items-center min-vh-100">
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card shadow-lg border-0">
                <div class="card-body text-center p-5">

                    <!-- Government Logo (Centered) -->
                    <img src="assets/images/logo.png" alt="Eastern Province Logo" class="img-fluid mb-4" style="max-height: 140px;">

                    <!-- Department Name (Trilingual) -->
                    <h4 class="mb-1">Department of Animal Production and Health</h4>
                    <h5 class="text-muted mb-4">
                        கால்நடை உற்பத்தி மற்றும் சுகாதார திணைக்களம்<br>
                        <small>පළාත් සත්ව නිෂ්පාදන හා සෞඛ්‍ය දෙපාර්තමේන්තුව - නැගෙනහිර පළාත</small>
                    </h5>

                    <!-- Login Form -->
                    <form action="auth/login_process.php" method="POST">
                        <div class="mb-3">
                            <input type="text" name="username" class="form-control form-control-lg" 
                                   placeholder="Username" required autofocus>
                        </div>
                        <div class="mb-4">
                            <input type="password" name="password" class="form-control form-control-lg" 
                                   placeholder="Password" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg w-100">
                            Login
                        </button>
                    </form>

                    <div class="mt-4 text-muted small">
                        © 2025 Eastern Province | Powered by ICTA Standards
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>