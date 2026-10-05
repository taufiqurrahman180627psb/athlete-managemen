<?php
/** Tambah / ubah atlet + upload foto. Admin & Pelatih. */
require_once __DIR__ . '/../../middleware/role.php';
requireStaff();

$pdo = db();
$id = get_id();
$editing = $id > 0;
$athlete = null;

if ($editing) {
    $st = $pdo->prepare('SELECT * FROM athletes WHERE id = ? LIMIT 1');
    $st->execute([$id]);
    $athlete = $st->fetch();
    if (!$athlete) {
        flash('danger', 'Data atlet tidak ditemukan.');
        go('athletes/index.php');
    }
}

$fields = ['full_name', 'gender', 'birth_place', 'birth_date', 'address', 'phone', 'school', 'class', 'sport', 'position', 'jersey_number', 'status', 'notes'];
$f = array_fill_keys($fields, '');
$f['status'] = 'aktif';
if ($athlete) {
    foreach ($fields as $k) { $f[$k] = (string) ($athlete[$k] ?? ''); }
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    foreach ($fields as $k) { $f[$k] = post_str($k); }

    if ($f['full_name'] === '' || mb_strlen($f['full_name']) > 100) { $errors[] = 'Nama lengkap wajib diisi (maks. 100 karakter).'; }
    if (!in_array($f['gender'], ['Laki-laki', 'Perempuan'], true)) { $errors[] = 'Jenis kelamin tidak valid.'; }
    if (!valid_date($f['birth_date']) || $f['birth_date'] > date('Y-m-d')) { $errors[] = 'Tanggal lahir tidak valid.'; }
    if (mb_strlen($f['birth_place']) > 100) { $errors[] = 'Tempat lahir maksimal 100 karakter.'; }
    if ($f['phone'] !== '' && !preg_match('/^[0-9+\-\s()]{6,20}$/', $f['phone'])) { $errors[] = 'Nomor telepon tidak valid.'; }
    if (mb_strlen($f['school']) > 150) { $errors[] = 'Nama sekolah maksimal 150 karakter.'; }
    if (mb_strlen($f['class']) > 20) { $errors[] = 'Kelas maksimal 20 karakter.'; }
    if ($f['sport'] === '' || mb_strlen($f['sport']) > 50) { $errors[] = 'Cabang olahraga wajib diisi (maks. 50 karakter).'; }
    if (mb_strlen($f['position']) > 50) { $errors[] = 'Posisi maksimal 50 karakter.'; }
    $jersey = parse_int_range($f['jersey_number'], 0, 99, 'Nomor punggung', $errors);
    if (!in_array($f['status'], ['aktif', 'nonaktif'], true)) { $errors[] = 'Status tidak valid.'; }
    if (mb_strlen($f['address']) > 1000 || mb_strlen($f['notes']) > 1000) { $errors[] = 'Alamat/catatan maksimal 1000 karakter.'; }

    $uploadErr = null;
    $newPhoto = handle_photo_upload('photo', $uploadErr);
    if ($uploadErr) { $errors[] = $uploadErr; }

    if (!$errors) {
        $p = [
            ':full_name' => $f['full_name'], ':gender' => $f['gender'], ':birth_place' => nn($f['birth_place']),
            ':birth_date' => $f['birth_date'], ':address' => nn($f['address']), ':phone' => nn($f['phone']),
            ':school' => nn($f['school']), ':class' => nn($f['class']), ':sport' => $f['sport'],
            ':position' => nn($f['position']), ':jersey' => $jersey, ':status' => $f['status'], ':notes' => nn($f['notes']),
        ];
        try {
            if ($editing) {
                $p[':photo'] = $newPhoto ?? $athlete['photo'];
                $p[':id'] = $id;
                $pdo->prepare('UPDATE athletes SET full_name=:full_name, gender=:gender, birth_place=:birth_place, birth_date=:birth_date,
                    address=:address, phone=:phone, school=:school, `class`=:class, sport=:sport, position=:position,
                    jersey_number=:jersey, status=:status, notes=:notes, photo=:photo WHERE id=:id')->execute($p);
                if ($newPhoto && $athlete['photo']) { delete_photo($athlete['photo']); }
                flash('success', 'Data atlet berhasil diperbarui.');
            } else {
                $p[':photo'] = $newPhoto;
                $pdo->prepare('INSERT INTO athletes (full_name, gender, birth_place, birth_date, address, phone, school, `class`, sport, position, jersey_number, status, notes, photo)
                    VALUES (:full_name, :gender, :birth_place, :birth_date, :address, :phone, :school, :class, :sport, :position, :jersey, :status, :notes, :photo)')->execute($p);
                flash('success', 'Atlet baru berhasil ditambahkan.');
            }
            go('athletes/index.php');
        } catch (PDOException $ex) {
            error_log('Simpan atlet gagal: ' . $ex->getMessage());
            delete_photo($newPhoto);
            $errors[] = 'Gagal menyimpan data. Silakan coba lagi.';
        }
    } else {
        delete_photo($newPhoto);
    }
}

$sportOptions = $pdo->query('SELECT name FROM sports UNION SELECT DISTINCT sport FROM athletes ORDER BY 1')->fetchAll(PDO::FETCH_COLUMN);
$pageTitle = $editing ? 'Ubah Atlet' : 'Tambah Atlet';
include ROOT_PATH . '/components/header.php';
?>
<div class="card shadow-sm">
    <div class="card-body">
        <?php foreach ($errors as $er): ?><div class="alert alert-danger py-2"><?= e($er) ?></div><?php endforeach; ?>
        <form method="post" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Nama lengkap *</label>
                    <input name="full_name" class="form-control" maxlength="100" value="<?= e($f['full_name']) ?>" required></div>
                <div class="col-md-3"><label class="form-label">Jenis kelamin *</label>
                    <select name="gender" class="form-select" required><option value="">- pilih -</option>
                        <?php foreach (['Laki-laki', 'Perempuan'] as $g): ?><option <?= $f['gender'] === $g ? 'selected' : '' ?>><?= e($g) ?></option><?php endforeach; ?>
                    </select></div>
                <div class="col-md-3"><label class="form-label">Status *</label>
                    <select name="status" class="form-select">
                        <option value="aktif" <?= $f['status'] === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                        <option value="nonaktif" <?= $f['status'] === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                    </select></div>
                <div class="col-md-4"><label class="form-label">Tempat lahir</label>
                    <input name="birth_place" class="form-control" maxlength="100" value="<?= e($f['birth_place']) ?>"></div>
                <div class="col-md-4"><label class="form-label">Tanggal lahir *</label>
                    <input type="date" name="birth_date" class="form-control" max="<?= date('Y-m-d') ?>" value="<?= e($f['birth_date']) ?>" required></div>
                <div class="col-md-4"><label class="form-label">Telepon</label>
                    <input name="phone" class="form-control" maxlength="20" value="<?= e($f['phone']) ?>"></div>
                <div class="col-12"><label class="form-label">Alamat</label>
                    <textarea name="address" class="form-control" rows="2" maxlength="1000"><?= e($f['address']) ?></textarea></div>
                <div class="col-md-6"><label class="form-label">Sekolah</label>
                    <input name="school" class="form-control" maxlength="150" value="<?= e($f['school']) ?>"></div>
                <div class="col-md-2"><label class="form-label">Kelas</label>
                    <input name="class" class="form-control" maxlength="20" value="<?= e($f['class']) ?>"></div>
                <div class="col-md-4"><label class="form-label">Cabang olahraga *</label>
                    <input name="sport" list="sportList" class="form-control" maxlength="50" value="<?= e($f['sport']) ?>" required>
                    <datalist id="sportList"><?php foreach ($sportOptions as $s): ?><option value="<?= e($s) ?>"><?php endforeach; ?></datalist></div>
                <div class="col-md-6"><label class="form-label">Posisi</label>
                    <input name="position" class="form-control" maxlength="50" value="<?= e($f['position']) ?>"></div>
                <div class="col-md-2"><label class="form-label">No. punggung</label>
                    <input type="number" name="jersey_number" class="form-control" min="0" max="99" value="<?= e($f['jersey_number']) ?>"></div>
                <div class="col-md-4"><label class="form-label">Foto (JPG/PNG, maks. <?= (int) setting('max_photo_mb', '2') ?> MB)</label>
                    <input type="file" name="photo" class="form-control" accept=".jpg,.jpeg,.png,image/jpeg,image/png"></div>
                <?php if ($athlete && $athlete['photo']): ?>
                    <div class="col-12 d-flex align-items-center gap-2"><?= avatar_html($athlete['full_name'], $athlete['photo'], 56) ?>
                        <span class="text-muted small">Foto saat ini. Unggah file baru untuk menggantinya.</span></div>
                <?php endif; ?>
                <div class="col-12"><label class="form-label">Catatan</label>
                    <textarea name="notes" class="form-control" rows="2" maxlength="1000"><?= e($f['notes']) ?></textarea></div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button class="btn btn-primary"><i class="bi bi-save"></i> Simpan</button>
                <a href="<?= e(url('athletes/index.php')) ?>" class="btn btn-light">Batal</a>
            </div>
        </form>
    </div>
</div>
<?php include ROOT_PATH . '/components/footer.php';
