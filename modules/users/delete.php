<?php
/** Hapus pengguna. HANYA ADMIN, POST + CSRF. Tidak boleh menghapus akun sendiri. */
require_once __DIR__ . '/../../middleware/role.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    go('users/index.php');
}
require_csrf();

$id = post_id();
if ($id <= 0) {
    flash('danger', 'ID tidak valid.');
    go('users/index.php');
}
if ($id === (int) $_SESSION['user_id']) {
    flash('danger', 'Anda tidak dapat menghapus akun Anda sendiri.');
    go('users/index.php');
}
try {
    $st = db()->prepare('DELETE FROM users WHERE id = ?');
    $st->execute([$id]);
    flash($st->rowCount() ? 'success' : 'warning', $st->rowCount() ? 'Pengguna berhasil dihapus.' : 'Pengguna tidak ditemukan.');
} catch (PDOException $ex) {
    error_log('Hapus pengguna gagal: ' . $ex->getMessage());
    flash('danger', 'Gagal menghapus pengguna.');
}
go('users/index.php');
