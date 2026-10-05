<?php
/** index.php - pintu masuk: arahkan ke dashboard sesuai role atau ke halaman login. */
require_once __DIR__ . '/middleware/auth.php';

if (!empty($_SESSION['user_id'])) {
    redirect(home_path());
}
redirect('/auth/login.php');
