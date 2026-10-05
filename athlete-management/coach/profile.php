<?php
/** Halaman tipis: cek role lalu memuat modul bersama (profile.php). */
require_once __DIR__ . '/../middleware/role.php';
requireCoach();
require __DIR__ . '/../modules/profile.php';
