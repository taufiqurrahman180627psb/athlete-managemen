<?php
/** Daftar pengguna. HANYA ADMIN. */
require_once __DIR__ . '/../../middleware/role.php';
requireAdmin();

$pdo = db();
$pageTitle = 'Manajemen Pengguna';
$q = get_str('q');
$role = get_str('role');

$where = [];
$params = [];
if ($q !== '') {
    $like = '%' . $q . '%';
    $where[] = '(u.full_name LIKE ? OR u.email LIKE ?)';
    array_push($params, $like, $like);
}
if (in_array($role, ['admin', 'pelatih', 'atlet'], true)) { $where[] = 'u.role = ?'; $params[] = $role; }
$w = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$st = $pdo->prepare("SELECT COUNT(*) FROM users u $w");
$st->execute($params);
$p = paginate((int) $st->fetchColumn(), (int) ($_GET['page'] ?? 1));

$st = $pdo->prepare("SELECT u.*, a.full_name AS athlete_name FROM users u LEFT JOIN athletes a ON a.id = u.athlete_id $w
                     ORDER BY u.id ASC LIMIT {$p['perPage']} OFFSET {$p['offset']}");
$st->execute($params);
$rows = $st->fetchAll();

include ROOT_PATH . '/components/header.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div class="text-muted">Total: <strong><?= $p['total'] ?></strong> pengguna</div>
    <a href="<?= e(url('users/form.php')) ?>" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Tambah Pengguna</a>
</div>

<form method="get" class="card card-body shadow-sm mb-3">
    <div class="row g-2">
        <div class="col-md-6"><input name="q" class="form-control" placeholder="Cari nama / email" value="<?= e($q) ?>"></div>
        <div class="col-md-3"><select name="role" class="form-select"><option value="">Semua role</option>
            <?php foreach (['admin', 'pelatih', 'atlet'] as $r): ?><option value="<?= e($r) ?>" <?= $role === $r ? 'selected' : '' ?>><?= e(role_label($r)) ?></option><?php endforeach; ?>
        </select></div>
        <div class="col-md-3 d-flex gap-2">
            <button class="btn btn-outline-primary"><i class="bi bi-search"></i> Cari</button>
            <a href="<?= e(url('users/index.php')) ?>" class="btn btn-light">Reset</a>
        </div>
    </div>
</form>

<div class="card shadow-sm">
    <div class="table-responsive"><table class="table table-hover mb-0">
        <thead class="table-light"><tr><th>No</th><th>Nama</th><th>Email</th><th>Role</th><th>Data Atlet</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
        <tbody>
        <?php if (!$rows): ?><tr><td colspan="7" class="text-center text-muted py-4">Data tidak ditemukan.</td></tr><?php endif; ?>
        <?php $no = $p['offset']; foreach ($rows as $u): $no++; $self = (int) $u['id'] === (int) $_SESSION['user_id']; ?>
            <tr>
                <td><?= $no ?></td>
                <td><?= e($u['full_name']) ?><?= $self ? ' <span class="badge bg-light text-dark border">Anda</span>' : '' ?></td>
                <td><?= e($u['email']) ?></td>
                <td><?= role_badge($u['role']) ?></td>
                <td><?php if ($u['role'] === 'atlet'): ?><?= $u['athlete_name'] ? e($u['athlete_name']) : '<span class="text-warning">Belum terhubung</span>' ?><?php else: ?>-<?php endif; ?></td>
                <td><?= status_badge($u['status']) ?></td>
                <td class="text-end text-nowrap">
                    <a class="btn btn-sm btn-outline-primary" title="Edit" href="<?= e(url('users/form.php?id=' . $u['id'])) ?>"><i class="bi bi-pencil"></i></a>
                    <?php if (!$self): ?>
                        <button class="btn btn-sm btn-outline-danger" title="Hapus" data-bs-toggle="modal" data-bs-target="#deleteModal"
                                data-id="<?= (int) $u['id'] ?>" data-name="<?= e($u['full_name']) ?>"><i class="bi bi-trash"></i></button>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody></table></div>
    <div class="card-footer bg-white"><?= pagination_html($p) ?></div>
</div>
<?php
$deleteUrl = url('users/delete.php');
$deleteWarning = 'Akun akan dihapus permanen. Data atlet yang terhubung tidak ikut dihapus.';
include ROOT_PATH . '/components/delete_modal.php';
include ROOT_PATH . '/components/footer.php';
