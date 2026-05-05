        </main>
        <footer class="text-center text-muted py-3 mt-5">
            <small>&copy; <?= date('Y') ?> <?= APP_NAME ?>. All rights reserved.</small>
        </footer>
    </div><!-- /.app-content -->
</div><!-- /.app-layout -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<!-- DataTables: search, sort, paginate, export-to-Excel/CSV/PDF/Print -->
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jszip@3.10.1/dist/jszip.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/pdfmake@0.2.10/build/pdfmake.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/pdfmake@0.2.10/build/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

<script src="<?= url('assets/js/app.js') ?>"></script>

<?php
// Realtime hub: only emit a WS URL when the user is logged in.
// Token + URL are short-lived and tied to current pharmacy_id.
if (function_exists('isLoggedIn') && isLoggedIn()) {
    require_once __DIR__ . '/../../lib/RealtimeHub.php';
    $__wsUrl = RealtimeHub::browserUrl();
    if ($__wsUrl): ?>
        <script>window.WS_URL = <?= json_encode($__wsUrl) ?>;</script>
        <script src="<?= url('assets/js/realtime.js') ?>"></script>
<?php endif; } ?>

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
