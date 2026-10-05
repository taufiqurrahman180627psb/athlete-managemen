<?php
/** Semua grafik perkembangan milik sendiri. */
require_once __DIR__ . '/../middleware/role.php';
requireAthlete();

$athlete = load_own_athlete();
$st = db()->prepare('SELECT * FROM fitness_tests WHERE athlete_id = ? ORDER BY test_date ASC, id ASC');
$st->execute([(int) $athlete['id']]);
$tests = $st->fetchAll();

$pageTitle = 'Perkembangan';
$useCharts = true;
include ROOT_PATH . '/components/header.php';
include ROOT_PATH . '/components/progress_charts.php';
include ROOT_PATH . '/components/footer.php';
