        </main>
        <footer class="text-center text-muted py-3 mt-5">
            <small>&copy; <?= date('Y') ?> <?= APP_NAME ?>. All rights reserved.</small>
        </footer>
    </div><!-- /.app-content -->
</div><!-- /.app-layout -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="<?= url('assets/js/app.js') ?>"></script>
<script>
// Sidebar toggle for mobile
(function() {
    const sidebar = document.getElementById('appSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const toggleBtn = document.getElementById('sidebarToggle');
    if (!toggleBtn) return;
    function openSidebar() { sidebar.classList.add('is-open'); overlay.classList.add('is-visible'); }
    function closeSidebar() { sidebar.classList.remove('is-open'); overlay.classList.remove('is-visible'); }
    toggleBtn.addEventListener('click', openSidebar);
    overlay.addEventListener('click', closeSidebar);
    // Close when a link is tapped on mobile
    sidebar.querySelectorAll('.sidebar-link').forEach(a => a.addEventListener('click', () => {
        if (window.innerWidth < 768) closeSidebar();
    }));
})();
</script>
</body>
</html>
