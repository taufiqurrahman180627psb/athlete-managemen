<?php
/**
 * Tambah / ubah pengguna + ubah role + hubungkan akun atlet ke data atlet. HANYA ADMIN.
 * Admin tidak dapat mengubah role/status akunnya sendiri (mencegah terkunci dari sistem).
 */
require_once __DIR__ . '/../../middleware/role.php';
requireAdmin();

$pdo = db();
$id = get_id();
$editing = $id > 0;
$user = null;

if ($editing) {
    $st = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $st->execute([$id]);
    $user = $st->fetch();
    if (!$user) {
        flash('danger', 'Pengguna tidak ditemukan.');
        go('users/index.php');
    }
}
$isSelf = $editing && (int) $user['id'] === (int) $_SESSION['user_id'];

$f = [
    'full_name' => $user['full_name'] ?? '',
    'email' => $user['email'] ?? '',
    'role' => $user['role'] ?? 'atlet',
    'athlete_id' => isset($user['athlete_id']) ? (string) $user['athlete_id'] : '',
    'status' => $user['status'] ?? 'aktif',
];

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    foreach ($f as $k => $_) { $f[$k] = post_str($k); }
    $f['email'] = strtolower($f['email']);
    $pass = (string) ($_POST['password'] ?? '');

    if ($isSelf) {                       // paksa nilai lama
        $f['role'] = $user['role'];
        $f['status'] = $user['status'];
    }
    if ($f['full_name'] === '' || mb_strlen($f['full_name']) > 100) { $errors[] = 'Nama wajib diisi (maks. 100 karakter).'; }
    if (!filter_var($f['email'], FILTER_VALIDATE_EMAIL) || strlen($f['email']) > 150) { $errors[] = 'Format email tidak valid.'; }
    if (!in_array($f['role'], ['admin', 'pelatih', 'atlet'], true)) { $errors[] = 'Role tidak valid.'; }
    if (!in_array($f['status'], ['aktif', 'nonaktif'], true)) { $errors[] = 'Status tidak valid.'; }

    if (!$editing || $pass !== '') {
        if ($err = validate_password($pass)) { $errors[] = $err; }
    }

    $athleteId = null;
    if ($f['role'] === 'atlet' && $f['athlete_id'] !== '') {
        $athleteId = (int) $f['athlete_id'];
        $chk = $pdo->prepare('SELECT id FROM athletes WHERE id = ?');
        $chk->execute([$athleteId]);
        if (!$chk->fetch()) { $errors[] = 'Data atlet tidak ditemukan.'; }
        $chk = $pdo->prepare('SELECT id FROM users WHERE athlete_id = ? AND id <> ?');
        $chk->execute([$athleteId, $id]);
        if ($chk->fetch()) { $errors[] = 'Data atlet tersebut sudah terhubung dengan akun lain.'; }
    }

    $chk = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id <> ?');
    $chk->execute([$f['email'], $id]);
    if ($chk->fetch()) { $errors[] = 'Email sudah digunakan pengguna lain.'; }

    if (!$errors) {
        try {
            if ($editing) {
                $sql = 'UPDATE users SET full_name=?, email=?, role=?, athlete_id=?, status=?';
                $p = [$f['full_name'], $f['email'], $f['role'], $athleteId, $f['status']];
                if ($pass !== '') { $sql .= ', password=?'; $p[] = password_hash($pass, PASSWORD_DEFAULT); }
                $p[] = $id;
                $pdo->prepare($sql . ' WHERE id=?')->execute($p);
                flash('success', 'Pengguna berhasil diperbarui.');
            } else {
                $pdo->prepare('INSERT INTO users (full_name, email, password, role, athlete_id, status) VALUES (?,?,?,?,?,?)')
                    ->execute([$f['full_name'], $f['email'], password_hash($pass, PASSWORD_DEFAULT), $f['role'], $athleteId, $f['status']]);
                flash('success', 'Pengguna berhasil ditambahkan.');
            }
            go('users/index.php');
        } catch (PDOException $ex) {
            error_log('Simpan pengguna gagal: ' . $ex->getMessage());
            $errors[] = 'Gagal menyimpan data (email atau data atlet mungkin sudah dipakai).';
        }
    }
}

// Atlet yang belum punya akun (+ atlet milik akun ini saat edit)
$st = $pdo->prepare('SELECT a.id, a.full_name, a.sport FROM athletes a LEFT JOIN users u ON u.athlete_id = a.id
                     WHERE u.id IS NULL OR u.id = ? ORDER BY a.full_name');
$st->execute([$id]);
$athletes = $st->fetchAll();

$pageTitle = $editing ? 'Ubah Pengguna' : 'Tambah Pengguna';
include ROOT_PATH . '/components/header.php';
?>
<div class="card shadow-sm"><div class="card-body">
    <?php foreach ($errors as $er): ?><div class="alert alert-danger py-2"><?= e($er) ?></div><?php endforeach; ?>
    <?php if ($isSelf): ?><div class="alert alert-info py-2">Ini akun Anda sendiri: role dan status tidak dapat diubah.</div><?php endif; ?>
    <form method="post" autocomplete="off">
        <?= csrf_field() ?>
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Nama lengkap *</label>
                <input name="full_name" class="form-control" maxlength="100" value="<?= e($f['full_name']) ?>" required></div>
            <div class="col-md-6"><label class="form-label">Email *</label>
                <input type="email" name="email" class="form-control" maxlength="150" value="<?= e($f['email']) ?>" required></div>
            <div class="col-md-4"><label class="form-label">Role *</label>
                <select name="role" id="roleSel" class="form-select" <?= $isSelf ? 'disabled' : '' ?>>
                    <?php foreach (['admin', 'pelatih', 'atlet'] as $r): ?><option value="<?= e($r) ?>" <?= $f['role'] === $r ? 'selected' : '' ?>><?= e(role_label($r)) ?></option><?php endforeach; ?>
                </select></div>
            <div class="col-md-4"><label class="form-label">Status *</label>
                <select name="status" class="form-select" <?= $isSelf ? 'disabled' : '' ?>>
                    <option value="aktif" <?= $f['status'] === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                    <option value="nonaktif" <?= $f['status'] === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                </select></div>
            <div class="col-md-4" id="athleteBox"><label class="form-label">Hubungkan ke data atlet</label>
                <select name="athlete_id" class="form-select"><option value="">- belum terhubung -</option>
                    <?php foreach ($athletes as $a): ?><option value="<?= (int) $a['id'] ?>" <?= $f['athlete_id'] === (string) $a['id'] ? 'selected' : '' ?>><?= e($a['full_name'] . ' (' . $a['sport'] . ')') ?></option><?php endforeach; ?>
                </select>
                <div class="form-text">Hanya untuk role Atlet.</div></div>
            <div class="col-md-6"><label class="form-label">Password <?= $editing ? '<small class="text-muted">(kosongkan jika tidak diubah)</small>' : '*' ?></label>
                <input type="password" name="password" class="form-control" minlength="8" <?= $editing ? '' : 'required' ?>></div>
        </div>
        <div class="mt-4 d-flex gap-2">
            <button class="btn btn-primary"><i class="bi bi-save"></i> Simpan</button>
            <a href="<?= e(url('users/index.php')) ?>" class="btn btn-light">Batal</a>
        </div>
    </form>
</div></div>
<script>
(function () {
    var sel = document.getElementById('roleSel'), box = document.getElementById('athleteBox');
    function toggle() { box.style.display = sel.value === 'atlet' ? '' : 'none'; }
    sel.addEventListener('change', toggle); toggle();
})();
</script>
<?php include ROOT_PATH . '/components/footer.php';
