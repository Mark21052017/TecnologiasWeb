<?php if (!empty($isAuthenticated)): ?>
        </div>
    </div>
<?php endif; ?>
<?php $appJsVersion = (string) (@filemtime(dirname(__DIR__, 2) . '/js/app.js') ?: '1'); ?>
<script src="<?= e(app_url('js/app.js?v=' . $appJsVersion)) ?>" defer></script>
</body>
</html>
