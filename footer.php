<footer class="site-footer">
        <p>&copy; 2026 Online Accessory Store. All Rights Reserved.</p>
    </footer>

    <?php if (isset($_SESSION['popup'])): ?>
        <div id="toast-message" class="toast-message">
            <?= htmlspecialchars($_SESSION['popup']) ?>
        </div>
        <script>
            setTimeout(() => {
                let toast = document.getElementById('toast-message');
                if(toast) {
                    toast.style.opacity = '0';
                    setTimeout(() => toast.remove(), 500);
                }
            }, 3000);
        </script>
        <?php unset($_SESSION['popup']); ?>
    <?php endif; ?>
</body>
</html>