</div><!-- .page-wrapper -->
</div><!-- .app-layout -->

<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<?php if (isset($extraJs)): ?>
    <?php foreach ((array)$extraJs as $js): ?>
        <script src="<?= e($js) ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>
</body>
</html>
