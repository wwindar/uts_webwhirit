        </main> <!-- /.admin-content -->
    </div> <!-- /.admin-main -->
</div> <!-- /.admin-layout -->

<script>
// Toggle Sidebar di Mobile
function toggleAdminSidebar() {
    var sidebar = document.getElementById('adminSidebar');
    var overlay = document.getElementById('adminSidebarOverlay');
    sidebar.classList.toggle('open');
    if(overlay) overlay.classList.toggle('open');
}

// Close sidebar if clicked outside (mobile)
window.onclick = function(event) {
    var sidebar = document.getElementById('adminSidebar');
    var overlay = document.getElementById('adminSidebarOverlay');
    var toggleBtn = document.querySelector('.admin-sidebar-toggle');
    if (sidebar && sidebar.classList.contains('open')) {
        if (!sidebar.contains(event.target) && !toggleBtn.contains(event.target) && event.target !== overlay) {
            sidebar.classList.remove('open');
            if(overlay) overlay.classList.remove('open');
        }
    }
}
</script>
</body>
</html>
