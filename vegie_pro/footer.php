    </div>
</div>
<script src="app.js"></script>
<?php if (!empty($_SESSION['flash_js'])): ?>
<script>showToast(<?= json_encode($_SESSION['flash_js']) ?>, 'success');</script>
<?php unset($_SESSION['flash_js']); endif; ?>
</body>
</html>