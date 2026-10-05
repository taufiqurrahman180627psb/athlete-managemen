<?php
/** Daftar atlet + pencarian + filter + paginasi. Admin & Pelatih. */
require_once __DIR__ . '/../../middleware/role.php';
requireStaff();

$pdo = db();
$pageTitle = 'Data Atlet';

$q      = get_str('q');
$gender = get_str('gender');
$sport  = get_str('sport');
$school = get_str('school');
$status = get_str('status');

$where = [];
$params = [];
if ($q !== '') {
    $like = '%' . $q . '%';
    $where[] = '(full_name LIKE ? OR school LIKE ? OR sport LIKE ? OR position LIKE ?)';
    array_push($params, $like, $like, $like, $like);
}
if (in_array($gender, ['Laki-laki', 'Perempuan'], true)) { $where[] = 'gender = ?'; $params[] = $gender; }
if ($sport !== '')  { $where[] = 'sport = ?';  $params[] = $sport; }
if ($school !== '') { $where[] = 'school = ?'; $params[] = $school; }
if (in_array($status, ['aktif', 'nonaktif'], true)) { $where[] = 'status = ?'; $params[] = $status; }
$w = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$st = $pdo->prepare("SELECT COUNT(*) FROM athletes $w");
$st->execute($params);
$p = paginate((int) $st->fetchColumn(), (int) ($_GET['page'] ?? 1));

$st = $pdo->prepare("SELECT id, full_name, gender, school, sport, position, status, photo FROM athletes $w ORDER BY full_name ASC LIMIT {$p['perPage']} OFFSET {$p['offset']}");
$st->execute($params);
$rows = $st->fetchAll();

$sports  = $pdo->query('SELECT DISTINCT sport FROM athletes ORDER BY sport')->fetchAll(PDO::FETCH_COLUMN);
$schools = $pdo->query("SELECT DISTINCT school FROM athletes WHERE school IS NOT NULL AND school <> '' ORDER BY school")->fetchAll(PDO::FETCH_COLUMN);

include ROOT_PATH . '/components/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div class="text-muted">Total: <strong><?= $p['total'] ?></strong> atlet</div>
    <a href="<?= e(url('athletes/form.php')) ?>" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Tambah Atlet</a>
</div>

<form method="get" class="card card-body shadow-sm mb-3">
    <div class="row g-2">
        <div class="col-md-4"><input name="q" class="form-control" placeholder="Cari nama / sekolah / cabang / posisi" value="<?= e($q) ?>"></div>
        <div class="col-md-2">
            <select name="gender" class="form-select"><option value="">Semua JK</option>
                <?php foreach (['Laki-laki', 'Perempuan'] as $g): ?><option <?= $gender === $g ? 'selected' : '' ?>><?= e($g) ?></option><?php endforeach; ?>
            </select></div>
        <div class="col-md-2">
            <select name="sport" class="form-select"><option value="">Semua cabang</option>
                <?php foreach ($sports as $s): ?><option <?= $sport === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?>
            </select></div>
        <div class="col-md-2">
            <select name="school" class="form-select"><option value="">Semua sekolah</option>
                <?php foreach ($schools as $s): ?><option <?= $school === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?>
            </select></div>
        <div class="col-md-2">
            <select name="status" class="form-select"><option value="">Semua status</option>
                <option value="aktif" <?= $status === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                <option value="nonaktif" <?= $status === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
            </select></div>
    </div>
    <div class="mt-2 d-flex gap-2">
        <button class="btn btn-outline-primary"><i class="bi bi-search"></i> Cari</button>
        <a href="<?= e(url('athletes/index.php')) ?>" class="btn btn-light">Reset</a>
    </div>
</form>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light"><tr>
                <th>No</th><th>Atlet</th><th>JK</th><th>Sekolah</th><th>Cabang</th><th>Posisi</th><th>Status</th><th class="text-end">Aksi</th>
            </tr></thead>
            <tbody>
            <?php if (!$rows): ?><tr><td colspan="8" class="text-center text-muted py-4">Data tidak ditemukan.</td></tr><?php endif; ?>
            <?php $no = $p['offset']; foreach ($rows as $a): $no++; ?>
                <tr>
                    <td><?= $no ?></td>
                    <td><div class="d-flex align-items-center gap-2"><?= avatar_html($a['full_name'], $a['photo'], 36) ?>
                        <a class="fw-semibold text-decoration-none" href="<?= e(url('athletes/view.php?id=' . $a['id'])) ?>"><?= e($a['full_name']) ?></a></div></td>
                    <td><?= e($a['gender']) ?></td>
                    <td><?= e($a['school'] ?: '-') ?></td>
                    <td><?= e($a['sport']) ?></td>
                    <td><?= e($a['position'] ?: '-') ?></td>
                    <td><?= status_badge($a['status']) ?></td>
                    <td class="text-end text-nowrap">
                        <a class="btn btn-sm btn-outline-secondary" title="Lihat" href="<?= e(url('athletes/view.php?id=' . $a['id'])) ?>"><i class="bi bi-eye"></i></a>
                        <a class="btn btn-sm btn-outline-primary" title="Edit" href="<?= e(url('athletes/form.php?id=' . $a['id'])) ?>"><i class="bi bi-pencil"></i></a>
                        <?php if (isAdmin()): ?>
                            <button class="btn btn-sm btn-outline-danger" title="Hapus" data-bs-toggle="modal" data-bs-target="#deleteModal"
                                    data-id="<?= (int) $a['id'] ?>" data-name="<?= e($a['full_name']) ?>"><i class="bi bi-trash"></i></button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white"><?= pagination_html($p) ?></div>
</div>

<?php
if (isAdmin()) {
    $deleteUrl = url('athletes/delete.php');
    $deleteWarning = 'Seluruh prestasi dan tes kebugaran atlet ini ikut terhapus. Akun terkait tidak dihapus, hanya diputus dari data atlet.';
    include ROOT_PATH . '/components/delete_modal.php';
}
include ROOT_PATH . '/components/footer.php';
