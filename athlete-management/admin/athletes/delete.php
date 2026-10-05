<?php
/** Halaman tipis: cek role lalu memuat modul bersama (athletes/delete.php). */
require_once __DIR__ . '/../../middleware/role.php';
requireAdmin();
require __DIR__ . '/../../modules/athletes/delete.php';
