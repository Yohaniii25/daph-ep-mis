<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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
<script>
    // Toggle sidebar
    document.getElementById('sidebarToggle').addEventListener('click', () => {
        document.body.classList.toggle('sb-sidenav-toggled');
        if (document.body.classList.contains('sb-sidenav-toggled')) {
            document.getElementById('layoutSidenav_nav').style.left = '-260px';
            document.getElementById('layoutSidenav_content').style.marginLeft = '0';
            document.querySelector('.sb-topnav').style.left = '0';
        } else {
            document.getElementById('layoutSidenav_nav').style.left = '0';
            document.getElementById('layoutSidenav_content').style.marginLeft = '260px';
            document.querySelector('.sb-topnav').style.left = '260px';
        }
    });
</script>
</body>
</html>