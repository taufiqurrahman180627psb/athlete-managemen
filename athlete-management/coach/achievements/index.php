<?php
/** Halaman tipis: cek role lalu memuat modul bersama (achievements/index.php). */
require_once __DIR__ . '/../../middleware/role.php';
requireCoach();
require __DIR__ . '/../../modules/achievements/index.php';
