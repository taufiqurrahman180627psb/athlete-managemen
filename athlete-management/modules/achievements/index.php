<?php
/** Daftar prestasi + filter tahun/atlet + paginasi. Admin & Pelatih. */
require_once __DIR__ . '/../../middleware/role.php';
requireStaff();

$pdo = db();
$pageTitle = 'Prestasi Atlet';

$q = get_str('q');
$year = get_id('year');
$athleteId = get_id('athlete_id');

$where = [];
$params = [];
if ($q !== '') {
    $like = '%' . $q . '%';
    $where[] = '(a.full_name LIKE ? OR c.competition_name LIKE ? OR c.category LIKE ?)';
    array_push($params, $like, $like, $like);
}
if ($year > 0)      { $where[] = 'c.competition_year = ?'; $params[] = $year; }
if ($athleteId > 0) { $where[] = 'c.athlete_id = ?';       $params[] = $athleteId; }
$w = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$st = $pdo->prepare("SELECT COUNT(*) FROM achievements c JOIN athletes a ON a.id = c.athlete_id $w");
$st->execute($params);
$p = paginate((int) $st->fetchColumn(), (int) ($_GET['page'] ?? 1));

$st = $pdo->prepare("SELECT c.*, a.full_name FROM achievements c JOIN athletes a ON a.id = c.athlete_id $w
                     ORDER BY c.competition_year DESC, c.id DESC LIMIT {$p['perPage']} OFFSET {$p['offset']}");
$st->execute($params);
$rows = $st->fetchAll();

$years = $pdo->query('SELECT DISTINCT competition_year FROM achievements ORDER BY competition_year DESC')->fetchAll(PDO::FETCH_COLUMN);
$athletes = $pdo->query('SELECT id, full_name FROM athletes ORDER BY full_name')->fetchAll();

include ROOT_PATH . '/components/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div class="text-muted">Total: <strong><?= $p['total'] ?></strong> prestasi</div>
    <a href="<?= e(url('achievements/form.php')) ?>" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Tambah Prestasi</a>
</div>

<form method="get" class="card card-body shadow-sm mb-3">
    <div class="row g-2">
        <div class="col-md-4"><input name="q" class="form-control" placeholder="Cari atlet / kejuaraan / kategori" value="<?= e($q) ?>"></div>
        <div class="col-md-3"><select name="athlete_id" class="form-select"><option value="">Semua atlet</option>
            <?php foreach ($athletes as $a): ?><option value="<?= (int) $a['id'] ?>" <?= $athleteId === (int) $a['id'] ? 'selected' : '' ?>><?= e($a['full_name']) ?></option><?php endforeach; ?>
        </select></div>
        <div class="col-md-2"><select name="year" class="form-select"><option value="">Semua tahun</option>
            <?php foreach ($years as $y): ?><option value="<?= (int) $y ?>" <?= $year === (int) $y ? 'selected' : '' ?>><?= (int) $y ?></option><?php endforeach; ?>
        </select></div>
        <div class="col-md-3 d-flex gap-2">
            <button class="btn btn-outline-primary"><i class="bi bi-search"></i> Cari</button>
            <a href="<?= e(url('achievements/index.php')) ?>" class="btn btn-light">Reset</a>
        </div>
    </div>
</form>

<div class="card shadow-sm">
    <div class="table-responsive"><table class="table table-hover mb-0">
        <thead class="table-light"><tr><th>No</th><th>Atlet</th><th>Tahun</th><th>Kejuaraan</th><th>Kategori</th><th>Hasil</th><th>Peringkat</th><th class="text-end">Aksi</th></tr></thead>
        <tbody>
        <?php if (!$rows): ?><tr><td colspan="8" class="text-center text-muted py-4">Data tidak ditemukan.</td></tr><?php endif; ?>
        <?php $no = $p['offset']; foreach ($rows as $r): $no++; ?>
            <tr>
                <td><?= $no ?></td>
                <td><a class="text-decoration-none" href="<?= e(url('athletes/view.php?id=' . $r['athlete_id'])) ?>"><?= e($r['full_name']) ?></a></td>
                <td><?= e($r['competition_year']) ?></td>
                <td><?= e($r['competition_name']) ?></td>
                <td><?= e($r['category'] ?: '-') ?></td>
                <td><?= e($r['result']) ?></td>
                <td><?= e($r['rank'] ?? '-') ?></td>
                <td class="text-end text-nowrap">
                    <a class="btn btn-sm btn-outline-primary" title="Edit" href="<?= e(url('achievements/form.php?id=' . $r['id'])) ?>"><i class="bi bi-pencil"></i></a>
                    <button class="btn btn-sm btn-outline-danger" title="Hapus" data-bs-toggle="modal" data-bs-target="#deleteModal"
                            data-id="<?= (int) $r['id'] ?>" data-name="<?= e($r['competition_name'] . ' - ' . $r['full_name']) ?>"><i class="bi bi-trash"></i></button>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody></table></div>
    <div class="card-footer bg-white"><?= pagination_html($p) ?></div>
</div>
<?php
$deleteUrl = url('achievements/delete.php');
include ROOT_PATH . '/components/delete_modal.php';
include ROOT_PATH . '/components/footer.php';
