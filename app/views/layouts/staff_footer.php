<?php defined('APP_ROOT') || exit; ?>
</main>

<footer class="site-footer">
    <div class="foot-inner">
        <span class="foot-brand">QUEEN <em>Espace <?= e(strtolower(ROLES_LABELS[$user['role'] ?? ''] ?? 'personnel')) ?></em></span>
        <span class="foot-copy">© <?= date('Y') ?> QUEEN. Tous droits réservés.</span>
    </div>
</footer>

<script src="<?= e(asset('js/client.js')) ?>" defer></script>
<script src="<?= e(asset('js/staff.js')) ?>" defer></script>
</body>
</html>
