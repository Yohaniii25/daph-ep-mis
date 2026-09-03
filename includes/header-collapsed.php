<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/notification_helper.php';

// Notification data for header bell
$header_user_id = intval($_SESSION['user_id'] ?? 0);
$header_unread_count = get_unread_notification_count($mysqli, $header_user_id);
$header_notifications = get_user_notifications($mysqli, $header_user_id, 7);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DAPH - EP MIS</title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../assets/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="../assets/css/buttons.bootstrap5.min.css" rel="stylesheet">
    <link href="../assets/css/sweetalert2.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="../assets/css/sms.css" rel="stylesheet">
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
                background: rgba(0,0,0,0.5);
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

            <!-- Notifications Dropdown -->
            <div class="dropdown me-4" id="notificationDropdownContainer">
                <a class="text-dark position-relative d-inline-block text-decoration-none" href="#" id="notificationBellDropdown" data-bs-toggle="dropdown" aria-expanded="false" title="Notifications">
                    <i class="bi bi-bell fs-4"></i>
                    <span id="notificationBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger <?= $header_unread_count > 0 ? '' : 'd-none' ?>" style="font-size: 0.65rem;">
                        <?= $header_unread_count > 99 ? '99+' : $header_unread_count ?>
                    </span>
                </a>
                <div class="dropdown-menu dropdown-menu-end shadow-lg border-0 py-0 mt-2" aria-labelledby="notificationBellDropdown" style="width: 360px; border-radius: 12px; overflow: hidden; z-index: 1055;">
                    <div class="p-3 text-white d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #500707 0%, #750d0d 100%);">
                        <div>
                            <span class="fw-bold fs-6"><i class="bi bi-bell-fill me-2"></i>Notifications</span>
                            <span id="notificationHeaderBadge" class="badge bg-light text-dark rounded-pill ms-2 <?= $header_unread_count > 0 ? '' : 'd-none' ?>"><?= $header_unread_count ?> New</span>
                        </div>
                        <?php if ($header_unread_count > 0): ?>
                            <button type="button" class="btn btn-sm btn-link text-white text-decoration-none p-0 mark-all-read-btn" style="font-size: 11px; opacity: 0.9;">
                                <i class="bi bi-check2-all me-1"></i>Mark all read
                            </button>
                        <?php endif; ?>
                    </div>
                    <div class="notification-list-container" style="max-height: 340px; overflow-y: auto;">
                        <?php if (empty($header_notifications)): ?>
                            <div class="text-center py-4 text-muted" id="notificationEmptyState">
                                <i class="bi bi-bell-slash fs-2 d-block mb-2 text-secondary opacity-50"></i>
                                <span class="small">No notifications yet</span>
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush" id="notificationListGroup">
                                <?php foreach ($header_notifications as $notif): ?>
                                    <a href="<?= !empty($notif['link']) ? '../' . ltrim($notif['link'], '/') : '#' ?>" 
                                       class="list-group-item list-group-item-action p-3 border-bottom notification-item <?= empty($notif['is_read']) ? 'bg-light fw-medium' : '' ?>"
                                       data-id="<?= $notif['id'] ?>" style="transition: background 0.2s;">
                                        <div class="d-flex align-items-start">
                                            <div class="me-3 mt-1">
                                                <?php if (strpos(strtolower($notif['title']), 'add') !== false): ?>
                                                    <div class="rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                                                        <i class="bi bi-person-plus-fill"></i>
                                                    </div>
                                                <?php elseif (strpos(strtolower($notif['title']), 'remov') !== false): ?>
                                                    <div class="rounded-circle bg-danger-subtle text-danger d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                                                        <i class="bi bi-person-x-fill"></i>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="rounded-circle bg-info-subtle text-info d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                                                        <i class="bi bi-info-circle-fill"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <span class="fw-bold small text-dark"><?= htmlspecialchars($notif['title']) ?></span>
                                                    <small class="text-muted" style="font-size: 10px;"><?= htmlspecialchars($notif['time_ago']) ?></small>
                                                </div>
                                                <p class="mb-0 text-muted small lh-sm" style="font-size: 12px;"><?= htmlspecialchars($notif['message']) ?></p>
                                            </div>
                                            <?php if (empty($notif['is_read'])): ?>
                                                <span class="ms-2 p-1 bg-danger rounded-circle align-self-center notif-unread-dot" style="width: 6px; height: 6px;" title="Unread"></span>
                                            <?php endif; ?>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="p-2 bg-light text-center border-top">
                        <a href="../pages/modules/hr/employee_managment.php" class="text-decoration-none small text-secondary fw-semibold">
                            <i class="bi bi-people me-1"></i>View Employee Management
                        </a>
                    </div>
                </div>
            </div>

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
        document.getElementById('sidebarToggle').addEventListener('click', function () {
            const sidebar = document.getElementById('layoutSidenav_nav');
            const topbar = document.querySelector('.top-bar');
            const content = document.getElementById('layoutSidenav_content');

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
                    overlay.onclick = function () {
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