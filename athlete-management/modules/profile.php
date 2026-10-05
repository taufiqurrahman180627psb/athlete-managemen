<?php
/** Profil akun + ganti password untuk Admin & Pelatih. */
require_once __DIR__ . '/../middleware/role.php';
requireStaff();

$pwErrors = [];
if (handle_password_change($pwErrors)) {
    redirect_self();
}
$pageTitle = 'Profil Saya';
include ROOT_PATH . '/components/header.php';
?>
<div class="row g-3">
    <div class="col-lg-6"><div class="card shadow-sm"><div class="card-header bg-white fw-semibold">Informasi Akun</div>
        <div class="card-body"><dl class="row mb-0 profile-dl">
            <dt class="col-sm-4">Nama</dt><dd class="col-sm-8"><?= e($_SESSION['full_name']) ?></dd>
            <dt class="col-sm-4">Email</dt><dd class="col-sm-8"><?= e($_SESSION['email']) ?></dd>
            <dt class="col-sm-4">Role</dt><dd class="col-sm-8"><?= role_badge($_SESSION['role']) ?></dd>
        </dl></div></div></div>
    <div class="col-lg-6"><?php include ROOT_PATH . '/components/change_password_card.php'; ?></div>
</div>
<?php include ROOT_PATH . '/components/footer.php';
