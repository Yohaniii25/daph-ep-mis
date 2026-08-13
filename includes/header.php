<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: " . (file_exists('index.php') ? 'index.php' : (file_exists('../index.php') ? '../index.php' : (file_exists('../../index.php') ? '../../index.php' : '../../../index.php'))));
    exit();
}

require_once __DIR__ . '/../config/constants.php';

// Calculate relative path from current page directory to project root
$project_root = realpath(__DIR__ . '/..');
$current_dir = realpath(getcwd() ?: __DIR__);
$rel_path = '';
if ($project_root && $current_dir) {
    $root_parts = array_values(array_filter(explode(DIRECTORY_SEPARATOR, $project_root)));
    $curr_parts = array_values(array_filter(explode(DIRECTORY_SEPARATOR, $current_dir)));
    $diff = count($curr_parts) - count($root_parts);
    if ($diff > 0) {
        $rel_path = str_repeat('../', $diff);
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= defined('SITE_SHORT_NAME') ? SITE_SHORT_NAME : 'DAPH - EP MIS' ?></title>

    <!-- Bootstrap 5 CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- DataTables & SweetAlert2 CSS -->
    <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">

    <!-- Dedicated Module CSS Files -->
    <link href="<?= $rel_path ?>assets/css/style.css" rel="stylesheet">
    <link href="<?= $rel_path ?>assets/css/farm.css" rel="stylesheet">
    <link href="<?= $rel_path ?>assets/css/veterinary.css" rel="stylesheet">

    <link rel="icon" type="image/png" href="<?= $rel_path ?>assets/img/favicon.png">

    <style>
        body {
            overflow-x: hidden;
        }

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
            transition: left 0.3s ease;
        }

        .top-bar.collapsed {
            left: 70px;
        }

        #layoutSidenav_nav {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 260px;
            z-index: 1030;
            transition: transform 0.3s ease;
            background: white;
            border-right: 1px solid #eee;
        }

        #layoutSidenav_nav.collapsed {
            transform: translateX(-190px);
        }

        #layoutSidenav_content {
            margin-left: 260px;
            padding-top: 70px;
            background: #f8f9fa;
            transition: margin-left 0.3s ease;
            min-height: 100vh;
        }

        #layoutSidenav_content.collapsed {
            margin-left: 70px;
        }

        #layoutSidenav_content #layoutSidenav_content {
            margin-left: 0 !important;
            padding-top: 0 !important;
            min-height: auto;
            background: transparent;
        }

        #layoutSidenav_content #layoutSidenav_content > main,
        #layoutSidenav_content > main {
            padding-top: 0.5rem;
        }

        .search-bar {
            width: 450px;
            border-radius: 30px;
            border: 1px solid #ddd;
            padding: 10px 20px;
            font-size: 16px;
        }

        /* Mobile Responsive */
        @media (max-width: 991.98px) {
            #layoutSidenav_nav {
                transform: translateX(-100%);
            }

            #layoutSidenav_nav.open {
                transform: translateX(0);
            }

            .top-bar {
                left: 0 !important;
            }

            #layoutSidenav_content {
                margin-left: 0 !important;
            }

            .sidebar-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                z-index: 1025;
                display: none;
            }

            .sidebar-overlay.open {
                display: block;
            }
        }
    </style>
</head>

<body>

    <!-- Top Bar -->
    <div class="top-bar d-flex align-items-center justify-content-between">
        <button class="btn btn-link text-dark p-0 me-3" id="sidebarToggle" style="font-size: 1.8rem;">
            <i class="bi bi-list"></i>
        </button>

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
                    <img src="<?= $rel_path ?>assets/img/user.jpg" class="rounded-circle me-2" width="40" height="40" alt="User">
                    <div>
                        <div class="fw-bold"><?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User') ?></div>
                        <small><?= ucwords(str_replace('_', ' ', $_SESSION['role'] ?? 'User')) ?></small>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <!-- Main Layout Container -->
    <div id="layoutSidenav">
        <?php require_once __DIR__ . '/sidebar.php'; ?>

        <div id="layoutSidenav_content">
            <main class="container-fluid px-4 py-3">