<?php
/** unauthorized.php - halaman 403 ketika role tidak berhak mengakses halaman. */
require_once __DIR__ . '/middleware/auth.php';
http_response_code(403);
$pageTitle = 'Akses Ditolak';
$bare = true;
include __DIR__ . '/components/header.php';
?>
<div class="card auth-card shadow text-center">
    <div class="card-body p-4">
        <div class="display-4 text-danger"><i class="bi bi-shield-lock"></i></div>
        <h1 class="h4 mt-2">Akses Ditolak (403)</h1>
        <p class="text-muted">Anda tidak memiliki hak akses ke halaman tersebut.</p>
        <?php if (!empty($_SESSION['user_id'])): ?>
            <a href="<?= e(BASE_URL . home_path()) ?>" class="btn btn-primary">Kembali ke Dashboard</a>
        <?php else: ?>
            <a href="<?= e(BASE_URL . '/auth/login.php') ?>" class="btn btn-primary">Ke Halaman Login</a>
        <?php endif; ?>
    </div>
</div>
<?php include __DIR__ . '/components/footer.php'; ?>
