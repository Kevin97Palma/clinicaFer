    </div><!-- /container-fluid -->
</main><!-- /sgc-main -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script src="<?= APP_URL ?>/assets/js/app.js"></script>
<script src="<?= APP_URL ?>/assets/js/sgc-interactions.js"></script>
<?php if (!empty($extraJs)): ?>
    <?= $extraJs ?>
<?php endif; ?>
</body>
</html>
