<?php
/**
 * pages/notifications.php
 * Central Dedicated Notification View Model & History Hub
 */

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/notification_helper.php';

$current_user_id = intval($_SESSION['user_id'] ?? 0);
$type_counts = get_notification_type_counts($mysqli, $current_user_id);
$initial_notifications = get_filtered_notifications($mysqli, $current_user_id, 'all', false, 100);
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
                                <?php if (!empty($item['link']) && $item['link'] !== '#'): ?>
                                    <a href="<?= $rel_path . ltrim($item['link'], '/') ?>" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center gap-1 notif-link-btn" data-id="<?= $item['id'] ?>">
                                        <span>View Action</span> <i class="bi bi-arrow-right-short"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if (empty($item['is_read'])): ?>
                                    <button type="button" class="btn btn-sm btn-light border single-mark-read-btn text-muted" data-id="<?= $item['id'] ?>" title="Mark as read">
                                        <i class="bi bi-check2"></i> Mark Read
                                    </button>
                                <?php else: ?>
                                    <span class="text-muted small d-inline-flex align-items-center gap-1 pe-2">
                                        <i class="bi bi-check2-circle text-success"></i> Read
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
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
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const apiEndpoint = '<?= $rel_path ?>includes/notifications_api.php';
    let currentFilter = 'all';

    function filterCards() {
        const query = document.getElementById('notifSearchInput').value.toLowerCase().trim();
        const rows = document.querySelectorAll('.notif-row-card');
        let visibleCount = 0;

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
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        const emptyState = document.getElementById('feedEmptyState');
        if (emptyState) {
            emptyState.style.display = (visibleCount === 0) ? 'block' : 'none';
        }
    }

    // Tab filter click
    document.querySelectorAll('#notifFilterTabs .filter-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('#notifFilterTabs .filter-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentFilter = this.dataset.filter;
            filterCards();
        });
    });

    // Search input
    document.getElementById('notifSearchInput').addEventListener('input', filterCards);

    // Refresh button
    document.getElementById('refreshNotifsBtn').addEventListener('click', function() {
        location.reload();
    });

    // Mark single notification as read
    $(document).on('click', '.single-mark-read-btn, .notif-link-btn', function(e) {
        const notifId = $(this).data('id');
        const card = $(this).closest('.notif-row-card');
        if (!notifId || card.attr('data-read') === '1') return;

        $.ajax({
            url: apiEndpoint,
            type: 'POST',
            data: { action: 'mark_read', id: notifId },
            dataType: 'json',
            success: function(resp) {
                if (resp && resp.success) {
                    card.attr('data-read', '1');
                    card.removeClass('bg-light-subtle border-start border-4 border-danger');
                    card.find('.unread-badge-tag').remove();
                    card.find('.single-mark-read-btn').replaceWith('<span class="text-muted small d-inline-flex align-items-center gap-1 pe-2"><i class="bi bi-check2-circle text-success"></i> Read</span>');
                    updateHeaderAndStats(resp.unread_count, resp.counts);
                }
            }
        });
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
                        card.find('.single-mark-read-btn').replaceWith('<span class="text-muted small d-inline-flex align-items-center gap-1 pe-2"><i class="bi bi-check2-circle text-success"></i> Read</span>');
                    });
                    btn.addClass('disabled');
                    updateHeaderAndStats(resp.unread_count, resp.counts);
                    filterCards();
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
        } else {
            $('#notificationBadge').addClass('d-none').text('0');
            $('#notificationHeaderBadge').addClass('d-none').text('0 New');
            $('.notif-main-unread-badge').addClass('d-none');
            $('.mark-all-read-btn').fadeOut(200);
            $('.mark-all-read-full-btn').addClass('disabled');
        }

        // Stats cards
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
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
