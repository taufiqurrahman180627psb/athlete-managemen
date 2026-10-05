<?php
/**
 * middleware/auth.php
 * Inti aplikasi: session aman, helper umum, CSRF, flash message, upload foto,
 * paginasi, dan requireLogin(). Di-include oleh SEMUA halaman.
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name('ATHLETESESSID');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']),
    ]);
    session_start();
}

header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');

/* =====================  OUTPUT & URL  ===================== */

function e($v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function js_json($v): string
{
    return json_encode($v, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
}

function redirect(string $path): void
{
    header('Location: ' . BASE_URL . $path);
    exit;
}

function redirect_self(): void
{
    header('Location: ' . ($_SERVER['REQUEST_URI'] ?? (BASE_URL . '/')));
    exit;
}

/** Nama folder per role: admin -> admin, pelatih -> coach, atlet -> athlete */
function role_dir(?string $role = null): string
{
    $role = $role ?? ($_SESSION['role'] ?? '');
    return ['admin' => 'admin', 'pelatih' => 'coach', 'atlet' => 'athlete'][$role] ?? '';
}

function home_path(): string
{
    return '/' . role_dir() . '/dashboard.php';
}

/** URL halaman di folder role yang sedang login. Contoh: url('athletes/index.php') */
function url(string $path = ''): string
{
    return BASE_URL . '/' . role_dir() . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    return BASE_URL . '/assets/' . ltrim($path, '/');
}

/** Redirect ke halaman di folder role sendiri. */
function go(string $path): void
{
    redirect('/' . role_dir() . '/' . ltrim($path, '/'));
}

/* =====================  INPUT  ===================== */

function get_str(string $key): string
{
    $v = $_GET[$key] ?? '';
    return is_string($v) ? trim($v) : '';
}

function post_str(string $key): string
{
    $v = $_POST[$key] ?? '';
    return is_string($v) ? trim($v) : '';
}

function get_id(string $key = 'id'): int
{
    $v = $_GET[$key] ?? '';
    return (is_string($v) && ctype_digit($v)) ? (int) $v : 0;
}

function post_id(string $key = 'id'): int
{
    $v = $_POST[$key] ?? '';
    return (is_string($v) && ctype_digit($v)) ? (int) $v : 0;
}

/** String kosong -> NULL (untuk kolom boleh NULL). */
function nn($v)
{
    return ($v === '' || $v === null) ? null : $v;
}

function valid_date(string $s): bool
{
    $d = DateTime::createFromFormat('Y-m-d', $s);
    return $d && $d->format('Y-m-d') === $s;
}

function parse_decimal($v, float $min, float $max, string $label, array &$errors, int $dec = 2): ?float
{
    $v = str_replace(',', '.', trim((string) $v));
    if ($v === '') {
        return null;
    }
    if (!is_numeric($v)) {
        $errors[] = "$label harus berupa angka.";
        return null;
    }
    $n = (float) $v;
    if ($n < $min || $n > $max) {
        $errors[] = "$label harus antara $min dan $max.";
        return null;
    }
    return round($n, $dec);
}

function parse_int_range($v, int $min, int $max, string $label, array &$errors): ?int
{
    $v = trim((string) $v);
    if ($v === '') {
        return null;
    }
    if (!ctype_digit($v) || (int) $v < $min || (int) $v > $max) {
        $errors[] = "$label harus bilangan bulat $min - $max.";
        return null;
    }
    return (int) $v;
}

function validate_password(string $p): ?string
{
    if (strlen($p) < 8) {
        return 'Password minimal 8 karakter.';
    }
    if (strlen($p) > 72) {
        return 'Password maksimal 72 karakter.';
    }
    if (!preg_match('/[A-Za-z]/', $p) || !preg_match('/\d/', $p)) {
        return 'Password harus mengandung huruf dan angka.';
    }
    return null;
}

/* =====================  FLASH & CSRF  ===================== */

function flash(string $type, string $msg): void
{
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg];
}

function flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_verify(): bool
{
    $t = $_POST['csrf_token'] ?? '';
    return is_string($t) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $t);
}

function require_csrf(): void
{
    if (!csrf_verify()) {
        http_response_code(419);
        exit('Token keamanan (CSRF) tidak valid atau sudah kedaluwarsa. Kembali, muat ulang halaman, lalu coba lagi.');
    }
}

/* =====================  SESSION & LOGIN  ===================== */

