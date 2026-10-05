<?php
/** Profil lengkap satu atlet + prestasi + tes + grafik. Admin & Pelatih. */
require_once __DIR__ . '/../../middleware/role.php';
requireStaff();

$pdo = db();
$id = get_id();
$st = $pdo->prepare('SELECT * FROM athletes WHERE id = ? LIMIT 1');
$st->execute([$id]);
$athlete = $st->fetch();
if (!$athlete) {
    flash('danger', 'Data atlet tidak ditemukan.');
    go('athletes/index.php');
}

$st = $pdo->prepare('SELECT * FROM achievements WHERE athlete_id = ? ORDER BY competition_year DESC, id DESC');
$st->execute([$id]);
$achievements = $st->fetchAll();

$st = $pdo->prepare('SELECT * FROM fitness_tests WHERE athlete_id = ? ORDER BY test_date ASC, id ASC');
$st->execute([$id]);
$tests = $st->fetchAll();
$latest = $tests ? end($tests) : null;

$pageTitle = 'Profil Atlet';
$useCharts = true;
include ROOT_PATH . '/components/header.php';
?>
<div class="d-flex flex-wrap gap-2 mb-3 no-print">
    <a href="<?= e(url('athletes/index.php')) ?>" class="btn btn-light"><i class="bi bi-arrow-left"></i> Kembali</a>
    <a href="<?= e(url('athletes/form.php?id=' . $id)) ?>" class="btn btn-outline-primary"><i class="bi bi-pencil"></i> Ubah</a>
    <a href="<?= e(url('achievements/form.php?athlete_id=' . $id)) ?>" class="btn btn-outline-success"><i class="bi bi-trophy"></i> Tambah Prestasi</a>
    <a href="<?= e(url('fitness-tests/form.php?athlete_id=' . $id)) ?>" class="btn btn-outline-success"><i class="bi bi-heart-pulse"></i> Tambah Tes</a>
</div>

<?php include ROOT_PATH . '/components/athlete_profile_card.php'; ?>

<div class="card shadow-sm mt-3">
    <div class="card-header bg-white fw-semibold">Prestasi (<?= count($achievements) ?>)</div>
    <div class="table-responsive"><table class="table mb-0">
        <thead class="table-light"><tr><th>Tahun</th><th>Kejuaraan</th><th>Kategori</th><th>Hasil</th><th>Peringkat</th></tr></thead>
        <tbody>
        <?php if (!$achievements): ?><tr><td colspan="5" class="text-center text-muted py-3">Belum ada prestasi.</td></tr><?php endif; ?>
        <?php foreach ($achievements as $a): ?>
            <tr><td><?= e($a['competition_year']) ?></td><td><?= e($a['competition_name']) ?></td><td><?= e($a['category'] ?: '-') ?></td>
                <td><?= e($a['result']) ?></td><td><?= e($a['rank'] ?? '-') ?></td></tr>
        <?php endforeach; ?>
        </tbody></table></div>
</div>

<div class="card shadow-sm mt-3">
    <div class="card-header bg-white fw-semibold">Riwayat Tes Kebugaran (<?= count($tests) ?>)</div>
    <div class="table-responsive"><table class="table table-sm mb-0">
        <thead class="table-light"><tr><th>Tanggal</th><th>TB</th><th>BB</th><th>Push-up</th><th>Sit-up</th><th>Sprint 20m</th><th>Beep</th><th>S&amp;R</th><th>Agility</th><th>VO2 Max</th></tr></thead>
        <tbody>
        <?php if (!$tests): ?><tr><td colspan="10" class="text-center text-muted py-3">Belum ada tes.</td></tr><?php endif; ?>
        <?php foreach (array_reverse($tests) as $t): ?>
            <tr><td><?= e(fmt_date($t['test_date'])) ?></td><td><?= e($t['height'] ?? '-') ?></td><td><?= e($t['weight'] ?? '-') ?></td>
                <td><?= e($t['push_up'] ?? '-') ?></td><td><?= e($t['sit_up'] ?? '-') ?></td><td><?= e($t['sprint_20m'] ?? '-') ?></td>
                <td><?= e($t['beep_test'] ?? '-') ?></td><td><?= e($t['sit_and_reach'] ?? '-') ?></td><td><?= e($t['agility'] ?? '-') ?></td><td><?= e($t['vo2_max'] ?? '-') ?></td></tr>
        <?php endforeach; ?>
        </tbody></table></div>
</div>

<h2 class="h5 mt-4">Grafik Perkembangan</h2>
<?php include ROOT_PATH . '/components/progress_charts.php'; ?>
<?php include ROOT_PATH . '/components/footer.php';
