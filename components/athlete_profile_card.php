<?php
/** Kartu profil atlet. Variabel: $athlete (array), $latest (tes terakhir atau null). */
$h = isset($latest['height']) ? (float) $latest['height'] : null;
$w = isset($latest['weight']) ? (float) $latest['weight'] : null;
$bmi = bmi($h, $w);
?>
<div class="row g-3">
    <div class="col-lg-8">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex flex-column flex-sm-row gap-3 align-items-sm-center mb-3">
                    <?= avatar_html($athlete['full_name'], $athlete['photo'], 96) ?>
                    <div>
                        <h2 class="h4 mb-1"><?= e($athlete['full_name']) ?></h2>
                        <div class="text-muted"><?= e($athlete['sport']) ?><?= $athlete['position'] ? ' &middot; ' . e($athlete['position']) : '' ?></div>
                        <div class="mt-1"><?= status_badge($athlete['status']) ?></div>
                    </div>
                </div>
                <dl class="row mb-0 profile-dl">
                    <dt class="col-sm-4">Jenis Kelamin</dt><dd class="col-sm-8"><?= e($athlete['gender']) ?></dd>
                    <dt class="col-sm-4">Tempat, Tgl Lahir</dt>
                    <dd class="col-sm-8"><?= e($athlete['birth_place'] ?: '-') ?>, <?= e(fmt_date($athlete['birth_date'])) ?>
                        <?php if (($age = age_from($athlete['birth_date'])) !== null): ?>(<?= $age ?> th)<?php endif; ?></dd>
                    <dt class="col-sm-4">Sekolah / Kelas</dt><dd class="col-sm-8"><?= e($athlete['school'] ?: '-') ?> / <?= e($athlete['class'] ?: '-') ?></dd>
                    <dt class="col-sm-4">No. Punggung</dt><dd class="col-sm-8"><?= e($athlete['jersey_number'] ?? '-') ?></dd>
                    <dt class="col-sm-4">Telepon</dt><dd class="col-sm-8"><?= e($athlete['phone'] ?: '-') ?></dd>
                    <dt class="col-sm-4">Alamat</dt><dd class="col-sm-8"><?= nl2br(e($athlete['address'] ?: '-')) ?></dd>
                    <dt class="col-sm-4">Catatan</dt><dd class="col-sm-8"><?= nl2br(e($athlete['notes'] ?: '-')) ?></dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Data Fisik Terakhir</div>
            <div class="card-body">
                <?php if ($latest): ?>
                    <div class="text-muted small mb-2">Tes <?= e(fmt_date($latest['test_date'])) ?></div>
                    <div class="d-flex justify-content-between border-bottom py-2"><span>Tinggi</span><strong><?= $h ? e($h) . ' cm' : '-' ?></strong></div>
                    <div class="d-flex justify-content-between border-bottom py-2"><span>Berat</span><strong><?= $w ? e($w) . ' kg' : '-' ?></strong></div>
                    <div class="d-flex justify-content-between py-2"><span>IMT (BMI)</span><strong><?= $bmi !== null ? e($bmi) : '-' ?></strong></div>
                <?php else: ?>
                    <p class="text-muted mb-0">Belum ada data tes kebugaran.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
