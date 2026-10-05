<?php /** components/navbar.php - bar atas. */ ?>
<nav class="topbar d-flex align-items-center justify-content-between px-3 no-print">
    <div class="d-flex align-items-center gap-2">
        <button class="btn btn-light d-md-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-label="Menu">
            <i class="bi bi-list"></i>
        </button>
        <h1 class="h5 mb-0"><?= e($pageTitle ?? '') ?></h1>
    </div>
    <div class="dropdown">
        <a href="#" class="d-flex align-items-center gap-2 text-decoration-none text-dark dropdown-toggle" data-bs-toggle="dropdown">
            <i class="bi bi-person-circle fs-4"></i>
            <span class="d-none d-sm-inline"><?= e($_SESSION['full_name'] ?? '') ?></span>
            <?= role_badge($_SESSION['role'] ?? '') ?>
        </a>
        <ul class="dropdown-menu dropdown-menu-end">
            <li><span class="dropdown-item-text small text-muted"><?= e($_SESSION['email'] ?? '') ?></span></li>
            <li><a class="dropdown-item" href="<?= e(url('profile.php')) ?>">Profil &amp; Password</a></li>
            <li><hr class="dropdown-divider"></li>
            <li>
                <form method="post" action="<?= e(BASE_URL . '/auth/logout.php') ?>">
                    <?= csrf_field() ?>
                    <button class="dropdown-item text-danger">Logout</button>
                </form>
            </li>
        </ul>
    </div>
</nav>
