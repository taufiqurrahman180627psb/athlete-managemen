<?php
/** Halaman tipis: cek role lalu memuat modul bersama (fitness/delete.php). */
require_once __DIR__ . '/../../middleware/role.php';
requireCoach();
require __DIR__ . '/../../modules/fitness/delete.php';
