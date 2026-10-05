<?php
/** Riwayat tes kebugaran milik sendiri (hanya baca). */
require_once __DIR__ . '/../middleware/role.php';
requireAthlete();

$athlete = load_own_athlete();
$st = db()->prepare('SELECT * FROM fitness_tests WHERE athlete_id = ? ORDER BY test_date DESC, id DESC');
$st->execute([(int) $athlete['id']]);
$rows = $st->fetchAll();

$pageTitle = 'Tes Kebugaran Saya';
include ROOT_PATH . '/components/header.php';
?>
<div class="card shadow-sm"><div class="table-responsive"><table class="table table-hover table-sm mb-0">
    <thead class="table-light"><tr><th>No</th><th>Tanggal</th><th>Tinggi (cm)</th><th>Berat (kg)</th><th>Push-up</th><th>Sit-up</th><th>Sprint 20m (dtk)</th><th>Beep</th><th>Sit &amp; Reach</th><th>Agility</th><th>VO2 Max</th><th>Catatan</th></tr></thead>
    <tbody>
    <?php if (!$rows): ?><tr><td colspan="12" class="text-center text-muted py-4">Belum ada hasil tes.</td></tr><?php endif; ?>
    <?php foreach ($rows as $i => $r): ?>
        <tr><td><?= $i + 1 ?></td><td><?= e(fmt_date($r['test_date'])) ?></td><td><?= e($r['height'] ?? '-') ?></td><td><?= e($r['weight'] ?? '-') ?></td>
            <td><?= e($r['push_up'] ?? '-') ?></td><td><?= e($r['sit_up'] ?? '-') ?></td><td><?= e($r['sprint_20m'] ?? '-') ?></td>
            <td><?= e($r['beep_test'] ?? '-') ?></td><td><?= e($r['sit_and_reach'] ?? '-') ?></td><td><?= e($r['agility'] ?? '-') ?></td>
            <td><?= e($r['vo2_max'] ?? '-') ?></td><td><?= e($r['notes'] ?: '-') ?></td></tr>
    <?php endforeach; ?>
    </tbody></table></div></div>
<?php include ROOT_PATH . '/components/footer.php';
