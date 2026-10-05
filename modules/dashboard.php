<?php
/** Dashboard Admin & Pelatih (satu file, tampilan menyesuaikan role). */
require_once __DIR__ . '/../middleware/role.php';
requireStaff();

$pdo = db();
$isAdmin = isAdmin();
$pageTitle = 'Dashboard';
$useCharts = true;

$count = function (string $sql) use ($pdo): int {
    return (int) $pdo->query($sql)->fetchColumn();
};

if ($isAdmin) {
    $cards = [
        ['Total Pengguna', 'bi-people', $count('SELECT COUNT(*) FROM users'), 'primary'],
        ['Total Pelatih', 'bi-person-workspace', $count("SELECT COUNT(*) FROM users WHERE role='pelatih'"), 'info'],
        ['Total Atlet', 'bi-person-badge', $count('SELECT COUNT(*) FROM athletes'), 'success'],
        ['Atlet Aktif', 'bi-person-check', $count("SELECT COUNT(*) FROM athletes WHERE status='aktif'"), 'success'],
        ['Atlet Nonaktif', 'bi-person-dash', $count("SELECT COUNT(*) FROM athletes WHERE status='nonaktif'"), 'secondary'],
        ['Total Prestasi', 'bi-trophy', $count('SELECT COUNT(*) FROM achievements'), 'warning'],
        ['Total Tes Kebugaran', 'bi-heart-pulse', $count('SELECT COUNT(*) FROM fitness_tests'), 'danger'],
    ];
} else {
    $cards = [
        ['Total Atlet', 'bi-person-badge', $count('SELECT COUNT(*) FROM athletes'), 'primary'],
        ['Atlet Aktif', 'bi-person-check', $count("SELECT COUNT(*) FROM athletes WHERE status='aktif'"), 'success'],
        ['Total Prestasi', 'bi-trophy', $count('SELECT COUNT(*) FROM achievements'), 'warning'],
        ['Total Tes Kebugaran', 'bi-heart-pulse', $count('SELECT COUNT(*) FROM fitness_tests'), 'danger'],
    ];
}

