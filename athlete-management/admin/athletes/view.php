<?php
/** Halaman tipis: cek role lalu memuat modul bersama (athletes/view.php). */
require_once __DIR__ . '/../../middleware/role.php';
requireAdmin();
require __DIR__ . '/../../modules/athletes/view.php';
