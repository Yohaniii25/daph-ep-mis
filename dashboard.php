<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Provincial Director Dashboard - DAPH Eastern Province</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/pd-dashboard.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container-fluid">
    <div class="row">

        <!-- Sidebar -->
        <div class="col-md-2 sidebar text-white">
            <div class="text-center py-4">
                <img src="assets/img/logo.png" height="70" alt="Logo">
            </div>
            <ul class="nav flex-column px-3">
                <li class="nav-item active"><a href="#" class="nav-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-book"></i> Diary Management</a></li>
                <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-car"></i> Vehicle Management</a></li>
                <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-file-alt"></i> Reports</a></li>
                <li class="nav-item mt-5"><a href="#" class="nav-link"><i class="fas fa-cog"></i> Settings</a></li>
                <li class="nav-item"><a href="auth/logout.php" class="nav-link text-danger"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="col-md-10 main-content">

            <!-- Top Bar -->
            <div class="topbar d-flex justify-content-between align-items-center p-3 bg-white shadow-sm">
                <div class="search-bar">
                    <input type="text" class="form-control rounded-pill" placeholder="Search">
                </div>
                <div class="d-flex align-items-center">
                    <span class="me-3"><i class="fas fa-bell text-danger"></i></span>
                    <select class="form-select me-3" style="width:auto;">
                        <option>English</option>
                        <option>සිංහල</option>
                        <option>தமிழ்</option>
                    </select>
                    <div class="d-flex align-items-center">
                        <img src="assets/img/user.jpg" class="rounded-circle me-2" height="40" alt="User">
                        <div>
                            <small class="d-block text-muted">Provincial Director</small>
                            <strong>M.A. Perera</strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Page Title -->
            <div class="p-4">
                <h2 class="text-maroon fw-bold">Provincial Director Dashboard</h2>

                <!-- Stats Cards -->
                <div class="row mt-4">
                    <div class="col-md-3">
                        <div class="stat-card bg-white p-4 rounded shadow-sm">
                            <h5>Total Development Projects</h5>
                            <h2 class="text-danger">24</h2>
                            <small class="text-success"><i class="fas fa-arrow-up"></i> 8.5% Up from yesterday</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card bg-white p-4 rounded shadow-sm">
                            <h5>Total Animals Treated</h5>
                            <h2 class="text-primary">2150 <small class="text-muted">Animals</small></h2>
                            <small class="text-success"><i class="fas fa-arrow-up"></i> 1.3% Up from past week</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card bg-white p-4 rounded shadow-sm">
                            <h5>Vehicle Requests Processed</h5>
                            <h2 class="text-warning">320</h2>
                            <small class="text-danger"><i class="fas fa-arrow-down"></i> 4.3% Down from yesterday</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card bg-white p-4 rounded shadow-sm">
                            <h5>Officers Active</h5>
                            <h2 class="text-success">12</h2>
                            <small class="text-success"><i class="fas fa-arrow-up"></i> 1.8% Up from yesterday</small>
                        </div>
                    </div>
                </div>

                <!-- Project Progress Chart -->
                <div class="mt-5 bg-white p-4 rounded shadow-sm">
                    <h4 class="mb-4">Project Progress</h4>
                    <div class="chart-container">
                        <img src="assets/img/chart.png" alt="Project Progress Chart" class="img-fluid">
                    </div>
                    <div class="text-center mt-3">
                        <span class="legend-pending"></span> Pending 
                        <span class="legend-rejected"></span> Rejected 
                        <span class="legend-approved"></span> Approved
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>