function logout_session(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function login_user(array $u): void
{
    session_regenerate_id(true);              // cegah session fixation
    $_SESSION['user_id']       = (int) $u['id'];
    $_SESSION['full_name']     = $u['full_name'];
    $_SESSION['email']         = $u['email'];
    $_SESSION['role']          = $u['role'];
    $_SESSION['athlete_id']    = $u['athlete_id'] !== null ? (int) $u['athlete_id'] : null;
    $_SESSION['ua']            = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '');
    $_SESSION['last_activity'] = time();
    unset($_SESSION['csrf'], $_SESSION['login_attempts'], $_SESSION['login_lock_until']);
}

/**
 * Wajib login. Setiap request: cek timeout, cek user-agent, dan SINKRONKAN role/status
 * dari database (akun yang dinonaktifkan / diubah role langsung berlaku).
 */
function requireLogin(): void
{
    static $checked = false;

    if (empty($_SESSION['user_id'])) {
        redirect('/auth/login.php');
    }
    if ($checked) {
        return;
    }

    if (($_SESSION['ua'] ?? '') !== hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '')) {
        logout_session();
        redirect('/auth/login.php');
    }
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
        logout_session();
        redirect('/auth/login.php?timeout=1');
    }
    $_SESSION['last_activity'] = time();

    $st = db()->prepare('SELECT id, full_name, email, role, athlete_id, status FROM users WHERE id = ? LIMIT 1');
    $st->execute([(int) $_SESSION['user_id']]);
    $u = $st->fetch();
    if (!$u || $u['status'] !== 'aktif') {
        logout_session();
        redirect('/auth/login.php?disabled=1');
    }
    $_SESSION['full_name']  = $u['full_name'];
    $_SESSION['email']      = $u['email'];
    $_SESSION['role']       = $u['role'];
    $_SESSION['athlete_id'] = $u['athlete_id'] !== null ? (int) $u['athlete_id'] : null;
    $checked = true;
}

/* =====================  TAMPILAN HELPER  ===================== */

function setting(string $key, string $default = ''): string
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        try {
            foreach (db()->query('SELECT setting_key, setting_value FROM settings') as $r) {
                $cache[$r['setting_key']] = $r['setting_value'];
            }
        } catch (PDOException $ex) {
        }
    }
    return $cache[$key] ?? $default;
}

function fmt_date(?string $d): string
{
    if (!$d) {
        return '-';
    }
    $t = strtotime($d);
    return $t ? date('d/m/Y', $t) : '-';
}

function age_from(?string $birth): ?int
{
    if (!$birth) {
        return null;
    }
    try {
        return (new DateTime($birth))->diff(new DateTime('today'))->y;
    } catch (Exception $ex) {
        return null;
    }
}

function bmi(?float $h, ?float $w): ?float
{
    if (!$h || !$w || $h <= 0) {
        return null;
    }
    return round($w / (($h / 100) ** 2), 1);
}

function role_label(string $r): string
{
    return ['admin' => 'Admin', 'pelatih' => 'Pelatih', 'atlet' => 'Atlet'][$r] ?? $r;
}

function status_badge(string $s): string
{
    return '<span class="badge bg-' . ($s === 'aktif' ? 'success' : 'secondary') . '">' . e(ucfirst($s)) . '</span>';
}

function role_badge(string $r): string
{
    $c = ['admin' => 'danger', 'pelatih' => 'primary', 'atlet' => 'success'][$r] ?? 'secondary';
    return '<span class="badge bg-' . $c . '">' . e(role_label($r)) . '</span>';
}

function avatar_html(string $name, ?string $photo, int $size = 40): string
{
    $s = (int) $size;
    if ($photo && preg_match('/^[a-f0-9]{32}\.(jpg|png)$/', $photo)) {
        return '<img src="' . e(UPLOAD_URL . $photo) . '" alt="' . e($name) . '" class="avatar" style="width:' . $s . 'px;height:' . $s . 'px">';
    }
    $init = mb_strtoupper(mb_substr(trim($name), 0, 1));
    return '<div class="avatar avatar-text" style="width:' . $s . 'px;height:' . $s . 'px;font-size:' . round($s / 2.3) . 'px">' . e($init) . '</div>';
}

/* =====================  PAGINASI  ===================== */

function paginate(int $total, int $page): array
{
    $perPage = max(5, min(100, (int) setting('records_per_page', '10')));
    $pages   = max(1, (int) ceil($total / $perPage));
    $page    = min(max(1, $page), $pages);
    return ['total' => $total, 'page' => $page, 'pages' => $pages, 'perPage' => $perPage, 'offset' => ($page - 1) * $perPage];
}

