<?php
/**
 * Laporan: Data Atlet, Prestasi, Kebugaran. Admin & Pelatih.
 * Cetak/PDF memakai dialog cetak browser (Save as PDF). Excel memakai file .xls (tabel HTML).
 */
require_once __DIR__ . '/../../middleware/role.php';
requireStaff();

$pdo = db();
$type = get_str('type');
if (!in_array($type, ['athletes', 'achievements', 'fitness'], true)) { $type = 'athletes'; }

$sport = get_str('sport');
$status = get_str('status');
$year = get_id('year');
$from = get_str('date_from');
$to = get_str('date_to');
if ($from !== '' && !valid_date($from)) { $from = ''; }
if ($to !== '' && !valid_date($to)) { $to = ''; }

$where = [];
$params = [];
if ($sport !== '') { $where[] = 'a.sport = ?'; $params[] = $sport; }

if ($type === 'athletes') {
    $title = 'Laporan Data Atlet';
    $headers = ['No', 'Nama', 'Jenis Kelamin', 'Tanggal Lahir', 'Sekolah', 'Kelas', 'Cabang Olahraga', 'Posisi', 'Status'];
    if (in_array($status, ['aktif', 'nonaktif'], true)) { $where[] = 'a.status = ?'; $params[] = $status; }
    $w = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    $st = $pdo->prepare("SELECT a.full_name, a.gender, a.birth_date, a.school, a.`class`, a.sport, a.position, a.status FROM athletes a $w ORDER BY a.full_name");
    $st->execute($params);
    $rows = [];
    foreach ($st as $i => $r) {
        $rows[] = [$i + 1, $r['full_name'], $r['gender'], fmt_date($r['birth_date']), $r['school'] ?: '-', $r['class'] ?: '-', $r['sport'], $r['position'] ?: '-', ucfirst($r['status'])];
    }
} elseif ($type === 'achievements') {
    $title = 'Laporan Prestasi Atlet';
    $headers = ['No', 'Nama Atlet', 'Tahun', 'Kejuaraan', 'Kategori', 'Hasil', 'Peringkat'];
    if ($year > 0) { $where[] = 'c.competition_year = ?'; $params[] = $year; }
    $w = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    $st = $pdo->prepare("SELECT a.full_name, c.competition_year, c.competition_name, c.category, c.result, c.`rank` FROM achievements c JOIN athletes a ON a.id = c.athlete_id $w ORDER BY c.competition_year DESC, a.full_name");
    $st->execute($params);
    $rows = [];
    foreach ($st as $i => $r) {
        $rows[] = [$i + 1, $r['full_name'], $r['competition_year'], $r['competition_name'], $r['category'] ?: '-', $r['result'], $r['rank'] ?? '-'];
    }
} else {
    $title = 'Laporan Tes Kebugaran';
    $headers = ['No', 'Nama Atlet', 'Tanggal Tes', 'Tinggi', 'Berat', 'Push-up', 'Sit-up', 'Sprint 20m', 'Beep Test', 'Sit and Reach', 'Agility', 'VO2 Max'];
    if ($from !== '') { $where[] = 't.test_date >= ?'; $params[] = $from; }
    if ($to !== '')   { $where[] = 't.test_date <= ?'; $params[] = $to; }
    $w = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    $st = $pdo->prepare("SELECT a.full_name, t.* FROM fitness_tests t JOIN athletes a ON a.id = t.athlete_id $w ORDER BY t.test_date DESC, a.full_name");
    $st->execute($params);
    $rows = [];
    foreach ($st as $i => $r) {
        $rows[] = [$i + 1, $r['full_name'], fmt_date($r['test_date']), $r['height'] ?? '-', $r['weight'] ?? '-', $r['push_up'] ?? '-', $r['sit_up'] ?? '-',
                   $r['sprint_20m'] ?? '-', $r['beep_test'] ?? '-', $r['sit_and_reach'] ?? '-', $r['agility'] ?? '-', $r['vo2_max'] ?? '-'];
    }
}

// ---- Export Excel (sebelum ada output HTML apa pun) ----
if (get_str('export') === 'xls') {
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="laporan-' . $type . '-' . date('Ymd') . '.xls"');
    echo "\xEF\xBB\xBF<html><head><meta charset=\"utf-8\"></head><body><table border=\"1\"><tr>";
    foreach ($headers as $h) { echo '<th>' . e($h) . '</th>'; }
    echo '</tr>';
    foreach ($rows as $r) {
        echo '<tr>';
        foreach ($r as $c) { echo '<td>' . e($c) . '</td>'; }
        echo '</tr>';
    }
    echo '</table></body></html>';
    exit;
}

