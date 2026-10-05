<?php
/**
 * middleware/role.php
 * Otorisasi RBAC. Panggil di AWAL setiap halaman terproteksi.
 * Contoh: requireAdmin();  requireRole(['admin','pelatih']);
 */
require_once __DIR__ . '/auth.php';

function requireRole($roles): void
{
    requireLogin();
    if (!in_array($_SESSION['role'] ?? '', (array) $roles, true)) {
        redirect('/unauthorized.php');
    }
}

function requireAdmin(): void   { requireRole('admin'); }
function requireCoach(): void   { requireRole('pelatih'); }
function requireAthlete(): void { requireRole('atlet'); }
function requireStaff(): void   { requireRole(['admin', 'pelatih']); }   // admin atau pelatih

function isAdmin(): bool { return ($_SESSION['role'] ?? '') === 'admin'; }
function isCoach(): bool { return ($_SESSION['role'] ?? '') === 'pelatih'; }
