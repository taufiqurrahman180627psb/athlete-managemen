<?php
/** Pengaturan sistem + daftar cabang olahraga. HANYA ADMIN. */
require_once __DIR__ . '/../middleware/role.php';
requireAdmin();

$pdo = db();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = post_str('action');

    if ($action === 'save_settings') {
        $app = post_str('app_name');
        $org = post_str('organization');
        $mb = parse_int_range(post_str('max_photo_mb'), 1, 10, 'Ukuran foto maksimal (MB)', $errors);
        $pp = parse_int_range(post_str('records_per_page'), 5, 100, 'Baris per halaman', $errors);
        if ($app === '' || mb_strlen($app) > 100) { $errors[] = 'Nama aplikasi wajib diisi (maks. 100 karakter).'; }
        if ($org === '' || mb_strlen($org) > 100) { $errors[] = 'Nama organisasi wajib diisi (maks. 100 karakter).'; }
        if ($mb === null || $pp === null) { $errors[] = 'Semua pengaturan wajib diisi.'; }
        if (!$errors) {
            $up = $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
            foreach (['app_name' => $app, 'organization' => $org, 'max_photo_mb' => (string) $mb, 'records_per_page' => (string) $pp] as $k => $v) {
                $up->execute([$k, $v]);
            }
            flash('success', 'Pengaturan disimpan.');
            redirect_self();
        }
    } elseif ($action === 'add_sport') {
        $name = post_str('sport_name');
        if ($name === '' || mb_strlen($name) > 50) {
            $errors[] = 'Nama cabang wajib diisi (maks. 50 karakter).';
        } else {
            $pdo->prepare('INSERT IGNORE INTO sports (name) VALUES (?)')->execute([$name]);
            flash('success', 'Cabang olahraga ditambahkan.');
            redirect_self();
        }
    } elseif ($action === 'delete_sport') {
        $pdo->prepare('DELETE FROM sports WHERE id = ?')->execute([post_id('sport_id')]);
        flash('success', 'Cabang olahraga dihapus dari daftar pilihan (data atlet tidak berubah).');
        redirect_self();
    }
}

$sports = $pdo->query('SELECT id, name FROM sports ORDER BY name')->fetchAll();
$pageTitle = 'Pengaturan';
include ROOT_PATH . '/components/header.php';
?>
<?php foreach ($errors as $er): ?><div class="alert alert-danger py-2"><?= e($er) ?></div><?php endforeach; ?>
<div class="row g-3">
    <div class="col-lg-7"><div class="card shadow-sm"><div class="card-header bg-white fw-semibold">Pengaturan Umum</div>
        <div class="card-body"><form method="post">
            <?= csrf_field() ?><input type="hidden" name="action" value="save_settings">
            <div class="mb-3"><label class="form-label">Nama aplikasi</label>
                <input name="app_name" class="form-control" maxlength="100" value="<?= e(setting('app_name', 'Sistem Manajemen Atlet')) ?>" required></div>
            <div class="mb-3"><label class="form-label">Nama organisasi / klub</label>
                <input name="organization" class="form-control" maxlength="100" value="<?= e(setting('organization', '')) ?>" required></div>
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Ukuran foto maksimal (MB)</label>
                    <input type="number" name="max_photo_mb" class="form-control" min="1" max="10" value="<?= e(setting('max_photo_mb', '2')) ?>" required>
                    <div class="form-text">Batas php.ini (upload_max_filesize) juga berlaku.</div></div>
                <div class="col-md-6"><label class="form-label">Baris per halaman</label>
                    <input type="number" name="records_per_page" class="form-control" min="5" max="100" value="<?= e(setting('records_per_page', '10')) ?>" required></div>
            </div>
            <button class="btn btn-primary mt-3"><i class="bi bi-save"></i> Simpan</button>
        </form></div></div></div>

    <div class="col-lg-5"><div class="card shadow-sm"><div class="card-header bg-white fw-semibold">Daftar Cabang Olahraga</div>
        <div class="card-body">
            <form method="post" class="d-flex gap-2 mb-3">
                <?= csrf_field() ?><input type="hidden" name="action" value="add_sport">
                <input name="sport_name" class="form-control" maxlength="50" placeholder="Cabang baru" required>
                <button class="btn btn-outline-primary">Tambah</button>
            </form>
            <ul class="list-group">
                <?php foreach ($sports as $s): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center"><?= e($s['name']) ?>
                        <form method="post" class="m-0" onsubmit="return confirm('Hapus dari daftar pilihan?')">
                            <?= csrf_field() ?><input type="hidden" name="action" value="delete_sport"><input type="hidden" name="sport_id" value="<?= (int) $s['id'] ?>">
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-x-lg"></i></button></form></li>
                <?php endforeach; ?>
            </ul>
        </div></div></div>
</div>
<?php include ROOT_PATH . '/components/footer.php';
