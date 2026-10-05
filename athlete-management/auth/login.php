<?php
/**
 * auth/login.php
 * Alur: validasi CSRF -> cari user (prepared) -> password_verify() -> session -> redirect per role.
 * Anti brute-force sederhana: 5x salah = kunci 10 menit (berbasis session).
 */
require_once __DIR__ . '/../middleware/auth.php';

if (!empty($_SESSION['user_id'])) {
    redirect(home_path());
}

$errors = [];
$email  = '';
$locked = ($_SESSION['login_lock_until'] ?? 0) > time();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    if ($locked) {
        $errors[] = 'Terlalu banyak percobaan gagal. Coba lagi dalam ' . max(1, (int) ceil(($_SESSION['login_lock_until'] - time()) / 60)) . ' menit.';
    } else {
        $email = strtolower(post_str('email'));
        $pass  = (string) ($_POST['password'] ?? '');

        if ($email === '' || $pass === '') {
            $errors[] = 'Email dan password wajib diisi.';
        } else {
            $st = db()->prepare('SELECT id, full_name, email, password, role, athlete_id, status FROM users WHERE email = ? LIMIT 1');
            $st->execute([$email]);
            $u = $st->fetch(PDO::FETCH_ASSOC) ?: null;

            // Selalu menjalankan password_verify (hash tiruan bila user tak ada) agar waktu respons seragam.
            $dummy = '$2y$10$LvFiPOSonSDyhYJgfolON.7ahAx8c0cKzx3kaANKtHpSLL84EIFDe';
            $ok = $u !== null && isset($u['password']) && password_verify($pass, (string) $u['password']);

            if ($ok && $u['status'] === 'aktif') {
                if (password_needs_rehash($u['password'], PASSWORD_DEFAULT)) {
                    db()->prepare('UPDATE users SET password = ? WHERE id = ?')
                        ->execute([password_hash($pass, PASSWORD_DEFAULT), $u['id']]);
                }
                // Login berhasil: reset penghitung percobaan gagal.
                unset($_SESSION['login_attempts'], $_SESSION['login_lock_until']);
                login_user($u);
                redirect(home_path());
            } elseif ($ok) {
                $errors[] = 'Akun Anda nonaktif. Hubungi administrator.';
            } else {
                $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
                if ($_SESSION['login_attempts'] >= 5) {
                    $_SESSION['login_lock_until'] = time() + 600;
                    $_SESSION['login_attempts']   = 0;
                }
                $errors[] = 'Email atau password salah.';
            }
        }
    }
}

$pageTitle = 'Login';
$bare = true;
include __DIR__ . '/../components/header.php';
?>
<div class="card auth-card shadow">
    <div class="card-body p-4">
        <div class="text-center mb-3">
            <div class="display-5 text-primary"><i class="bi bi-trophy-fill"></i></div>
            <h1 class="h4 mb-0"><?= e(setting('app_name', 'Sistem Manajemen Atlet')) ?></h1>
            <div class="text-muted small">Silakan login untuk melanjutkan</div>
        </div>

        <?php if (isset($_GET['timeout'])): ?><div class="alert alert-warning py-2">Sesi berakhir karena tidak aktif. Silakan login kembali.</div><?php endif; ?>
        <?php if (isset($_GET['disabled'])): ?><div class="alert alert-warning py-2">Akun tidak aktif atau sudah dihapus.</div><?php endif; ?>
        <?php if (isset($_GET['registered'])): ?><div class="alert alert-success py-2">Registrasi berhasil. Silakan login. Admin akan menghubungkan akun Anda dengan data atlet.</div><?php endif; ?>
        <?php if (isset($_GET['bye'])): ?><div class="alert alert-info py-2">Anda telah logout.</div><?php endif; ?>
        <?php foreach ($errors as $er): ?><div class="alert alert-danger py-2"><?= e($er) ?></div><?php endforeach; ?>

        <form method="post" autocomplete="on">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="<?= e($email) ?>" required autofocus>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button class="btn btn-primary w-100">Login</button>
        </form>
        <div class="text-center mt-3 small">Belum punya akun? <a href="<?= e(BASE_URL . '/auth/register.php') ?>">Daftar sebagai atlet</a></div>
    </div>
</div>
<?php include __DIR__ . '/../components/footer.php'; ?>
