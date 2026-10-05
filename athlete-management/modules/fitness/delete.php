<?php
/** Hapus hasil tes. Admin & Pelatih, POST + CSRF. */
require_once __DIR__ . '/../../middleware/role.php';
requireStaff();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    go('fitness-tests/index.php');
}
require_csrf();

$id = post_id();
if ($id <= 0) {
    flash('danger', 'ID tidak valid.');
    go('fitness-tests/index.php');
}
try {
    $st = db()->prepare('DELETE FROM fitness_tests WHERE id = ?');
    $st->execute([$id]);
    flash($st->rowCount() ? 'success' : 'warning', $st->rowCount() ? 'Hasil tes berhasil dihapus.' : 'Data tes tidak ditemukan.');
} catch (PDOException $ex) {
    error_log('Hapus tes gagal: ' . $ex->getMessage());
    flash('danger', 'Gagal menghapus hasil tes.');
}
go('fitness-tests/index.php');
