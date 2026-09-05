            </main>
        </div>
    </div>

    <!-- Core JavaScript Dependencies -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.0/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.0/vfs_fonts.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

    <?php
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
    <!-- Chart.js Library -->
    <script src="<?= $rel_path ?>assets/js/chart.min.js"></script>

    <!-- Dedicated Module JS Files -->
    <script src="<?= $rel_path ?>assets/js/farm.js"></script>
    <script src="<?= $rel_path ?>assets/js/veterinary.js"></script>

    <script>
        // Sidebar Toggle Script
        const sidebarToggleBtn = document.getElementById('sidebarToggle');
        if (sidebarToggleBtn) {
            sidebarToggleBtn.addEventListener('click', function() {
                const sidebar = document.getElementById('layoutSidenav_nav');
                const topbar = document.querySelector('.top-bar');
                const content = document.getElementById('layoutSidenav_content');

                if (window.innerWidth > 991) {
                    if (sidebar) sidebar.classList.toggle('collapsed');
                    if (topbar) topbar.classList.toggle('collapsed');
                    if (content) content.classList.toggle('collapsed');
                } else {
                    if (sidebar) sidebar.classList.toggle('open');

                    let overlay = document.querySelector('.sidebar-overlay');
                    if (!overlay) {
                        overlay = document.createElement('div');
                        overlay.className = 'sidebar-overlay';
                        overlay.onclick = function() {
                            if (sidebar) sidebar.classList.remove('open');
                            overlay.classList.remove('open');
                        };
                        document.body.appendChild(overlay);
                    }
                    overlay.classList.toggle('open');
                }
            });
        }

        // Logout Confirmation Script (SweetAlert2)
        const logoutLink = document.getElementById('logout-link');
        if (logoutLink) {
            logoutLink.addEventListener('click', function(e) {
                e.preventDefault();
                const targetUrl = this.getAttribute('href');
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Are you sure you want to log out?',
                        text: 'Your current active session will be ended.',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#500707', // Matching sidebar highlight color
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Yes, Log out',
                        cancelButtonText: 'Cancel',
                        focusCancel: true
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = targetUrl;
                        }
                    });
                } else {
                    if (confirm('Are you sure you want to log out?')) {
                        window.location.href = targetUrl;
                    }
                }
            });
        }

        // Notification Interactions (Dropdown, Modal, Mark Read)
        const notifApiUrl = '<?= $rel_path ?>includes/notifications_api.php';
        let modalNotifsCache = [];
        let modalFilter = 'all';

        function syncNotificationBadges(unreadCount) {
            const count = parseInt(unreadCount) || 0;
            if (count > 0) {
                $('#notificationBadge').removeClass('d-none').text(count > 99 ? '99+' : count);
                $('#notificationHeaderBadge').removeClass('d-none').text(count + ' New');
                $('.modal-unread-counter').removeClass('d-none').text(count + ' Unread');
            } else {
                $('#notificationBadge').addClass('d-none').text('0');
                $('#notificationHeaderBadge').addClass('d-none').text('0 New');
                $('.modal-unread-counter').addClass('d-none');
                $('.notif-unread-dot').remove();
                $('.notification-item').removeClass('bg-light fw-medium');
                $('.mark-all-read-btn').fadeOut(200);
            }
        }

        $(document).on('click', '.mark-all-read-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const btn = $(this);
            $.ajax({
                url: notifApiUrl,
                type: 'POST',
                data: { action: 'mark_read' },
                dataType: 'json',
                success: function(resp) {
                    if (resp && resp.success) {
                        syncNotificationBadges(0);
                        if (modalNotifsCache.length) {
                            modalNotifsCache.forEach(n => n.is_read = 1);
                            renderModalNotifications();
                        }
                    }
                }
            });
        });

        $(document).on('click', '.notification-item', function(e) {
            const item = $(this);
            const notifId = item.data('id');
            if (notifId && item.find('.notif-unread-dot').length > 0) {
                $.ajax({
                    url: notifApiUrl,
                    type: 'POST',
                    data: { action: 'mark_read', id: notifId },
                    dataType: 'json',
                    success: function(resp) {
                        item.removeClass('bg-light fw-medium');
                        item.find('.notif-unread-dot').remove();
                        if (resp && typeof resp.unread_count !== 'undefined') {
                            syncNotificationBadges(resp.unread_count);
                        }
                    }
                });
            }
        });

        // Load & Render Modal Notifications
        function loadModalNotifications() {
            $('#modalNotifListGroup').html('<div class="text-center py-5 text-muted" id="modalNotifLoading"><div class="spinner-border spinner-border-sm text-danger me-2" role="status"></div><span>Loading alerts...</span></div>');
            $.ajax({
                url: notifApiUrl,
                type: 'GET',
                data: { action: 'fetch', limit: 80 },
                dataType: 'json',
                success: function(resp) {
                    if (resp && resp.success) {
                        modalNotifsCache = resp.notifications || [];
                        syncNotificationBadges(resp.unread_count);
                        renderModalNotifications();
                    } else {
                        $('#modalNotifListGroup').html('<div class="text-center py-4 text-muted small">Could not load notifications.</div>');
                    }
                },
                error: function() {
                    $('#modalNotifListGroup').html('<div class="text-center py-4 text-danger small">Network error loading notifications.</div>');
                }
            });
        }

        function renderModalNotifications() {
            const query = ($('#modalNotifSearch').val() || '').toLowerCase().trim();
            const container = $('#modalNotifListGroup');
            container.empty();

            let filtered = modalNotifsCache.filter(n => {
                const type = (n.type || '').toLowerCase();
                const isRead = parseInt(n.is_read) === 1;
                const text = ((n.title || '') + ' ' + (n.message || '')).toLowerCase();

                let matchCat = true;
                if (modalFilter === 'unread') matchCat = !isRead;
                else if (modalFilter === 'approvals') matchCat = type.includes('approval');
                else if (modalFilter === 'transfers') matchCat = type.includes('transfer');
                else if (modalFilter === 'roles') matchCat = type.includes('role');

                const matchQuery = !query || text.includes(query);
                return matchCat && matchQuery;
            });

            if (filtered.length === 0) {
                container.html('<div class="text-center py-5 text-muted"><i class="bi bi-bell-slash fs-2 d-block mb-2 text-secondary opacity-50"></i><span class="small">No alerts matching this filter</span></div>');
                return;
            }

            filtered.forEach(item => {
                const isRead = parseInt(item.is_read) === 1;
                const badgeClass = item.badge_class || 'bg-secondary';
                const badgeText = item.badge_text || 'Alert';
                const iconClass = item.icon_class || 'bi-bell';
                const link = item.link ? '<?= $rel_path ?>' + item.link.replace(/^\//, '') : '';

                const rowHtml = `
                    <div class="list-group-item p-3 border-bottom modal-notif-row ${!isRead ? 'bg-light-subtle border-start border-4 border-danger' : ''}" data-id="${item.id}">
                        <div class="d-flex align-items-start justify-content-between gap-3">
                            <div class="d-flex align-items-start gap-2.5">
                                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 mt-1" style="width: 36px; height: 36px; background: rgba(0,0,0,0.05);">
                                    <i class="bi ${iconClass} fs-6 text-danger"></i>
                                </div>
                                <div>
                                    <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                                        <span class="badge ${badgeClass} rounded-pill" style="font-size: 10px;">${badgeText}</span>
                                        <strong class="small text-dark">${item.title}</strong>
                                        ${!isRead ? '<span class="badge bg-danger rounded-pill py-0.5 px-1.5" style="font-size: 9px;">NEW</span>' : ''}
                                    </div>
                                    <p class="mb-1 text-muted small lh-sm" style="font-size: 12px;">${item.message}</p>
                                    <div class="text-muted" style="font-size: 11px;">
                                        <i class="bi bi-clock me-1"></i>${item.time_ago || ''} • ${item.formatted_date || ''} ${item.formatted_time || ''}
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-1.5 flex-shrink-0">
                                ${link ? `<a href="${link}" class="btn btn-sm btn-outline-danger py-0.5 px-2" style="font-size: 11px;">View</a>` : ''}
                                ${!isRead ? `<button type="button" class="btn btn-sm btn-light border py-0.5 px-2 modal-mark-read-btn" data-id="${item.id}" style="font-size: 11px;"><i class="bi bi-check2"></i></button>` : '<i class="bi bi-check2-circle text-success" title="Read"></i>'}
                            </div>
                        </div>
                    </div>
                `;
                container.append(rowHtml);
            });
        }

        const modalEl = document.getElementById('allNotificationsModal');
        if (modalEl) {
            modalEl.addEventListener('show.bs.modal', function() {
                loadModalNotifications();
            });
        }

        // Modal filter click
        $(document).on('click', '.modal-filter-btn', function() {
            $('.modal-filter-btn').removeClass('btn-danger active').addClass('btn-outline-secondary');
            $(this).removeClass('btn-outline-secondary').addClass('btn-danger active');
            modalFilter = $(this).data('filter') || 'all';
            renderModalNotifications();
        });

        $(document).on('input', '#modalNotifSearch', function() {
            renderModalNotifications();
        });

        // Mark read inside modal
        $(document).on('click', '.modal-mark-read-btn', function() {
            const btn = $(this);
            const notifId = btn.data('id');
            if (!notifId) return;

            $.ajax({
                url: notifApiUrl,
                type: 'POST',
                data: { action: 'mark_read', id: notifId },
                dataType: 'json',
                success: function(resp) {
                    if (resp && resp.success) {
                        const row = btn.closest('.modal-notif-row');
                        row.removeClass('bg-light-subtle border-start border-4 border-danger');
                        row.find('.badge.bg-danger').remove();
                        btn.replaceWith('<i class="bi bi-check2-circle text-success" title="Read"></i>');
                        const cached = modalNotifsCache.find(n => parseInt(n.id) === parseInt(notifId));
                        if (cached) cached.is_read = 1;
                        if (resp.unread_count !== undefined) {
                            syncNotificationBadges(resp.unread_count);
                        }
                    }
                }
            });
        });
    </script>
    <?php if (!empty($pageScripts)) echo $pageScripts; ?>
</body>

</html>