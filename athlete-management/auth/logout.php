<?php
/** auth/logout.php - logout hanya lewat POST + token CSRF (mencegah logout paksa lewat link/gambar). */
require_once __DIR__ . '/../middleware/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    logout_session();
    redirect('/auth/login.php?bye=1');
}
redirect(empty($_SESSION['user_id']) ? '/auth/login.php' : home_path());
