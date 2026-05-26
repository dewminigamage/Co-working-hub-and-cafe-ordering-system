<!-- ============================================================
     FOOTER
     ============================================================ -->
<footer class="site-footer">
    <div class="container d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span>
            &copy; <?= date('Y') ?>
            <span class="brand-txt">HubSpace</span> – Co-Working Hub &amp; Café
        </span>
        <span class="d-flex gap-3">
            <a href="<?= BASE_URL ?>/index.php">Dashboard</a>
            <a href="<?= BASE_URL ?>/bookings/index.php">Bookings</a>
            <a href="<?= BASE_URL ?>/cafe/menu.php">Café</a>
        </span>
    </div>
</footer>

<!-- Bootstrap 5 JS bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Auto-dismiss flash alerts after 4 s -->
<script>
    (function () {
        const flash = document.querySelector('.flash-alert');
        if (flash) {
            setTimeout(() => {
                const bsAlert = bootstrap.Alert.getOrCreateInstance(flash);
                bsAlert.close();
            }, 4000);
        }
    })();
</script>
</body>
</html>
