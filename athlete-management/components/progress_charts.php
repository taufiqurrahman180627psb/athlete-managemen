<?php
/**
 * Grafik perkembangan (Chart.js).
 * Variabel: $tests (array tes URUT TANGGAL NAIK), $chartKeys (opsional, daftar kunci grafik).
 * Halaman pemanggil harus set $useCharts = true sebelum memuat header.
 */
$chartDefs = [
    'vo2_max'    => ['VO2 Max (ml/kg/menit)', '#0d6efd'],
    'push_up'    => ['Push-up (repetisi)',    '#198754'],
    'sit_up'     => ['Sit-up (repetisi)',     '#fd7e14'],
    'sprint_20m' => ['Sprint 20 Meter (detik)', '#dc3545'],
    'weight'     => ['Berat Badan (kg)',      '#6f42c1'],
];
$chartKeys = $chartKeys ?? array_keys($chartDefs);
$labels = array_map(fn($t) => date('d/m/Y', strtotime($t['test_date'])), $tests);
$cfg = [];
foreach ($chartKeys as $k) {
    if (!isset($chartDefs[$k])) { continue; }
    $cfg[$k] = [
        'label' => $chartDefs[$k][0],
        'color' => $chartDefs[$k][1],
        'data'  => array_map(fn($t) => $t[$k] === null ? null : (float) $t[$k], $tests),
    ];
}
?>
<?php if (!$tests): ?>
    <div class="alert alert-info mb-0">Belum ada data tes kebugaran untuk ditampilkan dalam grafik.</div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($cfg as $k => $c): ?>
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-white fw-semibold"><?= e($c['label']) ?></div>
                    <div class="card-body"><div class="chart-box"><canvas id="chart_<?= e($k) ?>"></canvas></div></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <script>
    (function () {
        var labels = <?= js_json($labels) ?>;
        var cfg = <?= js_json($cfg) ?>;
        Object.keys(cfg).forEach(function (k) {
            var el = document.getElementById('chart_' + k);
            if (!el) { return; }
            new Chart(el, {
                type: 'line',
                data: { labels: labels, datasets: [{
                    label: cfg[k].label, data: cfg[k].data, borderColor: cfg[k].color,
                    backgroundColor: cfg[k].color + '33', fill: true, tension: 0.3, spanGaps: true, pointRadius: 4
                }]},
                options: { responsive: true, maintainAspectRatio: false }
            });
        });
    })();
    </script>
<?php endif; ?>
