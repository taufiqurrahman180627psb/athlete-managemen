<?php
/** Daftar tes kebugaran + filter atlet & periode + paginasi. Admin & Pelatih. */
require_once __DIR__ . '/../../middleware/role.php';
requireStaff();

$pdo = db();
$pageTitle = 'Tes Kebugaran';

$q = get_str('q');
$athleteId = get_id('athlete_id');
$from = get_str('date_from');
$to = get_str('date_to');
if ($from !== '' && !valid_date($from)) { $from = ''; }
if ($to !== '' && !valid_date($to)) { $to = ''; }

$where = [];
$params = [];
if ($q !== '')      { $where[] = 'a.full_name LIKE ?'; $params[] = '%' . $q . '%'; }
if ($athleteId > 0) { $where[] = 't.athlete_id = ?';   $params[] = $athleteId; }
if ($from !== '')   { $where[] = 't.test_date >= ?';   $params[] = $from; }
if ($to !== '')     { $where[] = 't.test_date <= ?';   $params[] = $to; }
$w = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$st = $pdo->prepare("SELECT COUNT(*) FROM fitness_tests t JOIN athletes a ON a.id = t.athlete_id $w");
$st->execute($params);
$p = paginate((int) $st->fetchColumn(), (int) ($_GET['page'] ?? 1));

$st = $pdo->prepare("SELECT t.*, a.full_name FROM fitness_tests t JOIN athletes a ON a.id = t.athlete_id $w
                     ORDER BY t.test_date DESC, t.id DESC LIMIT {$p['perPage']} OFFSET {$p['offset']}");
$st->execute($params);
$rows = $st->fetchAll();
$athletes = $pdo->query('SELECT id, full_name FROM athletes ORDER BY full_name')->fetchAll();

include ROOT_PATH . '/components/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div class="text-muted">Total: <strong><?= $p['total'] ?></strong> hasil tes</div>
    <a href="<?= e(url('fitness-tests/form.php')) ?>" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Tambah Hasil Tes</a>
</div>

<form method="get" class="card card-body shadow-sm mb-3">
    <div class="row g-2">
        <div class="col-md-3"><input name="q" class="form-control" placeholder="Cari nama atlet" value="<?= e($q) ?>"></div>
        <div class="col-md-3"><select name="athlete_id" class="form-select"><option value="">Semua atlet</option>
            <?php foreach ($athletes as $a): ?><option value="<?= (int) $a['id'] ?>" <?= $athleteId === (int) $a['id'] ? 'selected' : '' ?>><?= e($a['full_name']) ?></option><?php endforeach; ?>
        </select></div>
        <div class="col-md-2"><input type="date" name="date_from" class="form-control" value="<?= e($from) ?>" title="Dari tanggal"></div>
        <div class="col-md-2"><input type="date" name="date_to" class="form-control" value="<?= e($to) ?>" title="Sampai tanggal"></div>
        <div class="col-md-2 d-flex gap-2">
            <button class="btn btn-outline-primary"><i class="bi bi-search"></i></button>
            <a href="<?= e(url('fitness-tests/index.php')) ?>" class="btn btn-light">Reset</a>
        </div>
    </div>
</form>

<div class="card shadow-sm">
    <div class="table-responsive"><table class="table table-hover table-sm mb-0">
        <thead class="table-light"><tr><th>No</th><th>Atlet</th><th>Tanggal</th><th>TB</th><th>BB</th><th>Push-up</th><th>Sit-up</th><th>Sprint 20m</th><th>Beep</th><th>S&amp;R</th><th>Agility</th><th>VO2 Max</th><th class="text-end">Aksi</th></tr></thead>
        <tbody>
        <?php if (!$rows): ?><tr><td colspan="13" class="text-center text-muted py-4">Data tidak ditemukan.</td></tr><?php endif; ?>
        <?php $no = $p['offset']; foreach ($rows as $r): $no++; ?>
            <tr>
                <td><?= $no ?></td>
                <td><a class="text-decoration-none" href="<?= e(url('athletes/view.php?id=' . $r['athlete_id'])) ?>"><?= e($r['full_name']) ?></a></td>
                <td><?= e(fmt_date($r['test_date'])) ?></td>
                <td><?= e($r['height'] ?? '-') ?></td><td><?= e($r['weight'] ?? '-') ?></td>
                <td><?= e($r['push_up'] ?? '-') ?></td><td><?= e($r['sit_up'] ?? '-') ?></td>
                <td><?= e($r['sprint_20m'] ?? '-') ?></td><td><?= e($r['beep_test'] ?? '-') ?></td>
                <td><?= e($r['sit_and_reach'] ?? '-') ?></td><td><?= e($r['agility'] ?? '-') ?></td><td><?= e($r['vo2_max'] ?? '-') ?></td>
                <td class="text-end text-nowrap">
                    <a class="btn btn-sm btn-outline-primary" title="Edit" href="<?= e(url('fitness-tests/form.php?id=' . $r['id'])) ?>"><i class="bi bi-pencil"></i></a>
                    <button class="btn btn-sm btn-outline-danger" title="Hapus" data-bs-toggle="modal" data-bs-target="#deleteModal"
                            data-id="<?= (int) $r['id'] ?>" data-name="tes <?= e(fmt_date($r['test_date']) . ' - ' . $r['full_name']) ?>"><i class="bi bi-trash"></i></button>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody></table></div>
    <div class="card-footer bg-white"><?= pagination_html($p) ?></div>
</div>
<?php
$deleteUrl = url('fitness-tests/delete.php');
include ROOT_PATH . '/components/delete_modal.php';
include ROOT_PATH . '/components/footer.php';
