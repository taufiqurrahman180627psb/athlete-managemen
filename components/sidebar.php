<?php
/** components/sidebar.php - menu berbeda untuk tiap role. */
$role   = $_SESSION['role'] ?? '';
$script = $_SERVER['SCRIPT_NAME'] ?? '';

// [label, icon, path (relatif folder role), potongan URL untuk menandai menu aktif]
$staffCommon = [
    ['Dashboard',     'bi-speedometer2',          'dashboard.php',            '/dashboard.php'],
];
$menus = [
    'admin' => [
        ['Dashboard',     'bi-speedometer2',          'dashboard.php',            '/dashboard.php'],
        ['Pengguna',      'bi-people',                'users/index.php',          '/users/'],
        ['Data Atlet',    'bi-person-badge',          'athletes/index.php',       '/athletes/'],
        ['Prestasi',      'bi-trophy',                'achievements/index.php',   '/achievements/'],
        ['Tes Kebugaran', 'bi-heart-pulse',           'fitness-tests/index.php',  '/fitness-tests/'],
        ['Laporan',       'bi-file-earmark-bar-graph', 'reports/index.php',       '/reports/'],
        ['Pengaturan',    'bi-gear',                  'settings.php',             '/settings.php'],
    ],
    'pelatih' => [
        ['Dashboard',     'bi-speedometer2',          'dashboard.php',            '/dashboard.php'],
        ['Data Atlet',    'bi-person-badge',          'athletes/index.php',       '/athletes/'],
        ['Prestasi',      'bi-trophy',                'achievements/index.php',   '/achievements/'],
        ['Tes Kebugaran', 'bi-heart-pulse',           'fitness-tests/index.php',  '/fitness-tests/'],
        ['Laporan',       'bi-file-earmark-bar-graph', 'reports/index.php',       '/reports/'],
        ['Profil',        'bi-person-circle',         'profile.php',              '/profile.php'],
    ],
    'atlet' => [
        ['Dashboard',          'bi-speedometer2',  'dashboard.php',     '/dashboard.php'],
        ['Profil Saya',        'bi-person-circle', 'profile.php',       '/profile.php'],
        ['Prestasi Saya',      'bi-trophy',        'achievements.php',  '/achievements.php'],
        ['Tes Kebugaran Saya', 'bi-heart-pulse',   'fitness-tests.php', '/fitness-tests.php'],
        ['Perkembangan',       'bi-graph-up-arrow', 'progress.php',     '/progress.php'],
    ],
];
$items = $menus[$role] ?? [];
?>
<aside class="offcanvas-md offcanvas-start sidebar no-print" tabindex="-1" id="sidebar">
    <div class="offcanvas-header d-md-none">
        <h5 class="offcanvas-title text-white">Menu</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#sidebar"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column p-0">
        <a class="sidebar-brand" href="<?= e(BASE_URL . home_path()) ?>">
            <i class="bi bi-trophy-fill"></i> <?= e(setting('app_name', 'Manajemen Atlet')) ?>
        </a>
        <nav class="nav flex-column flex-grow-1 py-2">
            <?php foreach ($items as $m): ?>
                <a class="nav-link<?= str_contains($script, $m[3]) ? ' active' : '' ?>" href="<?= e(url($m[2])) ?>">
                    <i class="bi <?= e($m[1]) ?>"></i> <?= e($m[0]) ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <form method="post" action="<?= e(BASE_URL . '/auth/logout.php') ?>" class="p-3">
            <?= csrf_field() ?>
            <button class="btn btn-outline-light w-100"><i class="bi bi-box-arrow-right"></i> Logout</button>
        </form>
    </div>
</aside>
