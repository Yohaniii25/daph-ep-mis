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
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <?php
    $farm_css_path = 'assets/css/farm.css';
    if (!file_exists($farm_css_path)) {
        if (file_exists('../assets/css/farm.css')) {
            $farm_css_path = '../assets/css/farm.css';
        } elseif (file_exists('../../assets/css/farm.css')) {
            $farm_css_path = '../../assets/css/farm.css';
        } else {
            $farm_css_path = '../../../assets/css/farm.css';
        }
    }
    ?>
    <link href="<?= $farm_css_path ?>" rel="stylesheet">
    <link rel="icon" type="image/png" href="https://sltdigital.site/daph-ep-mis/assets/img/favicon.png">
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
            padding-top: 50px;
            background: #f8f9fa;
            transition: margin-left 0.3s ease;
        }

        #layoutSidenav_content.collapsed {
            margin-left: 70px;
        }

        .search-bar {
            width: 450px;
            border-radius: 30px;
            border: 1px solid #ddd;
            padding: 10px 20px;
            font-size: 16px;
        }

        .content-wrapper {
            margin-left: 260px;
            /* padding-top: 70px; */
            background: #f8f9fa;
            transition: margin-left 0.3s ease;
        }

        .content-wrapper.collapsed {
            margin-left: 70px;
        }

        /* Mobile */
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
        <!-- <button class="btn btn-link text-dark p-0 me-3" id="sidebarToggle" style="font-size: 1.8rem;">
            <i class="bi bi-list"></i>
        </button> -->

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
                        <div class="fw-bold"><?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User') ?></div>
                        <small><?= ucwords(str_replace('_', ' ', $_SESSION['role'])) ?></small>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <!-- Layout -->
    <div id="layoutSidenav">
        <?php require_once __DIR__ . '/sidebar.php'; ?>

        <div id="layoutSidenav_content">
            <main class="container-fluid px-4">
                <!-- Page content here -->
            </main>
        </div>
    </div>

    <!-- Bootstrap Bundle + Toggle Script -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            const sidebar = document.getElementById('layoutSidenav_nav');
            const topbar = document.querySelector('.top-bar');
            const content = document.getElementById('layoutSidbienav_content');

            if (window.innerWidth > 991) {
                sidebar.classList.toggle('collapsed');
                topbar.classList.toggle('collapsed');
                content.classList.toggle('collapsed');
            } else {
                sidebar.classList.toggle('open');

                let overlay = document.querySelector('.sidebar-overlay');
                if (!overlay) {
                    overlay = document.createElement('div');
                    overlay.className = 'sidebar-overlay';
                    overlay.onclick = function() {
                        sidebar.classList.remove('open');
                        overlay.classList.remove('open');
                    };
                    document.body.appendChild(overlay);
                }
                overlay.classList.toggle('open');
            }
        });
    </script>

</body>

</html>