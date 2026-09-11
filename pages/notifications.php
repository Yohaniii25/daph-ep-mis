<?php
/**
 * pages/notifications.php
 * Central Dedicated Notification View Model & History Hub
 */

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/notification_helper.php';

$current_user_id = intval($_SESSION['user_id'] ?? 0);
$type_counts = get_notification_type_counts($mysqli, $current_user_id);
$initial_notifications = get_filtered_notifications($mysqli, $current_user_id, 'all', false, 1000);
?>

<div class="container-fluid px-0 py-2">
    <!-- Breadcrumb & Page Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 border-bottom pb-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="<?= $rel_path ?>dashboard.php" class="text-decoration-none text-muted">Dashboard</a></li>
                    <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Notifications Center</li>
                </ol>
            </nav>
            <h3 class="fw-bold mb-0 d-flex align-items-center gap-2">
                <i class="bi bi-bell-fill text-danger"></i> System Alerts & Notifications
                <span class="badge bg-danger rounded-pill fs-6 notif-main-unread-badge <?= $type_counts['unread'] > 0 ? '' : 'd-none' ?>">
                    <?= $type_counts['unread'] ?> Unread
                </span>
            </h3>
            <p class="text-muted small mb-0">Central hub for approvals, inter-unit transfer alerts, role assignments, and departmental actions</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1" id="refreshNotifsBtn" title="Refresh Notifications">
                <i class="bi bi-arrow-clockwise"></i> Refresh
            </button>
            <button type="button" class="btn btn-danger btn-sm d-flex align-items-center gap-1 mark-all-read-full-btn <?= $type_counts['unread'] > 0 ? '' : 'disabled' ?>">
                <i class="bi bi-check2-all"></i> Mark All as Read
            </button>
            <button type="button" class="btn btn-outline-danger btn-sm d-flex align-items-center gap-1 clear-all-read-btn" title="Delete all read notifications">
                <i class="bi bi-trash"></i> Clear Read
            </button>
        </div>
    </div>

    <!-- Quick Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm rounded-3 p-3 h-100 bg-white border-start border-4 border-secondary">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold" style="font-size: 11px;">Total Alerts</div>
                        <h4 class="fw-bold mb-0 text-dark" id="statTotal"><?= $type_counts['total'] ?></h4>
                    </div>
                    <div class="rounded-circle bg-light text-secondary d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                        <i class="bi bi-bell fs-5"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm rounded-3 p-3 h-100 bg-white border-start border-4 border-danger">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold" style="font-size: 11px;">Unread</div>
                        <h4 class="fw-bold mb-0 text-danger" id="statUnread"><?= $type_counts['unread'] ?></h4>
                    </div>
                    <div class="rounded-circle bg-danger-subtle text-danger d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                        <i class="bi bi-envelope-exclamation fs-5"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm rounded-3 p-3 h-100 bg-white border-start border-4 border-warning">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold" style="font-size: 11px;">Approvals</div>
                        <h4 class="fw-bold mb-0 text-warning text-dark" id="statApprovals"><?= $type_counts['approvals'] ?></h4>
                    </div>
                    <div class="rounded-circle bg-warning-subtle text-warning d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                        <i class="bi bi-shield-check fs-5"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm rounded-3 p-3 h-100 bg-white border-start border-4 border-info">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold" style="font-size: 11px;">Transfers</div>
                        <h4 class="fw-bold mb-0 text-info" id="statTransfers"><?= $type_counts['transfers'] ?></h4>
                    </div>
                    <div class="rounded-circle bg-info-subtle text-info d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                        <i class="bi bi-arrow-left-right fs-5"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm rounded-3 p-3 h-100 bg-white border-start border-4 border-primary">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold" style="font-size: 11px;">Role Grants</div>
                        <h4 class="fw-bold mb-0 text-primary" id="statRoles"><?= $type_counts['roles'] ?></h4>
                    </div>
                    <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                        <i class="bi bi-person-badge fs-5"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card border-0 shadow-sm rounded-3 p-3 h-100 bg-white border-start border-4 border-success">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small text-uppercase fw-semibold" style="font-size: 11px;">Officer Updates</div>
                        <h4 class="fw-bold mb-0 text-success" id="statOfficers"><?= $type_counts['officers'] ?></h4>
                    </div>
                    <div class="rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                        <i class="bi bi-person-lines-fill fs-5"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Notification Controls & Filters -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-3">
            <div class="row g-3 align-items-center justify-content-between">
                <!-- Filter Pills -->
                <div class="col-12 col-lg-8">
                    <ul class="nav nav-pills gap-1" id="notifFilterTabs">
                        <li class="nav-item">
                            <button class="nav-link active py-1 px-3 rounded-pill filter-btn" data-filter="all">
                                All Alerts <span class="badge bg-secondary rounded-pill ms-1" id="pillAll"><?= $type_counts['total'] ?></span>
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link py-1 px-3 rounded-pill filter-btn" data-filter="unread">
                                Unread <span class="badge bg-danger rounded-pill ms-1" id="pillUnread"><?= $type_counts['unread'] ?></span>
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link py-1 px-3 rounded-pill filter-btn" data-filter="approvals">
                                Approvals <span class="badge bg-warning text-dark rounded-pill ms-1" id="pillApprovals"><?= $type_counts['approvals'] ?></span>
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link py-1 px-3 rounded-pill filter-btn" data-filter="transfers">
                                Transfers <span class="badge bg-info text-dark rounded-pill ms-1" id="pillTransfers"><?= $type_counts['transfers'] ?></span>
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link py-1 px-3 rounded-pill filter-btn" data-filter="roles">
                                Roles <span class="badge bg-primary rounded-pill ms-1" id="pillRoles"><?= $type_counts['roles'] ?></span>
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link py-1 px-3 rounded-pill filter-btn" data-filter="officers">
                                Officers <span class="badge bg-success rounded-pill ms-1" id="pillOfficers"><?= $type_counts['officers'] ?></span>
                            </button>
                        </li>
                    </ul>
                </div>
                <!-- Search Input -->
                <div class="col-12 col-lg-4">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" class="form-control border-start-0 ps-0" id="notifSearchInput" placeholder="Filter alerts by keyword...">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Notification Feed Container -->
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="list-group list-group-flush" id="notificationFeedList">
            <?php if (empty($initial_notifications)): ?>
                <div class="text-center py-5 text-muted" id="feedEmptyState">
                    <i class="bi bi-bell-slash text-secondary opacity-50" style="font-size: 3.5rem;"></i>
                    <h5 class="fw-bold mt-3 text-dark">No Notifications Found</h5>
                    <p class="small text-muted mb-0">You're all caught up! There are currently no notifications matching this criteria.</p>
                </div>
            <?php else: ?>
                <div class="text-center py-5 text-muted d-none" id="feedEmptyState">
                    <i class="bi bi-bell-slash text-secondary opacity-50" style="font-size: 3.5rem;"></i>
                    <h5 class="fw-bold mt-3 text-dark">No Notifications Found</h5>
                    <p class="small text-muted mb-0">There are currently no notifications matching this criteria.</p>
                </div>
                <?php foreach ($initial_notifications as $item): ?>
                    <div class="list-group-item p-3 notif-row-card <?= empty($item['is_read']) ? 'bg-light-subtle border-start border-4 border-danger' : '' ?>" 
                         data-id="<?= $item['id'] ?>" 
                         data-type="<?= htmlspecialchars($item['type'] ?? '') ?>"
                         data-read="<?= !empty($item['is_read']) ? '1' : '0' ?>"
                         style="transition: background-color 0.2s ease;">
                        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                            <div class="d-flex align-items-start gap-3">
                                <!-- Type Icon -->
                                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 mt-1" 
                                     style="width: 42px; height: 42px; background: rgba(0,0,0,0.04);">
                                    <i class="bi <?= $item['icon_class'] ?> fs-5 <?= str_replace('bg-', 'text-', explode(' ', $item['badge_class'])[0]) ?>"></i>
                                </div>
                                <!-- Content -->
                                <div>
                                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                        <span class="badge <?= $item['badge_class'] ?> rounded-pill" style="font-size: 11px;">
                                            <?= htmlspecialchars($item['badge_text']) ?>
                                        </span>
                                        <h6 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($item['title']) ?></h6>
                                        <?php if (empty($item['is_read'])): ?>
                                            <span class="badge bg-danger rounded-pill px-2 py-0.5 unread-badge-tag" style="font-size: 10px;">NEW</span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-secondary mb-1 notif-msg-text" style="font-size: 0.92rem;">
                                        <?= htmlspecialchars($item['message']) ?>
                                    </p>
                                    <div class="d-flex align-items-center gap-3 text-muted" style="font-size: 12px;">
                                        <span><i class="bi bi-clock me-1"></i><?= htmlspecialchars($item['time_ago']) ?></span>
                                        <span>•</span>
                                        <span><i class="bi bi-calendar3 me-1"></i><?= htmlspecialchars($item['formatted_date']) ?> at <?= htmlspecialchars($item['formatted_time']) ?></span>
                                    </div>
                                </div>
                            </div>
                            <!-- Actions -->
                            <div class="d-flex align-items-center gap-2 flex-shrink-0 ms-auto ms-md-0">
                                <button type="button" 
                                        class="btn btn-sm <?= empty($item['is_read']) ? 'btn-outline-success' : 'btn-outline-secondary' ?> single-toggle-read-btn d-inline-flex align-items-center gap-1" 
                                        data-id="<?= $item['id'] ?>" 
                                        data-read="<?= !empty($item['is_read']) ? '1' : '0' ?>"
                                        title="<?= empty($item['is_read']) ? 'Mark as read' : 'Mark as unread' ?>">
                                    <i class="bi <?= empty($item['is_read']) ? 'bi-check2' : 'bi-envelope' ?>"></i>
                                    <span class="btn-text"><?= empty($item['is_read']) ? 'Mark Read' : 'Mark Unread' ?></span>
                                </button>
                                <button type="button" 
                                        class="btn btn-sm btn-outline-danger single-delete-btn d-inline-flex align-items-center gap-1" 
                                        data-id="<?= $item['id'] ?>" 
                                        title="Delete notification">
                                    <i class="bi bi-trash"></i>
                                    <span class="btn-text">Delete</span>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Pagination Footer -->
        <div class="card-footer bg-white border-top py-3 px-3 d-flex flex-column flex-sm-row align-items-center justify-content-between gap-3" id="notifPaginationContainer">
            <div class="text-muted small" id="paginationInfo">
                Showing <span class="fw-semibold" id="pageStart">1</span> to <span class="fw-semibold" id="pageEnd">10</span> of <span class="fw-semibold" id="pageTotal">0</span> alerts
            </div>
            <nav aria-label="Notifications pagination" id="paginationNav">
                <ul class="pagination pagination-sm mb-0" id="paginationList">
                    <!-- Dynamic pagination items -->
                </ul>
            </nav>
        </div>
    </div>
