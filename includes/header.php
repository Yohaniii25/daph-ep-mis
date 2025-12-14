<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DAPH Eastern Province MIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        /* Critical Fix: Make sidebar fixed full height */
        html, body {
            height: 100%;
            margin: 0;
            overflow-x: hidden;
        }
        #layoutSidenav {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }
        #layoutSidenav_nav {
            width: 260px;
            flex-shrink: 0;
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            z-index: 1030;
            overflow-y: auto;
        }
        #layoutSidenav_content {
            margin-left: 260px;
            width: calc(100% - 260px);
            min-height: 100vh;
            background: #f8f9fa;
        }
        .sb-topnav {
            height: 70px;
            z-index: 1031;
            position: fixed;
            top: 0;
            left: 260px;
            right: 0;
            background: #6B0F1A !important;
        }
        main {
            padding-top: 90px;
        }
        /* Hide scrollbar but keep functionality */
        #layoutSidenav_nav::-webkit-scrollbar { width: 6px; }
        #layoutSidenav_nav::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.3); border-radius: 3px; }
    </style>
</head>
<body>

<!-- Top Navbar (Fixed) -->
<nav class="sb-topnav navbar navbar-expand navbar-dark">
    <button class="btn btn-link text-white ms-4" id="sidebarToggle">
        <i class="bi bi-list fs-4"></i>
    </button>

    <form class="d-none d-md-flex ms-auto me-4">
        <input class="form-control bg-dark text-white border-0" type="search" placeholder="Search..." style="width:300px;">
    </form>

    <ul class="navbar-nav ms-auto me-4">
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle text-white" href="#" data-bs-toggle="dropdown">
                <i class="bi bi-bell fs-4"></i>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">3</span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="#">New disease alert</a></li>
                <li><a class="dropdown-item" href="#">Vehicle request pending</a></li>
            </ul>
        </li>

        <li class="nav-item dropdown ms-3">
            <a class="nav-link dropdown-toggle text-white d-flex align-items-center" href="#" data-bs-toggle="dropdown">
                <img src="../assets/img/user.jpg" class="rounded-circle me-2" width="40" height="40">
                <div class="text-start">
                    <div class="fw-bold"><?= htmlspecialchars($_SESSION['full_name']) ?></div>
                    <small><?= ucwords(str_replace('_', ' ', $_SESSION['role'])) ?></small>
                </div>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="../pages/profile.php">Profile</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="../logout.php">Logout</a></li>
            </ul>
        </li>
    </ul>
</nav>

<!-- Main Layout -->
<div id="layoutSidenav">
    <?php require_once __DIR__ . '/sidebar.php'; ?>
    <div id="layoutSidenav_content">
        <!-- Content goes here -->