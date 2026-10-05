<?php
/** Halaman tipis: cek role lalu memuat modul bersama (reports/index.php). */
require_once __DIR__ . '/../../middleware/role.php';
requireAdmin();
require __DIR__ . '/../../modules/reports/index.php';
