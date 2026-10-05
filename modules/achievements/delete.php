<?php
/** Hapus prestasi. Admin & Pelatih, POST + CSRF. */
require_once __DIR__ . '/../../middleware/role.php';
requireStaff();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    go('achievements/index.php');
}
require_csrf();

$id = post_id();
if ($id <= 0) {
    flash('danger', 'ID tidak valid.');
    go('achievements/index.php');
}
try {
    $st = db()->prepare('DELETE FROM achievements WHERE id = ?');
    $st->execute([$id]);
    flash($st->rowCount() ? 'success' : 'warning', $st->rowCount() ? 'Prestasi berhasil dihapus.' : 'Data prestasi tidak ditemukan.');
} catch (PDOException $ex) {
    error_log('Hapus prestasi gagal: ' . $ex->getMessage());
    flash('danger', 'Gagal menghapus prestasi.');
}
go('achievements/index.php');
