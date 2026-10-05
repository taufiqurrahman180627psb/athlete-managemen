<?php
/** Halaman tipis: cek role lalu memuat modul bersama (athletes/form.php). */
require_once __DIR__ . '/../../middleware/role.php';
requireCoach();
require __DIR__ . '/../../modules/athletes/form.php';
