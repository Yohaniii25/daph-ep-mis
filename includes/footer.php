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
    <!-- Farm Module JS -->
    <script src="<?= $rel_path ?>assets/js/farm.js"></script>

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
    </script>
    <?php if (!empty($pageScripts)) echo $pageScripts; ?>
</body>

</html>