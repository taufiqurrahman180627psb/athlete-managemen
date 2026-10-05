<?php
/** Profil pribadi atlet (hanya baca) + ganti password. */
require_once __DIR__ . '/../middleware/role.php';
requireAthlete();

$pwErrors = [];
if (handle_password_change($pwErrors)) {
    redirect_self();
}

$athlete = load_own_athlete();
$st = db()->prepare('SELECT * FROM fitness_tests WHERE athlete_id = ? ORDER BY test_date DESC, id DESC LIMIT 1');
$st->execute([(int) $athlete['id']]);
$latest = $st->fetch() ?: null;

$pageTitle = 'Profil Saya';
include ROOT_PATH . '/components/header.php';
include ROOT_PATH . '/components/athlete_profile_card.php';
?>
<div class="row mt-3"><div class="col-lg-6"><?php include ROOT_PATH . '/components/change_password_card.php'; ?></div></div>
<?php include ROOT_PATH . '/components/footer.php';
