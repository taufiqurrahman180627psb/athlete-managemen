<?php
/**
 * auth/register.php
 * Registrasi publik SELALU membuat role 'atlet' tanpa athlete_id.
 * Admin yang kemudian menghubungkan akun ke data atlet (Pengguna > Edit).
 * Admin/Pelatih hanya dapat dibuat oleh Admin lewat menu Pengguna.
 */
require_once __DIR__ . '/../middleware/auth.php';

if (!empty($_SESSION['user_id'])) {
    redirect(home_path());
}

$errors = [];
$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $name  = post_str('full_name');
    $email = strtolower(post_str('email'));
    $pass  = (string) ($_POST['password'] ?? '');
    $conf  = (string) ($_POST['confirm_password'] ?? '');

    if ($name === '' || mb_strlen($name) > 100) {
        $errors[] = 'Nama lengkap wajib diisi (maks. 100 karakter).';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
        $errors[] = 'Format email tidak valid.';
    }
    if ($err = validate_password($pass)) {
        $errors[] = $err;
    }
    if ($pass !== $conf) {
        $errors[] = 'Konfirmasi password tidak sama.';
    }

    if (!$errors) {
        try {
            $chk = db()->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            $chk->execute([$email]);
            if ($chk->fetch()) {
                $errors[] = 'Email sudah terdaftar.';
            } else {
                db()->prepare("INSERT INTO users (full_name, email, password, role, athlete_id, status) VALUES (?, ?, ?, 'atlet', NULL, 'aktif')")
                    ->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT)]);
                redirect('/auth/login.php?registered=1');
            }
        } catch (PDOException $ex) {
            error_log('Register gagal: ' . $ex->getMessage());
            $errors[] = 'Registrasi gagal. Email mungkin sudah terdaftar.';
        }
    }
}

$pageTitle = 'Daftar';
$bare = true;
include __DIR__ . '/../components/header.php';
?>
<div class="card auth-card shadow">
    <div class="card-body p-4">
        <h1 class="h4 text-center mb-3">Daftar Akun Atlet</h1>
        <?php foreach ($errors as $er): ?><div class="alert alert-danger py-2"><?= e($er) ?></div><?php endforeach; ?>
        <form method="post" autocomplete="off">
            <?= csrf_field() ?>
            <div class="mb-3"><label class="form-label">Nama lengkap</label>
                <input name="full_name" class="form-control" value="<?= e($name) ?>" maxlength="100" required></div>
            <div class="mb-3"><label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="<?= e($email) ?>" maxlength="150" required></div>
            <div class="mb-3"><label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" minlength="8" required>
                <div class="form-text">Minimal 8 karakter, kombinasi huruf dan angka.</div></div>
            <div class="mb-3"><label class="form-label">Konfirmasi password</label>
                <input type="password" name="confirm_password" class="form-control" required></div>
            <button class="btn btn-primary w-100">Daftar</button>
        </form>
        <div class="text-center mt-3 small"><a href="<?= e(BASE_URL . '/auth/login.php') ?>">Sudah punya akun? Login</a></div>
    </div>
</div>
<?php include __DIR__ . '/../components/footer.php'; ?>