$sports = $pdo->query('SELECT DISTINCT sport FROM athletes ORDER BY sport')->fetchAll(PDO::FETCH_COLUMN);
$years = $pdo->query('SELECT DISTINCT competition_year FROM achievements ORDER BY competition_year DESC')->fetchAll(PDO::FETCH_COLUMN);
$exportUrl = '?' . http_build_query(array_merge($_GET, ['export' => 'xls', 'type' => $type]));

$pageTitle = 'Laporan';
include ROOT_PATH . '/components/header.php';
?>
<form method="get" class="card card-body shadow-sm mb-3 no-print">
    <div class="row g-2 align-items-end">
        <div class="col-md-3"><label class="form-label small mb-1">Jenis laporan</label>
            <select name="type" class="form-select" onchange="this.form.submit()">
                <option value="athletes" <?= $type === 'athletes' ? 'selected' : '' ?>>Data Atlet</option>
                <option value="achievements" <?= $type === 'achievements' ? 'selected' : '' ?>>Prestasi</option>
                <option value="fitness" <?= $type === 'fitness' ? 'selected' : '' ?>>Kebugaran</option>
            </select></div>
        <div class="col-md-2"><label class="form-label small mb-1">Cabang</label>
            <select name="sport" class="form-select"><option value="">Semua</option>
                <?php foreach ($sports as $s): ?><option <?= $sport === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?></select></div>
        <?php if ($type === 'athletes'): ?>
            <div class="col-md-2"><label class="form-label small mb-1">Status</label>
                <select name="status" class="form-select"><option value="">Semua</option>
                    <option value="aktif" <?= $status === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                    <option value="nonaktif" <?= $status === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option></select></div>
        <?php elseif ($type === 'achievements'): ?>
            <div class="col-md-2"><label class="form-label small mb-1">Tahun</label>
                <select name="year" class="form-select"><option value="">Semua</option>
                    <?php foreach ($years as $y): ?><option value="<?= (int) $y ?>" <?= $year === (int) $y ? 'selected' : '' ?>><?= (int) $y ?></option><?php endforeach; ?></select></div>
        <?php else: ?>
            <div class="col-md-2"><label class="form-label small mb-1">Dari</label><input type="date" name="date_from" class="form-control" value="<?= e($from) ?>"></div>
            <div class="col-md-2"><label class="form-label small mb-1">Sampai</label><input type="date" name="date_to" class="form-control" value="<?= e($to) ?>"></div>
        <?php endif; ?>
        <div class="col-md-3 d-flex gap-2 flex-wrap">
            <button class="btn btn-outline-primary">Tampilkan</button>
            <button type="button" class="btn btn-secondary" onclick="window.print()"><i class="bi bi-printer"></i> Cetak</button>
            <button type="button" class="btn btn-danger" onclick="window.print()" title="Di dialog cetak pilih 'Simpan sebagai PDF'"><i class="bi bi-file-earmark-pdf"></i> PDF</button>
            <a class="btn btn-success" href="<?= e($exportUrl) ?>"><i class="bi bi-file-earmark-excel"></i> Excel</a>
        </div>
    </div>
</form>

<div class="card shadow-sm"><div class="card-body">
    <div class="text-center mb-3">
        <h2 class="h5 mb-0"><?= e(setting('organization', 'Klub Olahraga')) ?></h2>
        <div class="fw-semibold"><?= e($title) ?></div>
        <div class="small text-muted">Dicetak: <?= date('d/m/Y H:i') ?> oleh <?= e($_SESSION['full_name']) ?> &middot; <?= count($rows) ?> data</div>
    </div>
    <div class="table-responsive"><table class="table table-bordered table-sm mb-0">
        <thead class="table-light"><tr><?php foreach ($headers as $h): ?><th><?= e($h) ?></th><?php endforeach; ?></tr></thead>
        <tbody>
        <?php if (!$rows): ?><tr><td colspan="<?= count($headers) ?>" class="text-center text-muted py-3">Tidak ada data.</td></tr><?php endif; ?>
        <?php foreach ($rows as $r): ?><tr><?php foreach ($r as $c): ?><td><?= e($c) ?></td><?php endforeach; ?></tr><?php endforeach; ?>
        </tbody></table></div>
</div></div>
<?php include ROOT_PATH . '/components/footer.php';