</div>

<style>
.notif-row-card:hover {
    background-color: #f8fafc !important;
}
#notifFilterTabs .nav-link {
    color: #475569;
    font-size: 0.85rem;
    font-weight: 600;
    transition: all 0.2s ease;
    background: #f1f5f9;
}
#notifFilterTabs .nav-link.active {
    background-color: #500707 !important;
    color: #fff !important;
}
#notifFilterTabs .nav-link.active .badge {
    background-color: rgba(255,255,255,0.25) !important;
    color: #fff !important;
}
.pagination .page-item.active .page-link {
    background-color: #500707 !important;
    border-color: #500707 !important;
    color: #fff !important;
}
.pagination .page-link {
    color: #500707;
}
.pagination .page-link:hover {
    color: #3b0505;
    background-color: #f8fafc;
}
.single-delete-btn:hover {
    background-color: #dc3545;
    color: #fff;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const apiEndpoint = '<?= $rel_path ?>includes/notifications_api.php';
    const pageSize = 10;
    let currentPage = 1;
    let currentFilter = 'all';

    function renderPagination(totalItems) {
        const totalPages = Math.ceil(totalItems / pageSize) || 1;
        const paginationNav = document.getElementById('paginationNav');
        const paginationList = document.getElementById('paginationList');
        const paginationContainer = document.getElementById('notifPaginationContainer');
        const pageStart = document.getElementById('pageStart');
        const pageEnd = document.getElementById('pageEnd');
        const pageTotal = document.getElementById('pageTotal');

        if (pageTotal) pageTotal.textContent = totalItems;

        if (totalItems === 0) {
            if (paginationContainer) paginationContainer.style.display = 'none';
            return;
        }

        if (paginationContainer) paginationContainer.style.display = 'flex';

        const startIndex = (currentPage - 1) * pageSize;
        const endIndex = Math.min(startIndex + pageSize, totalItems);

        if (pageStart) pageStart.textContent = totalItems > 0 ? (startIndex + 1) : 0;
        if (pageEnd) pageEnd.textContent = endIndex;

        if (!paginationList) return;

        let html = '';

        // Previous button
        html += `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" data-page="${currentPage - 1}" aria-label="Previous">
                <i class="bi bi-chevron-left"></i> Prev
            </a>
        </li>`;

        if (totalPages <= 7) {
            for (let i = 1; i <= totalPages; i++) {
                html += `<li class="page-item ${i === currentPage ? 'active' : ''}">
                    <a class="page-link" href="#" data-page="${i}">${i}</a>
                </li>`;
            }
        } else {
            if (currentPage <= 4) {
                for (let i = 1; i <= 5; i++) {
                    html += `<li class="page-item ${i === currentPage ? 'active' : ''}">
                        <a class="page-link" href="#" data-page="${i}">${i}</a>
                    </li>`;
                }
                html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                html += `<li class="page-item ${totalPages === currentPage ? 'active' : ''}">
                    <a class="page-link" href="#" data-page="${totalPages}">${totalPages}</a>
                </li>`;
            } else if (currentPage >= totalPages - 3) {
                html += `<li class="page-item ${1 === currentPage ? 'active' : ''}">
                    <a class="page-link" href="#" data-page="1">1</a>
                </li>`;
                html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                for (let i = totalPages - 4; i <= totalPages; i++) {
                    html += `<li class="page-item ${i === currentPage ? 'active' : ''}">
                        <a class="page-link" href="#" data-page="${i}">${i}</a>
                    </li>`;
                }
            } else {
                html += `<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>`;
                html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                for (let i = currentPage - 1; i <= currentPage + 1; i++) {
                    html += `<li class="page-item ${i === currentPage ? 'active' : ''}">
                        <a class="page-link" href="#" data-page="${i}">${i}</a>
                    </li>`;
                }
                html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                html += `<li class="page-item"><a class="page-link" href="#" data-page="${totalPages}">${totalPages}</a></li>`;
            }
        }

        // Next button
        html += `<li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
            <a class="page-link" href="#" data-page="${currentPage + 1}" aria-label="Next">
                Next <i class="bi bi-chevron-right"></i>
            </a>
        </li>`;

        paginationList.innerHTML = html;

        // Hide page nav buttons if total items is 10 or fewer
        if (totalItems <= pageSize) {
            if (paginationNav) paginationNav.classList.add('d-none');
        } else {
            if (paginationNav) paginationNav.classList.remove('d-none');
        }
    }

    function filterCards(resetToPageOne = true) {
        if (resetToPageOne) {
            currentPage = 1;
        }

        const query = document.getElementById('notifSearchInput').value.toLowerCase().trim();
        const rows = Array.from(document.querySelectorAll('.notif-row-card'));
        const matchedRows = [];

        rows.forEach(row => {
            const type = (row.dataset.type || '').toLowerCase();
            const isRead = row.dataset.read === '1';
            const text = row.innerText.toLowerCase();

            let matchCategory = true;
            if (currentFilter === 'unread') {
                matchCategory = !isRead;
            } else if (currentFilter === 'approvals') {
                matchCategory = type.includes('approval');
            } else if (currentFilter === 'transfers') {
                matchCategory = type.includes('transfer');
            } else if (currentFilter === 'roles') {
                matchCategory = type.includes('role');
            } else if (currentFilter === 'officers') {
                matchCategory = type.includes('officer');
            }

            const matchQuery = !query || text.includes(query);

            if (matchCategory && matchQuery) {
                matchedRows.push(row);
            } else {
                row.style.display = 'none';
            }
        });

        const totalItems = matchedRows.length;
        const totalPages = Math.ceil(totalItems / pageSize) || 1;

        if (currentPage > totalPages) {
            currentPage = totalPages;
        }
        if (currentPage < 1) {
            currentPage = 1;
        }

        const startIndex = (currentPage - 1) * pageSize;
        const endIndex = startIndex + pageSize;

        matchedRows.forEach((row, idx) => {
            if (idx >= startIndex && idx < endIndex) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });

        const emptyState = document.getElementById('feedEmptyState');
        if (emptyState) {
            emptyState.classList.toggle('d-none', totalItems > 0);
            emptyState.style.display = (totalItems === 0) ? 'block' : 'none';
        }

        renderPagination(totalItems);
    }

    // Pagination link clicks
    $(document).on('click', '#paginationList .page-link', function(e) {
        e.preventDefault();
        const targetPage = parseInt($(this).data('page'));
        if (!isNaN(targetPage) && targetPage >= 1 && targetPage !== currentPage) {
            currentPage = targetPage;
            filterCards(false);
            const feedContainer = document.getElementById('notificationFeedList');
            if (feedContainer) {
                feedContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }
    });

    // Tab filter click
    document.querySelectorAll('#notifFilterTabs .filter-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('#notifFilterTabs .filter-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentFilter = this.dataset.filter;
            filterCards(true);
        });
    });

    // Search input
    document.getElementById('notifSearchInput').addEventListener('input', function() {
        filterCards(true);
    });

    // Refresh button
    document.getElementById('refreshNotifsBtn').addEventListener('click', function() {
        location.reload();
    });

    // Toggle single notification read / unread
    $(document).on('click', '.single-toggle-read-btn', function(e) {
        e.preventDefault();
        const btn = $(this);
        const notifId = btn.data('id');
        const card = btn.closest('.notif-row-card');
        if (!notifId) return;

        $.ajax({
            url: apiEndpoint,
            type: 'POST',
            data: { action: 'toggle_read', id: notifId },
            dataType: 'json',
            success: function(resp) {
                if (resp && resp.success) {
                    const isRead = parseInt(resp.is_read) === 1;
                    card.attr('data-read', isRead ? '1' : '0');
                    btn.attr('data-read', isRead ? '1' : '0');

                    if (isRead) {
                        card.removeClass('bg-light-subtle border-start border-4 border-danger');
                        card.find('.unread-badge-tag').remove();
                        btn.removeClass('btn-outline-success').addClass('btn-outline-secondary');
                        btn.attr('title', 'Mark as unread');
                        btn.html('<i class="bi bi-envelope"></i> <span class="btn-text">Mark Unread</span>');
                    } else {
                        card.addClass('bg-light-subtle border-start border-4 border-danger');
                        if (card.find('.unread-badge-tag').length === 0) {
                            card.find('.d-flex.flex-wrap.align-items-center.gap-2.mb-1').append('<span class="badge bg-danger rounded-pill px-2 py-0.5 unread-badge-tag" style="font-size: 10px;">NEW</span>');
                        }
                        btn.removeClass('btn-outline-secondary').addClass('btn-outline-success');
                        btn.attr('title', 'Mark as read');
                        btn.html('<i class="bi bi-check2"></i> <span class="btn-text">Mark Read</span>');
                    }

                    // Also sync topbar dropdown item if present
                    const ddItem = $(`#notificationListGroup .notification-item[data-id="${notifId}"]`);
                    if (ddItem.length) {
                        const ddBtn = ddItem.find('.dropdown-toggle-read-btn');
                        ddItem.attr('data-read', isRead ? '1' : '0');
                        ddBtn.attr('data-read', isRead ? '1' : '0');
                        if (isRead) {
                            ddItem.removeClass('bg-light fw-medium');
                            ddItem.find('.notif-unread-dot').remove();
                            ddBtn.attr('title', 'Mark as unread').css('color', '#198754').html('<i class="bi bi-envelope"></i>');
                        } else {
                            ddItem.addClass('bg-light fw-medium');
                            if (ddItem.find('.notif-unread-dot').length === 0) {
                                ddBtn.parent().append('<span class="p-1 bg-danger rounded-circle notif-unread-dot" style="width: 6px; height: 6px;" title="Unread"></span>');
                            }
                            ddBtn.attr('title', 'Mark as read').css('color', '#dc3545').html('<i class="bi bi-check2"></i>');
                        }
                    }

                    updateHeaderAndStats(resp.unread_count, resp.counts);
                    filterCards(false);
                }
            }
        });
    });

    // Delete single notification
    $(document).on('click', '.single-delete-btn', function(e) {
        e.preventDefault();
        const btn = $(this);
        const notifId = btn.data('id');
        const card = btn.closest('.notif-row-card');
        if (!notifId) return;

        const performDelete = () => {
            $.ajax({
                url: apiEndpoint,
                type: 'POST',
                data: { action: 'delete', id: notifId },
                dataType: 'json',
                success: function(resp) {
                    if (resp && resp.success) {
                        card.fadeOut(250, function() {
                            $(this).remove();
                            $(`#notificationListGroup .notification-item[data-id="${notifId}"]`).remove();
                            updateHeaderAndStats(resp.unread_count, resp.counts);
                            filterCards(false);
                        });

                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted',
                                text: 'Notification deleted successfully.',
                                timer: 1500,
                                showConfirmButton: false,
                                toast: true,
                                position: 'top-end'
                            });
                        }
                    } else {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire('Error', 'Failed to delete notification.', 'error');
                        } else {
                            alert('Failed to delete notification.');
                        }
                    }
                },
                error: function() {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire('Error', 'An error occurred while deleting the notification.', 'error');
                    } else {
                        alert('An error occurred while deleting the notification.');
                    }
                }
            });
        };

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Delete Alert?',
                text: 'Are you sure you want to permanently delete this notification?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    performDelete();
                }
            });
        } else {
            if (confirm('Are you sure you want to delete this notification?')) {
                performDelete();
            }
        }
    });

    // Clear all read notifications
    $('.clear-all-read-btn').on('click', function(e) {
        e.preventDefault();
        const readRows = $('.notif-row-card[data-read="1"]');
        if (readRows.length === 0) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'info',
                    title: 'No Read Notifications',
                    text: 'There are no read notifications to clear.',
                    timer: 2000,
                    showConfirmButton: false
                });
            } else {
                alert('No read notifications to clear.');
            }
            return;
        }

        const performClear = () => {
            $.ajax({
                url: apiEndpoint,
                type: 'POST',
                data: { action: 'delete_all_read' },
                dataType: 'json',
                success: function(resp) {
                    if (resp && resp.success) {
                        $('.notif-row-card[data-read="1"]').fadeOut(250, function() {
                            $(this).remove();
                            updateHeaderAndStats(resp.unread_count, resp.counts);
                            filterCards(true);
                        });

                        $('#notificationListGroup .notification-item[data-read="1"]').remove();

                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Read Alerts Cleared',
                                text: 'All read notifications have been removed.',
                                timer: 1800,
                                showConfirmButton: false
                            });
                        }
                    }
                }
            });
        };

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Clear All Read Alerts?',
                text: 'This will permanently remove all notifications that have been marked as read.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, clear them',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    performClear();
                }
            });
        } else {
            if (confirm('Are you sure you want to delete all read notifications?')) {
                performClear();
            }
        }
    });

    // Mark all as read
    $('.mark-all-read-full-btn').on('click', function(e) {
        e.preventDefault();
        const btn = $(this);
        if (btn.hasClass('disabled')) return;

        $.ajax({
            url: apiEndpoint,
            type: 'POST',
            data: { action: 'mark_read' },
            dataType: 'json',
            success: function(resp) {
                if (resp && resp.success) {
                    $('.notif-row-card').each(function() {
                        const card = $(this);
                        card.attr('data-read', '1');
                        card.removeClass('bg-light-subtle border-start border-4 border-danger');
                        card.find('.unread-badge-tag').remove();
                        card.find('.single-toggle-read-btn')
                            .removeClass('btn-outline-success')
                            .addClass('btn-outline-secondary')
                            .attr('data-read', '1')
                            .attr('title', 'Mark as unread')
                            .html('<i class="bi bi-envelope"></i> <span class="btn-text">Mark Unread</span>');
                    });
                    btn.addClass('disabled');
                    updateHeaderAndStats(resp.unread_count, resp.counts);
                    filterCards(false);
                }
            }
        });
    });

    function updateHeaderAndStats(unreadCount, counts) {
        // Topbar bell badges
        if (unreadCount > 0) {
            $('#notificationBadge').removeClass('d-none').text(unreadCount > 99 ? '99+' : unreadCount);
            $('#notificationHeaderBadge').removeClass('d-none').text(unreadCount + ' New');
            $('.notif-main-unread-badge').removeClass('d-none').text(unreadCount + ' Unread');
            $('.mark-all-read-full-btn').removeClass('disabled');
        } else {
            $('#notificationBadge').addClass('d-none').text('0');
            $('#notificationHeaderBadge').addClass('d-none').text('0 New');
            $('.notif-main-unread-badge').addClass('d-none');
            $('.mark-all-read-btn').fadeOut(200);
            $('.mark-all-read-full-btn').addClass('disabled');
        }

        // Stats cards & pill badges
        if (counts) {
            $('#statTotal').text(counts.total);
            $('#statUnread').text(counts.unread);
            $('#statApprovals').text(counts.approvals);
            $('#statTransfers').text(counts.transfers);
            $('#statRoles').text(counts.roles);
            $('#statOfficers').text(counts.officers);

            $('#pillAll').text(counts.total);
            $('#pillUnread').text(counts.unread);
            $('#pillApprovals').text(counts.approvals);
            $('#pillTransfers').text(counts.transfers);
            $('#pillRoles').text(counts.roles);
            $('#pillOfficers').text(counts.officers);
        }
    }

    // Initialize pagination on load
    filterCards(true);
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
