<?php
/**
 * components/header.php
 * Variabel opsional: $pageTitle (string), $bare (bool: tanpa sidebar), $useCharts (bool: muat Chart.js)
 */
$appName = setting('app_name', 'Sistem Manajemen Atlet');
?><!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(($pageTitle ?? 'Halaman') . ' | ' . $appName) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= e(asset('css/style.css')) ?>" rel="stylesheet">
<?php if (!empty($useCharts)): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<?php endif; ?>
</head>
<body class="<?= !empty($bare) ? 'bare' : 'app' ?>">
<?php if (empty($bare)): ?>
<div class="d-flex" id="wrapper">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <div id="page-content" class="flex-grow-1">
        <?php include __DIR__ . '/navbar.php'; ?>
        <main class="p-3 p-md-4">
            <?php foreach (flashes() as $f): ?>
                <div class="alert alert-<?= e($f['type']) ?> alert-dismissible fade show no-print" role="alert">
                    <?= e($f['msg']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
                </div>
            <?php endforeach; ?>
<?php endif; ?>
