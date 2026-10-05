<?php
/** Prestasi milik sendiri (hanya baca). */
require_once __DIR__ . '/../middleware/role.php';
requireAthlete();

$athlete = load_own_athlete();
$st = db()->prepare('SELECT * FROM achievements WHERE athlete_id = ? ORDER BY competition_year DESC, id DESC');
$st->execute([(int) $athlete['id']]);
$rows = $st->fetchAll();

$pageTitle = 'Prestasi Saya';
include ROOT_PATH . '/components/header.php';
?>
<div class="card shadow-sm"><div class="table-responsive"><table class="table table-hover mb-0">
    <thead class="table-light"><tr><th>No</th><th>Tahun</th><th>Kejuaraan</th><th>Kategori</th><th>Hasil</th><th>Peringkat</th><th>Catatan</th></tr></thead>
    <tbody>
    <?php if (!$rows): ?><tr><td colspan="7" class="text-center text-muted py-4">Belum ada prestasi.</td></tr><?php endif; ?>
    <?php foreach ($rows as $i => $r): ?>
        <tr><td><?= $i + 1 ?></td><td><?= e($r['competition_year']) ?></td><td><?= e($r['competition_name']) ?></td><td><?= e($r['category'] ?: '-') ?></td>
            <td><?= e($r['result']) ?></td><td><?= e($r['rank'] ?? '-') ?></td><td><?= e($r['notes'] ?: '-') ?></td></tr>
    <?php endforeach; ?>
    </tbody></table></div></div>
<?php include ROOT_PATH . '/components/footer.php';