function pagination_html(array $p): string
{
    if ($p['pages'] <= 1) {
        return '';
    }
    $q   = $_GET;
    $out = '<nav><ul class="pagination pagination-sm mb-0 flex-wrap">';
    for ($i = 1; $i <= $p['pages']; $i++) {
        if ($i !== 1 && $i !== $p['pages'] && abs($i - $p['page']) > 2) {
            if (abs($i - $p['page']) === 3) {
                $out .= '<li class="page-item disabled"><span class="page-link">…</span></li>';
            }
            continue;
        }
        $q['page'] = $i;
        $out .= '<li class="page-item' . ($i === $p['page'] ? ' active' : '') . '"><a class="page-link" href="?' . e(http_build_query($q)) . '">' . $i . '</a></li>';
    }
    return $out . '</ul></nav>';
}

/* =====================  UPLOAD FOTO  ===================== */

/** Return nama file baru, atau null (cek $error untuk pesan). Hanya JPG/PNG, MIME & isi gambar divalidasi. */
function handle_photo_upload(string $field, ?string &$error = null): ?string
{
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $f = $_FILES[$field];
    if ($f['error'] !== UPLOAD_ERR_OK) {
        $error = 'Upload foto gagal (kode ' . (int) $f['error'] . '). Periksa ukuran file.';
        return null;
    }
    $maxMb = max(1, min(10, (int) setting('max_photo_mb', '2')));
    if ($f['size'] > $maxMb * 1024 * 1024) {
        $error = "Ukuran foto maksimal $maxMb MB.";
        return null;
    }
    $mime    = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
    if (!isset($allowed[$mime]) || @getimagesize($f['tmp_name']) === false) {
        $error = 'Foto harus berformat JPG atau PNG yang valid.';
        return null;
    }
    if (!is_dir(UPLOAD_DIR) && !mkdir(UPLOAD_DIR, 0755, true)) {
        $error = 'Folder uploads/athletes tidak dapat dibuat.';
        return null;
    }
    $name = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];   // nama acak, bukan nama asli
    if (!move_uploaded_file($f['tmp_name'], UPLOAD_DIR . $name)) {
        $error = 'Gagal menyimpan foto ke server.';
        return null;
    }
    return $name;
}

function delete_photo(?string $name): void
{
    if ($name && preg_match('/^[a-f0-9]{32}\.(jpg|png)$/', $name)) {
        @unlink(UPLOAD_DIR . $name);
    }
}

/* =====================  GANTI PASSWORD (semua role)  ===================== */

function handle_password_change(array &$errors): bool
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || post_str('action') !== 'change_password') {
        return false;
    }
    require_csrf();
    $cur  = (string) ($_POST['current_password'] ?? '');
    $new  = (string) ($_POST['new_password'] ?? '');
    $conf = (string) ($_POST['confirm_password'] ?? '');

    $st = db()->prepare('SELECT password FROM users WHERE id = ?');
    $st->execute([(int) $_SESSION['user_id']]);
    $hash = (string) $st->fetchColumn();

    if (!password_verify($cur, $hash)) {
        $errors[] = 'Password saat ini salah.';
    }
    if ($err = validate_password($new)) {
        $errors[] = $err;
    }
    if ($new !== $conf) {
        $errors[] = 'Konfirmasi password tidak sama.';
    }
    if ($errors) {
        return false;
    }
    $up = db()->prepare('UPDATE users SET password = ? WHERE id = ?');
    $up->execute([password_hash($new, PASSWORD_DEFAULT), (int) $_SESSION['user_id']]);
    session_regenerate_id(true);
    flash('success', 'Password berhasil diubah.');
    return true;
}

/** Atlet: ambil data atlet MILIK SENDIRI dari SESSION (bukan dari URL). */
function load_own_athlete(): array
{
    $id = (int) ($_SESSION['athlete_id'] ?? 0);
    if ($id > 0) {
        $st = db()->prepare('SELECT * FROM athletes WHERE id = ? LIMIT 1');
        $st->execute([$id]);
        if ($a = $st->fetch()) {
            return $a;
        }
    }
    $pageTitle = 'Akun belum terhubung';
    include ROOT_PATH . '/components/header.php';
    echo '<div class="alert alert-warning"><h5 class="alert-heading">Akun belum terhubung dengan data atlet</h5>'
       . 'Hubungi Admin agar akun Anda dihubungkan dengan data atlet.</div>';
    include ROOT_PATH . '/components/footer.php';
    exit;
}
