<?php
/** Halaman tipis: cek role lalu memuat modul bersama (users/form.php). */
require_once __DIR__ . '/../../middleware/role.php';
requireAdmin();
require __DIR__ . '/../../modules/users/form.php';
