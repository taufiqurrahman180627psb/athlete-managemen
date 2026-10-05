<?php
/** Tambah / ubah prestasi. Admin & Pelatih. */
require_once __DIR__ . '/../../middleware/role.php';
requireStaff();

$pdo = db();
$id = get_id();
$editing = $id > 0;
$row = null;

if ($editing) {
    $st = $pdo->prepare('SELECT * FROM achievements WHERE id = ? LIMIT 1');
    $st->execute([$id]);
    $row = $st->fetch();
    if (!$row) {
        flash('danger', 'Data prestasi tidak ditemukan.');
        go('achievements/index.php');
    }
}

$f = [
    'athlete_id' => $row ? (string) $row['athlete_id'] : (string) get_id('athlete_id'),
    'competition_year' => $row ? (string) $row['competition_year'] : date('Y'),
    'competition_name' => $row['competition_name'] ?? '',
    'category' => $row['category'] ?? '',
    'result' => $row['result'] ?? '',
    'rank' => isset($row['rank']) ? (string) $row['rank'] : '',
    'notes' => $row['notes'] ?? '',
];
if ($f['athlete_id'] === '0') { $f['athlete_id'] = ''; }

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    foreach ($f as $k => $_) { $f[$k] = post_str($k); }

    $aid = (int) $f['athlete_id'];
    $chk = $pdo->prepare('SELECT id FROM athletes WHERE id = ?');
    $chk->execute([$aid]);
    if ($aid <= 0 || !$chk->fetch()) { $errors[] = 'Atlet tidak valid.'; }

    $year = parse_int_range($f['competition_year'], 1990, (int) date('Y') + 1, 'Tahun', $errors);
    if ($year === null && !$errors) { $errors[] = 'Tahun wajib diisi.'; }
    if ($f['competition_name'] === '' || mb_strlen($f['competition_name']) > 150) { $errors[] = 'Nama kejuaraan wajib diisi (maks. 150 karakter).'; }
    if (mb_strlen($f['category']) > 100) { $errors[] = 'Kategori maksimal 100 karakter.'; }
    if ($f['result'] === '' || mb_strlen($f['result']) > 100) { $errors[] = 'Hasil wajib diisi (maks. 100 karakter).'; }
    $rank = parse_int_range($f['rank'], 1, 999, 'Peringkat', $errors);
    if (mb_strlen($f['notes']) > 1000) { $errors[] = 'Catatan maksimal 1000 karakter.'; }

    if (!$errors) {
        $p = [$aid, $year, $f['competition_name'], nn($f['category']), $f['result'], $rank, nn($f['notes'])];
        try {
            if ($editing) {
                $p[] = $id;
                $pdo->prepare('UPDATE achievements SET athlete_id=?, competition_year=?, competition_name=?, category=?, result=?, `rank`=?, notes=? WHERE id=?')->execute($p);
                flash('success', 'Prestasi berhasil diperbarui.');
            } else {
                $pdo->prepare('INSERT INTO achievements (athlete_id, competition_year, competition_name, category, result, `rank`, notes) VALUES (?,?,?,?,?,?,?)')->execute($p);
                flash('success', 'Prestasi berhasil ditambahkan.');
            }
            go('achievements/index.php');
        } catch (PDOException $ex) {
            error_log('Simpan prestasi gagal: ' . $ex->getMessage());
            $errors[] = 'Gagal menyimpan data. Silakan coba lagi.';
        }
    }
}

$athletes = $pdo->query('SELECT id, full_name, sport FROM athletes ORDER BY full_name')->fetchAll();
$pageTitle = $editing ? 'Ubah Prestasi' : 'Tambah Prestasi';
include ROOT_PATH . '/components/header.php';
?>
<div class="card shadow-sm"><div class="card-body">
    <?php foreach ($errors as $er): ?><div class="alert alert-danger py-2"><?= e($er) ?></div><?php endforeach; ?>
    <form method="post">
        <?= csrf_field() ?>
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Atlet *</label>
                <select name="athlete_id" class="form-select" required><option value="">- pilih atlet -</option>
                    <?php foreach ($athletes as $a): ?><option value="<?= (int) $a['id'] ?>" <?= $f['athlete_id'] === (string) $a['id'] ? 'selected' : '' ?>><?= e($a['full_name'] . ' (' . $a['sport'] . ')') ?></option><?php endforeach; ?>
                </select></div>
            <div class="col-md-2"><label class="form-label">Tahun *</label>
                <input type="number" name="competition_year" class="form-control" min="1990" max="<?= (int) date('Y') + 1 ?>" value="<?= e($f['competition_year']) ?>" required></div>
            <div class="col-md-4"><label class="form-label">Nama kejuaraan *</label>
                <input name="competition_name" class="form-control" maxlength="150" value="<?= e($f['competition_name']) ?>" required></div>
            <div class="col-md-4"><label class="form-label">Kategori</label>
                <input name="category" class="form-control" maxlength="100" value="<?= e($f['category']) ?>"></div>
            <div class="col-md-4"><label class="form-label">Hasil *</label>
                <input name="result" class="form-control" maxlength="100" placeholder="Juara 1" value="<?= e($f['result']) ?>" required></div>
            <div class="col-md-4"><label class="form-label">Peringkat</label>
                <input type="number" name="rank" class="form-control" min="1" max="999" value="<?= e($f['rank']) ?>"></div>
            <div class="col-12"><label class="form-label">Catatan</label>
                <textarea name="notes" class="form-control" rows="2" maxlength="1000"><?= e($f['notes']) ?></textarea></div>
        </div>
        <div class="mt-4 d-flex gap-2">
            <button class="btn btn-primary"><i class="bi bi-save"></i> Simpan</button>
            <a href="<?= e(url('achievements/index.php')) ?>" class="btn btn-light">Batal</a>
        </div>
    </form>
</div></div>
<?php include ROOT_PATH . '/components/footer.php';
