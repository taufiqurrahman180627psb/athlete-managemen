<?php
/** Halaman tipis: cek role lalu memuat modul bersama (fitness/form.php). */
require_once __DIR__ . '/../../middleware/role.php';
requireAdmin();
require __DIR__ . '/../../modules/fitness/form.php';
