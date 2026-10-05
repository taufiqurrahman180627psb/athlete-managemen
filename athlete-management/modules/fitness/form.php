<?php
/** Tambah / ubah hasil tes kebugaran. Admin & Pelatih. */
require_once __DIR__ . '/../../middleware/role.php';
requireStaff();

$pdo = db();
$id = get_id();
$editing = $id > 0;
$row = null;

if ($editing) {
    $st = $pdo->prepare('SELECT * FROM fitness_tests WHERE id = ? LIMIT 1');
    $st->execute([$id]);
    $row = $st->fetch();
    if (!$row) {
        flash('danger', 'Data tes tidak ditemukan.');
        go('fitness-tests/index.php');
    }
}

// kolom => [label, satuan, min, max, desimal (0 = bilangan bulat)]
$numFields = [
    'height'        => ['Tinggi badan', 'cm', 50, 250, 2],
    'weight'        => ['Berat badan', 'kg', 10, 300, 2],
    'push_up'       => ['Push-up', 'repetisi', 0, 500, 0],
    'sit_up'        => ['Sit-up', 'repetisi', 0, 500, 0],
    'sprint_20m'    => ['Sprint 20 m', 'detik', 1, 20, 2],
    'beep_test'     => ['Beep test', 'level', 0, 30, 1],
    'sit_and_reach' => ['Sit and reach', 'cm', -50, 100, 2],
    'agility'       => ['Agility', 'detik', 5, 60, 2],
    'vo2_max'       => ['VO2 Max', 'ml/kg/menit', 10, 100, 2],
];

$f = ['athlete_id' => $row ? (string) $row['athlete_id'] : (string) get_id('athlete_id'),
      'test_date' => $row['test_date'] ?? date('Y-m-d'), 'notes' => $row['notes'] ?? ''];
if ($f['athlete_id'] === '0') { $f['athlete_id'] = ''; }
foreach ($numFields as $k => $_) { $f[$k] = isset($row[$k]) ? (string) $row[$k] : ''; }

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    foreach ($f as $k => $_) { $f[$k] = post_str($k); }

    $aid = (int) $f['athlete_id'];
    $chk = $pdo->prepare('SELECT id FROM athletes WHERE id = ?');
    $chk->execute([$aid]);
    if ($aid <= 0 || !$chk->fetch()) { $errors[] = 'Atlet tidak valid.'; }
    if (!valid_date($f['test_date']) || $f['test_date'] > date('Y-m-d')) { $errors[] = 'Tanggal tes tidak valid (tidak boleh di masa depan).'; }
    if (mb_strlen($f['notes']) > 1000) { $errors[] = 'Catatan maksimal 1000 karakter.'; }

    $vals = [];
    foreach ($numFields as $k => [$label, $unit, $min, $max, $dec]) {
        $vals[$k] = $dec === 0
            ? parse_int_range($f[$k], (int) $min, (int) $max, $label, $errors)
            : parse_decimal($f[$k], (float) $min, (float) $max, $label, $errors, $dec);
    }

    if (!$errors) {
        $p = [$aid, $f['test_date']];
        foreach ($numFields as $k => $_) { $p[] = $vals[$k]; }
        $p[] = nn($f['notes']);
        try {
            if ($editing) {
                $p[] = $id;
                $pdo->prepare('UPDATE fitness_tests SET athlete_id=?, test_date=?, height=?, weight=?, push_up=?, sit_up=?, sprint_20m=?, beep_test=?, sit_and_reach=?, agility=?, vo2_max=?, notes=? WHERE id=?')->execute($p);
                flash('success', 'Hasil tes berhasil diperbarui.');
            } else {
                $pdo->prepare('INSERT INTO fitness_tests (athlete_id, test_date, height, weight, push_up, sit_up, sprint_20m, beep_test, sit_and_reach, agility, vo2_max, notes) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)')->execute($p);
                flash('success', 'Hasil tes berhasil ditambahkan.');
            }
            go('fitness-tests/index.php');
        } catch (PDOException $ex) {
            error_log('Simpan tes gagal: ' . $ex->getMessage());
            $errors[] = 'Gagal menyimpan data. Silakan coba lagi.';
        }
    }
}

$athletes = $pdo->query('SELECT id, full_name, sport FROM athletes ORDER BY full_name')->fetchAll();
$pageTitle = $editing ? 'Ubah Hasil Tes' : 'Tambah Hasil Tes';
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
            <div class="col-md-3"><label class="form-label">Tanggal tes *</label>
                <input type="date" name="test_date" class="form-control" max="<?= date('Y-m-d') ?>" value="<?= e($f['test_date']) ?>" required></div>
            <div class="w-100"></div>
            <?php foreach ($numFields as $k => [$label, $unit, $min, $max, $dec]): ?>
                <div class="col-6 col-md-3"><label class="form-label"><?= e($label) ?> <small class="text-muted">(<?= e($unit) ?>)</small></label>
                    <input type="text" inputmode="decimal" name="<?= e($k) ?>" class="form-control" value="<?= e($f[$k]) ?>" placeholder="<?= e($min . ' - ' . $max) ?>"></div>
            <?php endforeach; ?>
            <div class="col-12"><label class="form-label">Catatan</label>
                <textarea name="notes" class="form-control" rows="2" maxlength="1000"><?= e($f['notes']) ?></textarea></div>
        </div>
        <div class="mt-4 d-flex gap-2">
            <button class="btn btn-primary"><i class="bi bi-save"></i> Simpan</button>
            <a href="<?= e(url('fitness-tests/index.php')) ?>" class="btn btn-light">Batal</a>
        </div>
    </form>
</div></div>
<?php include ROOT_PATH . '/components/footer.php';
