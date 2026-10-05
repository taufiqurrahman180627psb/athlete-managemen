<?php
/** Dashboard atlet: HANYA data milik sendiri (athlete_id dari SESSION, bukan dari URL). */
require_once __DIR__ . '/../middleware/role.php';
requireAthlete();

$athlete = load_own_athlete();
$aid = (int) $athlete['id'];
$pdo = db();

$st = $pdo->prepare('SELECT COUNT(*) FROM achievements WHERE athlete_id = ?');
$st->execute([$aid]);
$achCount = (int) $st->fetchColumn();

$st = $pdo->prepare('SELECT * FROM fitness_tests WHERE athlete_id = ? ORDER BY test_date ASC, id ASC');
$st->execute([$aid]);
$tests = $st->fetchAll();
$latest = $tests ? end($tests) : null;

$pageTitle = 'Dashboard';
$useCharts = true;
include ROOT_PATH . '/components/header.php';
?>
<div class="card shadow-sm mb-3"><div class="card-body d-flex flex-column flex-sm-row align-items-sm-center gap-3">
    <?= avatar_html($athlete['full_name'], $athlete['photo'], 88) ?>
    <div class="flex-grow-1">
        <h2 class="h4 mb-1">Halo, <?= e($athlete['full_name']) ?></h2>
        <div class="text-muted"><?= e($athlete['sport']) ?><?= $athlete['position'] ? ' &middot; ' . e($athlete['position']) : '' ?> &middot; <?= e($athlete['school'] ?: '-') ?></div>
        <div class="mt-1"><?= status_badge($athlete['status']) ?></div>
    </div>
</div></div>

<div class="row g-3 mb-3">
    <div class="col-md-4"><div class="card shadow-sm h-100"><div class="card-body">
        <div class="text-muted small">Jumlah Prestasi</div><div class="display-6 fw-bold"><?= $achCount ?></div></div></div></div>
    <div class="col-md-8"><div class="card shadow-sm h-100"><div class="card-body">
        <div class="text-muted small mb-1">Tes Kebugaran Terakhir</div>
        <?php if ($latest): ?>
            <div class="fw-semibold mb-2"><?= e(fmt_date($latest['test_date'])) ?></div>
            <div class="row text-center g-2">
                <div class="col-4 col-md"><div class="fs-5 fw-bold"><?= e($latest['vo2_max'] ?? '-') ?></div><div class="small text-muted">VO2 Max</div></div>
                <div class="col-4 col-md"><div class="fs-5 fw-bold"><?= e($latest['push_up'] ?? '-') ?></div><div class="small text-muted">Push-up</div></div>
                <div class="col-4 col-md"><div class="fs-5 fw-bold"><?= e($latest['sit_up'] ?? '-') ?></div><div class="small text-muted">Sit-up</div></div>
                <div class="col-6 col-md"><div class="fs-5 fw-bold"><?= e($latest['sprint_20m'] ?? '-') ?></div><div class="small text-muted">Sprint 20m (dtk)</div></div>
                <div class="col-6 col-md"><div class="fs-5 fw-bold"><?= e($latest['weight'] ?? '-') ?></div><div class="small text-muted">Berat (kg)</div></div>
            </div>
        <?php else: ?><p class="text-muted mb-0">Belum ada data tes kebugaran.</p><?php endif; ?>
    </div></div></div>
</div>

<h2 class="h5">Grafik Perkembangan</h2>
<?php $chartKeys = ['vo2_max', 'weight']; include ROOT_PATH . '/components/progress_charts.php'; ?>
<div class="mt-3"><a href="<?= e(url('progress.php')) ?>" class="btn btn-outline-primary">Lihat semua grafik</a></div>
<?php include ROOT_PATH . '/components/footer.php';
