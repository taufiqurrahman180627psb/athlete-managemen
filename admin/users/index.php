<?php
/** Halaman tipis: cek role lalu memuat modul bersama (users/index.php). */
require_once __DIR__ . '/../../middleware/role.php';
requireAdmin();
require __DIR__ . '/../../modules/users/index.php';
