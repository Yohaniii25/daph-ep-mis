<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: " . (file_exists('index.php') ? 'index.php' : (file_exists('../index.php') ? '../index.php' : (file_exists('../../index.php') ? '../../index.php' : '../../../index.php'))));
    exit();
}

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/notification_helper.php';

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
    <title><?= defined('SITE_SHORT_NAME') ? SITE_SHORT_NAME : 'DAPH - EP MIS' ?></title>

    <!-- Bootstrap 5 CSS & Icons -->
    <link href="<?= $rel_path ?>assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= $rel_path ?>assets/css/bootstrap-icons.min.css" rel="stylesheet">

    <!-- DataTables & SweetAlert2 CSS -->
    <link href="<?= $rel_path ?>assets/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="<?= $rel_path ?>assets/css/buttons.bootstrap5.min.css" rel="stylesheet">
    <link href="<?= $rel_path ?>assets/css/sweetalert2.min.css" rel="stylesheet">

    <!-- Dedicated Module CSS Files -->
    <link href="<?= $rel_path ?>assets/css/style.css" rel="stylesheet">
    <link href="<?= $rel_path ?>assets/css/farm.css" rel="stylesheet">
    <link href="<?= $rel_path ?>assets/css/veterinary.css" rel="stylesheet">
    <link href="<?= $rel_path ?>assets/css/sms.css" rel="stylesheet">
    <link href="<?= $rel_path ?>assets/css/district.css" rel="stylesheet">

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

            <!-- Notifications Dropdown -->
            <div class="dropdown me-4" id="notificationDropdownContainer">
                <a class="text-dark position-relative d-inline-block text-decoration-none" href="#" id="notificationBellDropdown" data-bs-toggle="dropdown" aria-expanded="false" title="Notifications">
                    <i class="bi bi-bell fs-4"></i>
                    <span id="notificationBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger <?= $header_unread_count > 0 ? '' : 'd-none' ?>" style="font-size: 0.65rem;">
                        <?= $header_unread_count > 99 ? '99+' : $header_unread_count ?>
                    </span>
                </a>
                <div class="dropdown-menu dropdown-menu-end shadow-lg border-0 py-0 mt-2" aria-labelledby="notificationBellDropdown" style="width: 360px; border-radius: 12px; overflow: hidden; z-index: 1055;">
                    <div class="p-3 text-light d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #500707 0%, #750d0d 100%);">
                        <div>
                            <span class="fw-bold fs-6"><i class="bi bi-bell-fill me-2"></i>Notifications</span>
                            <span id="notificationHeaderBadge" class="badge bg-light text-dark rounded-pill ms-2 <?= $header_unread_count > 0 ? '' : 'd-none' ?>"><?= $header_unread_count ?> New</span>
                        </div>
                        <?php if ($header_unread_count > 0): ?>
                            <button type="button" class="btn btn-sm btn-link text-light text-decoration-none p-0 mark-all-read-btn" style="font-size: 11px; opacity: 0.9;">
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
                                    <a href="<?= !empty($notif['link']) ? $rel_path . ltrim($notif['link'], '/') : '#' ?>" 
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
                    <div class="p-2 bg-light border-top d-flex justify-content-between align-items-center px-3">
                        <button type="button" class="btn btn-link btn-sm text-decoration-none p-0 text-dark fw-semibold small" data-bs-toggle="modal" data-bs-target="#allNotificationsModal">
                            <i class="bi bi-window-stack me-1 text-danger"></i>Quick Modal
                        </button>
                        <a href="<?= $rel_path ?>pages/notifications.php" class="text-decoration-none small text-danger fw-bold">
                            View All Hub <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>

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

    <!-- Comprehensive All Notifications Modal -->
    <div class="modal fade" id="allNotificationsModal" tabindex="-1" aria-labelledby="allNotificationsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header text-light px-4 py-3" style="background: linear-gradient(135deg, #500707 0%, #750d0d 100%);">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-bell-fill fs-5"></i>
                        <h5 class="modal-title fw-bold" id="allNotificationsModalLabel">Notifications & System Alerts</h5>
                        <span class="badge bg-light text-dark rounded-pill ms-2 modal-unread-counter <?= $header_unread_count > 0 ? '' : 'd-none' ?>">
                            <?= $header_unread_count ?> Unread
                        </span>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <!-- Modal Filter Controls -->
                    <div class="p-3 bg-light border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div class="btn-group btn-group-sm" role="group" id="modalNotifFilterGroup">
                            <button type="button" class="btn btn-danger active modal-filter-btn" data-filter="all">All</button>
                            <button type="button" class="btn btn-outline-secondary modal-filter-btn" data-filter="unread">Unread</button>
                            <button type="button" class="btn btn-outline-secondary modal-filter-btn" data-filter="approvals">Approvals</button>
                            <button type="button" class="btn btn-outline-secondary modal-filter-btn" data-filter="transfers">Transfers</button>
                            <button type="button" class="btn btn-outline-secondary modal-filter-btn" data-filter="roles">Roles</button>
                        </div>
                        <div class="input-group input-group-sm" style="max-width: 250px;">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" class="form-control border-start-0 ps-0" id="modalNotifSearch" placeholder="Filter alerts...">
                        </div>
                    </div>
                    <!-- Modal Notification List -->
                    <div class="list-group list-group-flush" id="modalNotifListGroup" style="min-height: 250px; max-height: 480px; overflow-y: auto;">
                        <div class="text-center py-5 text-muted" id="modalNotifLoading">
                            <div class="spinner-border spinner-border-sm text-danger me-2" role="status"></div>
                            <span>Loading alerts...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light px-4 py-2.5 d-flex justify-content-between align-items-center">
                    <button type="button" class="btn btn-sm btn-outline-secondary mark-all-read-btn">
                        <i class="bi bi-check2-all me-1"></i>Mark All Read
                    </button>
                    <div class="d-flex align-items-center gap-2">
                        <a href="<?= $rel_path ?>pages/notifications.php" class="btn btn-sm btn-danger fw-semibold">
                            <i class="bi bi-box-arrow-up-right me-1"></i>Open Full Hub
                        </a>
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Layout Container -->
    <div id="layoutSidenav">
        <?php require_once __DIR__ . '/sidebar.php'; ?>


        <div id="layoutSidenav_content">
            <main class="container-fluid px-4 py-3">