<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

require_once __DIR__ . '/../config/constants.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DAPH - EP MIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>

        .top-bar {
            height: 70px;
            background: #fff;
            border-bottom: 1px solid #eee;
            position: fixed;
            top: 0;
            left: 260px;
            right: 0;
            z-index: 1020;
            display: flex;
            align-items: center;
            padding: 0 30px;
        }

        #layoutSidenav_nav {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 260px;
            z-index: 1030;
        }
        .search-bar {
            width: 450px;
            border-radius: 30px;
            border: 1px solid #ddd;
            padding: 10px 20px;
            font-size: 16px;
        }

        .nav-link.active {
            background: white !important;
            color: #6B0F1A !important;
            border-left: 4px solid #fff;
            font-weight: bold;
        }

        #layoutSidenav_content {
            margin-left: 130px;
            padding-top: 40px;
            min-height: 100vh;
            background: #fff;
        }

        .sb-topnav {
            left: 260px !important;
        }

        /* Active menu highlight */
        .nav-link.bg-danger {
            background: #5a0d15 !important;
            font-weight: bold;
        }

        /* Hover effect */
        .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
        }


    </style>
</head>

<body>

    <div class="top-bar">
        <input type="text" class="search-bar border-0" placeholder="Search">

        <div class="ms-auto d-flex align-items-center">
            <div class="dropdown me-3">
                <a class="text-dark dropdown-toggle d-flex align-items-center text-decoration-none" href="#" data-bs-toggle="dropdown">
                    English
                </a>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#">Sinhala</a></li>
                    <li><a class="dropdown-item" href="#">Tamil</a></li>
                </ul>
            </div>

            <a class="text-dark me-4 position-relative" href="#">
                <i class="bi bi-bell fs-4"></i>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">9+</span>
            </a>

            <div class="dropdown">
                <a class="d-flex align-items-center text-dark text-decoration-none" data-bs-toggle="dropdown">
                    <img src="../assets/img/user.jpg" class="rounded-circle me-2" width="40" height="40">
                    <div>
                        <div class="fw-bold"><?= htmlspecialchars($_SESSION['full_name']) ?></div>
                        <small><?= ucwords(str_replace('_', ' ', $_SESSION['role'])) ?></small>
                    </div>
                </a>

            </div>
        </div>
    </div>

    <div id="layoutSidenav">
        <?php require_once __DIR__ . '/sidebar.php'; ?>
        <div id="layoutSidenav_content">