$recentAthletes = $pdo->query('SELECT id, full_name, sport, school, photo FROM athletes ORDER BY id DESC LIMIT 5')->fetchAll();
$recentAch = $pdo->query('SELECT a.id AS athlete_id, a.full_name, c.competition_year, c.competition_name, c.result
                          FROM achievements c JOIN athletes a ON a.id = c.athlete_id ORDER BY c.id DESC LIMIT 5')->fetchAll();
$recentTests = $pdo->query('SELECT a.id AS athlete_id, a.full_name, t.test_date, t.vo2_max, t.weight
                            FROM fitness_tests t JOIN athletes a ON a.id = t.athlete_id ORDER BY t.test_date DESC, t.id DESC LIMIT 5')->fetchAll();
$bySport = $pdo->query('SELECT sport, COUNT(*) AS c FROM athletes GROUP BY sport ORDER BY c DESC, sport')->fetchAll();

// Pelatih: pilih atlet untuk grafik perkembangan
$tests = [];
$athleteList = [];
$selId = 0;
if (!$isAdmin) {
    $athleteList = $pdo->query('SELECT id, full_name FROM athletes ORDER BY full_name')->fetchAll();
    $validIds = array_map('intval', array_column($athleteList, 'id'));
    $selId = get_id('athlete_id');
    if (!in_array($selId, $validIds, true)) {
        $selId = $recentTests ? (int) $recentTests[0]['athlete_id'] : ($validIds[0] ?? 0);
    }
    if ($selId > 0) {
        $st = $pdo->prepare('SELECT * FROM fitness_tests WHERE athlete_id = ? ORDER BY test_date ASC, id ASC');
        $st->execute([$selId]);
        $tests = $st->fetchAll();
    }
}

include ROOT_PATH . '/components/header.php';
?>
<div class="row g-3 mb-3">
    <?php foreach ($cards as [$label, $icon, $val, $color]): ?>
        <div class="col-6 col-lg-3">
            <div class="card shadow-sm stat-card h-100"><div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-<?= e($color) ?>-subtle text-<?= e($color) ?>-emphasis"><i class="bi <?= e($icon) ?>"></i></div>
                <div><div class="fs-4 fw-bold lh-1"><?= (int) $val ?></div><div class="text-muted small"><?= e($label) ?></div></div>
            </div></div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-5"><div class="card shadow-sm h-100">
        <div class="card-header bg-white fw-semibold">Atlet per Cabang Olahraga</div>
        <div class="card-body"><?php if ($bySport): ?><div class="chart-box"><canvas id="sportChart"></canvas></div><?php else: ?><p class="text-muted mb-0">Belum ada data.</p><?php endif; ?></div>
    </div></div>
    <div class="col-lg-7"><div class="card shadow-sm h-100">
        <div class="card-header bg-white fw-semibold">Atlet Terbaru</div>
        <ul class="list-group list-group-flush">
            <?php if (!$recentAthletes): ?><li class="list-group-item text-muted">Belum ada atlet.</li><?php endif; ?>
            <?php foreach ($recentAthletes as $a): ?>
                <li class="list-group-item d-flex align-items-center gap-2"><?= avatar_html($a['full_name'], $a['photo'], 36) ?>
                    <div><a class="fw-semibold text-decoration-none" href="<?= e(url('athletes/view.php?id=' . $a['id'])) ?>"><?= e($a['full_name']) ?></a>
                    <div class="small text-muted"><?= e($a['sport']) ?> &middot; <?= e($a['school'] ?: '-') ?></div></div></li>
            <?php endforeach; ?>
        </ul>
    </div></div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-6"><div class="card shadow-sm h-100">
        <div class="card-header bg-white fw-semibold">Prestasi Terbaru</div>
        <ul class="list-group list-group-flush">
            <?php if (!$recentAch): ?><li class="list-group-item text-muted">Belum ada prestasi.</li><?php endif; ?>
            <?php foreach ($recentAch as $r): ?>
                <li class="list-group-item"><strong><?= e($r['full_name']) ?></strong> &mdash; <?= e($r['result']) ?>
                    <div class="small text-muted"><?= e($r['competition_name']) ?> (<?= e($r['competition_year']) ?>)</div></li>
            <?php endforeach; ?>
        </ul>
    </div></div>
    <div class="col-lg-6"><div class="card shadow-sm h-100">
        <div class="card-header bg-white fw-semibold"><?= $isAdmin ? 'Tes Kebugaran Terbaru' : 'Atlet yang Terakhir Mengikuti Tes' ?></div>
        <ul class="list-group list-group-flush">
            <?php if (!$recentTests): ?><li class="list-group-item text-muted">Belum ada tes.</li><?php endif; ?>
            <?php foreach ($recentTests as $r): ?>
                <li class="list-group-item d-flex justify-content-between">
                    <span><strong><?= e($r['full_name']) ?></strong><div class="small text-muted">Tes <?= e(fmt_date($r['test_date'])) ?></div></span>
                    <span class="text-end small text-muted">VO2 <?= e($r['vo2_max'] ?? '-') ?><br>BB <?= e($r['weight'] ?? '-') ?> kg</span></li>
            <?php endforeach; ?>
        </ul>
    </div></div>
</div>

<?php if (!$isAdmin): ?>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
        <h2 class="h5 mb-0">Grafik Perkembangan Atlet</h2>
        <form method="get" class="d-flex gap-2">
            <select name="athlete_id" class="form-select" onchange="this.form.submit()">
                <?php foreach ($athleteList as $a): ?><option value="<?= (int) $a['id'] ?>" <?= $selId === (int) $a['id'] ? 'selected' : '' ?>><?= e($a['full_name']) ?></option><?php endforeach; ?>
            </select>
        </form>
    </div>
    <?php include ROOT_PATH . '/components/progress_charts.php'; ?>
<?php endif; ?>

<?php if ($bySport): ?>
<script>
new Chart(document.getElementById('sportChart'), {
    type: 'bar',
    data: { labels: <?= js_json(array_column($bySport, 'sport')) ?>,
            datasets: [{ label: 'Jumlah atlet', data: <?= js_json(array_map('intval', array_column($bySport, 'c'))) ?>, backgroundColor: '#4dabf7' }] },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
});
</script>
<?php endif; ?>
<?php include ROOT_PATH . '/components/footer.php';
