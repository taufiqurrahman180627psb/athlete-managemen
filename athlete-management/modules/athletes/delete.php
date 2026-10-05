<?php
/** Hapus atlet. HANYA ADMIN, hanya lewat POST + CSRF. */
require_once __DIR__ . '/../../middleware/role.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    go('athletes/index.php');
}
require_csrf();

$id = post_id();
if ($id <= 0) {
    flash('danger', 'ID tidak valid.');
    go('athletes/index.php');
}
$pdo = db();
$st = $pdo->prepare('SELECT photo FROM athletes WHERE id = ?');
$st->execute([$id]);
$row = $st->fetch();
if (!$row) {
    flash('danger', 'Data atlet tidak ditemukan.');
    go('athletes/index.php');
}
try {
    $pdo->prepare('DELETE FROM athletes WHERE id = ?')->execute([$id]);   // prestasi & tes ikut terhapus (CASCADE)
    delete_photo($row['photo']);
    flash('success', 'Data atlet berhasil dihapus.');
} catch (PDOException $ex) {
    error_log('Hapus atlet gagal: ' . $ex->getMessage());
    flash('danger', 'Gagal menghapus data atlet.');
}
go('athletes/index.php');
