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

    <!-- Global DataTables DOM Restructuring & Layout Standardization -->
    <script>
    (function($) {
        if (!$) return;

        // 1. Centralized DataTables Defaults
        if ($.fn && $.fn.dataTable) {
            $.extend(true, $.fn.dataTable.defaults, {
                dom: "<'row dt-controls-row-top align-items-center mb-3'<'col-12 col-md-6 d-flex align-items-center flex-wrap gap-2'lB><'col-12 col-md-6 d-flex justify-content-md-end align-items-center'f>>" +
                     "<'table-responsive dt-table-container'tr>" +
                     "<'row dt-controls-row-bottom align-items-center mt-3'<'col-12 col-md-5 d-flex align-items-center'i><'col-12 col-md-7 d-flex justify-content-md-end'p>>",
                language: {
                    search: "",
                    searchPlaceholder: "Search records...",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    infoEmpty: "Showing 0 to 0 of 0 entries",
                    infoFiltered: "(filtered from _MAX_ total entries)",
                    paginate: {
                        first: '<i class="bi bi-chevron-double-left"></i>',
                        previous: '<i class="bi bi-chevron-left"></i>',
                        next: '<i class="bi bi-chevron-right"></i>',
                        last: '<i class="bi bi-chevron-double-right"></i>'
                    }
                }
            });
        }

        // 2. Global DOM Restructuring Engine
        // Wraps ONLY the <table> in an overflow-x: auto container (.table-responsive.dt-table-container)
        // Keeps Search bar (top) and Pagination (bottom) stationary and completely outside the scroll wrapper.
        function restructureDataTableDOM(tableEl) {
            if (!tableEl) return;
            const $table = $(tableEl);
            if (!$table.length) return;

            const $wrapper = $table.closest('.dataTables_wrapper');
            if (!$wrapper.length) return;

            // Remove horizontal overflow constraints from any ancestor .table-responsive
            // so search bar and pagination do not scroll horizontally
            $wrapper.parents('.table-responsive').each(function() {
                const $p = $(this);
                $p.removeClass('table-responsive')
                  .removeClass('has-scroll-right')
                  .removeClass('has-scroll-left')
                  .addClass('dt-table-outer-wrapper');
            });

            // Ensure ONLY the <table> element is wrapped inside the scroll container
            let $scrollContainer = $table.parent('.dt-table-container');
            if (!$scrollContainer.length) {
                const $directParent = $table.parent();
                if ($directParent.hasClass('table-responsive') && $wrapper.find($directParent).length) {
                    $directParent.addClass('dt-table-container');
                    $scrollContainer = $directParent;
                } else {
                    $table.wrap('<div class="table-responsive dt-table-container"></div>');
                    $scrollContainer = $table.parent();
                }
            }

            // Station top controls (search, length, export buttons) strictly above the scrollable table
            const $topControls = $wrapper.find('.dataTables_length, .dataTables_filter, .dt-buttons');
            if ($topControls.length && $scrollContainer.length) {
                const $topRow = $topControls.closest('.row');
                if ($topRow.length) {
                    if ($scrollContainer.find($topRow).length > 0) {
                        $topRow.insertBefore($scrollContainer);
                    } else {
                        const $scrollRow = $scrollContainer.closest('.row');
                        if ($scrollRow.length && $scrollRow[0] !== $topRow[0]) {
                            if ($topRow.index() > $scrollRow.index()) {
                                $topRow.insertBefore($scrollRow);
                            }
                        }
                    }
                }
            }

            // Station bottom controls (info counter, pagination) strictly below the scrollable table
            const $bottomControls = $wrapper.find('.dataTables_info, .dataTables_paginate');
            if ($bottomControls.length && $scrollContainer.length) {
                const $bottomRow = $bottomControls.closest('.row');
                if ($bottomRow.length) {
                    if ($scrollContainer.find($bottomRow).length > 0) {
                        $bottomRow.insertAfter($scrollContainer);
                    } else {
                        const $scrollRow = $scrollContainer.closest('.row');
                        if ($scrollRow.length && $scrollRow[0] !== $bottomRow[0]) {
                            if ($bottomRow.index() < $scrollRow.index()) {
                                $bottomRow.insertAfter($scrollRow);
                            }
                        }
                    }
                }
            }

            // Re-apply WCAG scroll indicators to the table scroll container
            if (typeof updateTableScrollCues === 'function' && $scrollContainer.length) {
                updateTableScrollCues($scrollContainer[0]);
            }
        }
        window.restructureDataTableDOM = restructureDataTableDOM;

        // Auto-hook into all DataTables events across the system
        $(document).on('init.dt', function(e, settings) {
            restructureDataTableDOM(settings.nTable);
        });

        $(document).on('draw.dt', function(e, settings) {
            restructureDataTableDOM(settings.nTable);
        });

        function scanAndRestructureAllTables() {
            $('table.dataTable, .dataTables_wrapper table').each(function() {
                restructureDataTableDOM(this);
            });
        }

        $(document).ready(function() {
            scanAndRestructureAllTables();
            setTimeout(scanAndRestructureAllTables, 200);
            setTimeout(scanAndRestructureAllTables, 800);
        });

        $(window).on('load', scanAndRestructureAllTables);

        // Run when switching Bootstrap tabs, opening modals, or completing AJAX
        $(document).on('shown.bs.tab shown.bs.modal ajaxComplete', function() {
            setTimeout(scanAndRestructureAllTables, 100);
        });
    })(window.jQuery);
    </script>

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

    <script>window.DAPH_REL_PATH = "<?= $rel_path ?>";</script>
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
            const liveMsg = count > 0 ? ('You have ' + count + ' unread notifications') : 'No new notifications';
            const liveRegion = document.getElementById('liveAlertsRegion');
            if (liveRegion) liveRegion.textContent = liveMsg;

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
                        $('.notification-item').attr('data-read', '1').removeClass('bg-light fw-medium');
                        $('.notif-unread-dot').remove();
                        $('.dropdown-toggle-read-btn')
                            .attr('title', 'Mark as unread')
                            .css('color', '#198754')
                            .html('<i class="bi bi-envelope"></i>');
                        if (modalNotifsCache.length) {
                            modalNotifsCache.forEach(n => n.is_read = 1);
                            renderModalNotifications();
                        }
                    }
                }
            });
        });

        // Dropdown individual toggle read/unread
        $(document).on('click', '.dropdown-toggle-read-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const btn = $(this);
            const notifId = btn.data('id');
            const item = btn.closest('.notification-item');
            if (!notifId) return;

            $.ajax({
                url: notifApiUrl,
                type: 'POST',
                data: { action: 'toggle_read', id: notifId },
                dataType: 'json',
                success: function(resp) {
                    if (resp && resp.success) {
                        const isRead = parseInt(resp.is_read) === 1;
                        item.attr('data-read', isRead ? '1' : '0');
                        btn.attr('data-read', isRead ? '1' : '0');
                        if (isRead) {
                            item.removeClass('bg-light fw-medium');
                            item.find('.notif-unread-dot').remove();
                            btn.attr('title', 'Mark as unread');
                            btn.css('color', '#198754');
                            btn.html('<i class="bi bi-envelope"></i>');
                        } else {
                            item.addClass('bg-light fw-medium');
                            if (item.find('.notif-unread-dot').length === 0) {
                                btn.parent().append('<span class="p-1 bg-danger rounded-circle notif-unread-dot" style="width: 6px; height: 6px;" title="Unread"></span>');
                            }
                            btn.attr('title', 'Mark as read');
                            btn.css('color', '#dc3545');
                            btn.html('<i class="bi bi-check2"></i>');
                        }
                        syncNotificationBadges(resp.unread_count);
                    }
                }
            });
        });

        // Notification item click navigation
        $(document).on('click', '.notif-item-link', function(e) {
            const item = $(this).closest('.notification-item');
            const notifId = item.data('id');
            const isRead = item.attr('data-read') === '1';
            if (notifId && !isRead) {
                $.ajax({
                    url: notifApiUrl,
                    type: 'POST',
                    data: { action: 'mark_read', id: notifId },
                    async: false
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
                                <button type="button" class="btn btn-sm ${!isRead ? 'btn-outline-success' : 'btn-outline-secondary'} py-0.5 px-2 modal-toggle-read-btn" data-id="${item.id}" data-read="${isRead ? '1' : '0'}" style="font-size: 11px;" title="${!isRead ? 'Mark as read' : 'Mark as unread'}">
                                    <i class="bi ${!isRead ? 'bi-check2' : 'bi-envelope'}"></i>
                                    <span>${!isRead ? 'Read' : 'Unread'}</span>
                                </button>
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

        // Toggle read inside modal
        $(document).on('click', '.modal-toggle-read-btn', function() {
            const btn = $(this);
            const notifId = btn.data('id');
            if (!notifId) return;

            $.ajax({
                url: notifApiUrl,
                type: 'POST',
                data: { action: 'toggle_read', id: notifId },
                dataType: 'json',
                success: function(resp) {
                    if (resp && resp.success) {
                        const isRead = parseInt(resp.is_read) === 1;
                        const row = btn.closest('.modal-notif-row');
                        const cached = modalNotifsCache.find(n => parseInt(n.id) === parseInt(notifId));
                        if (cached) cached.is_read = isRead ? 1 : 0;

                        if (isRead) {
                            row.removeClass('bg-light-subtle border-start border-4 border-danger');
                            row.find('.badge.bg-danger').remove();
                            btn.removeClass('btn-outline-success').addClass('btn-outline-secondary');
                            btn.attr('data-read', '1').attr('title', 'Mark as unread');
                            btn.html('<i class="bi bi-envelope"></i> <span>Unread</span>');
                        } else {
                            row.addClass('bg-light-subtle border-start border-4 border-danger');
                            if (row.find('.badge.bg-danger').length === 0) {
                                row.find('.d-flex.align-items-center.gap-2.mb-1').append('<span class="badge bg-danger rounded-pill py-0.5 px-1.5" style="font-size: 9px;">NEW</span>');
                            }
                            btn.removeClass('btn-outline-secondary').addClass('btn-outline-success');
                            btn.attr('data-read', '0').attr('title', 'Mark as read');
                            btn.html('<i class="bi bi-check2"></i> <span>Read</span>');
                        }

                        // Also update header dropdown if item present
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

                        if (resp.unread_count !== undefined) {
                            syncNotificationBadges(resp.unread_count);
                        }
                    }
                }
            });
        });

        // =====================================================================
        // Accessibility (WCAG 2.1 Level AA) Global Enhancements
        // =====================================================================

        // 1. Modal Focus Trapping & Restoration (WCAG 2.4.3)
        let lastActiveModalTrigger = null;
        $(document).on('show.bs.modal', function(e) {
            lastActiveModalTrigger = e.relatedTarget || document.activeElement;
        });
        $(document).on('hidden.bs.modal', function() {
            if (lastActiveModalTrigger && typeof lastActiveModalTrigger.focus === 'function') {
                lastActiveModalTrigger.focus();
                lastActiveModalTrigger = null;
            }
        });

        // 2. Keyboard-Navigable Table Scroll Regions & Visual Scrolling Cues (WCAG 2.1.1 / UX)
        function updateTableScrollCues(el) {
            if (!el) return;
            // Check if table container has horizontal overflow
            const canScrollRight = el.scrollWidth > (el.clientWidth + 2) && (el.scrollLeft + el.clientWidth < el.scrollWidth - 3);
            const canScrollLeft = el.scrollLeft > 3;

            el.classList.toggle('has-scroll-right', canScrollRight);
            el.classList.toggle('has-scroll-left', canScrollLeft);
        }

        function makeTablesAccessible() {
            document.querySelectorAll('.table-responsive').forEach(function(el) {
                if (!el.hasAttribute('tabindex')) {
                    el.setAttribute('tabindex', '0');
                }
                if (!el.hasAttribute('role')) {
                    el.setAttribute('role', 'region');
                }
                if (!el.hasAttribute('aria-label')) {
                    const table = el.querySelector('table');
                    const tableTitle = (table && (table.getAttribute('aria-label') || table.getAttribute('id') || (table.querySelector('caption') ? table.querySelector('caption').textContent.trim() : null))) || 'Data table';
                    el.setAttribute('aria-label', tableTitle + ', scrollable horizontally with left and right arrow keys');
                }
                updateTableScrollCues(el);
            });
        }

        // Initialize immediately and after DOM content loads
        makeTablesAccessible();
        $(document).ready(makeTablesAccessible);
        window.addEventListener('load', makeTablesAccessible);

        // Update scroll indicators dynamically when scrolling horizontally
        $(document).on('scroll', '.table-responsive', function() {
            updateTableScrollCues(this);
        });

        // Keyboard Accessibility: Left and Right arrow keys scroll focused table horizontally
        $(document).on('keydown', '.table-responsive', function(e) {
            // Only trigger keyboard scrolling if the responsive table wrapper itself is focused
            if (e.target !== this) return;

            const scrollDistance = 80;
            if (e.key === 'ArrowRight') {
                this.scrollBy({ left: scrollDistance, behavior: 'smooth' });
                e.preventDefault();
            } else if (e.key === 'ArrowLeft') {
                this.scrollBy({ left: -scrollDistance, behavior: 'smooth' });
                e.preventDefault();
            } else if (e.key === 'Home') {
                this.scrollTo({ left: 0, behavior: 'smooth' });
                e.preventDefault();
            } else if (e.key === 'End') {
                this.scrollTo({ left: this.scrollWidth, behavior: 'smooth' });
                e.preventDefault();
            }
        });

        // Re-evaluate cues on resize, tab switch, modal show, DataTables draw, and AJAX
        $(window).on('resize', function() {
            document.querySelectorAll('.table-responsive').forEach(updateTableScrollCues);
        });
        $(document).on('shown.bs.tab shown.bs.modal draw.dt ajaxComplete', function() {
            setTimeout(makeTablesAccessible, 50);
        });
    </script>
    <?php if (!empty($pageScripts)) echo $pageScripts; ?>
</body>

</html>