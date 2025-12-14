<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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