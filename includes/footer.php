    </div>
</main>

<footer class="site-footer">
    <div class="container footer-inner">
        <span>UPTM Smart Library System (UPTM-SLS)</span>
        <span>Final Year Project Prototype</span>
    </div>
</footer>

<?php $jsFile = __DIR__ . '/../assets/js/app.js'; $jsVersion = is_file($jsFile) ? (string) filemtime($jsFile) : '3'; ?>
<script src="<?= e(url('assets/js/app.js')) ?>?v=<?= e($jsVersion) ?>"></script>
</body>
</html>
