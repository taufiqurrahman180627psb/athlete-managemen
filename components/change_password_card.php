<?php /** Form ganti password. Variabel: $pwErrors (array). */ ?>
<div class="card shadow-sm">
    <div class="card-header bg-white fw-semibold">Ganti Password</div>
    <div class="card-body">
        <?php foreach ($pwErrors as $er): ?><div class="alert alert-danger py-2"><?= e($er) ?></div><?php endforeach; ?>
        <form method="post" autocomplete="off">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="change_password">
            <div class="mb-3"><label class="form-label">Password saat ini</label>
                <input type="password" name="current_password" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">Password baru</label>
                <input type="password" name="new_password" class="form-control" minlength="8" required>
                <div class="form-text">Minimal 8 karakter, kombinasi huruf dan angka.</div></div>
            <div class="mb-3"><label class="form-label">Konfirmasi password baru</label>
                <input type="password" name="confirm_password" class="form-control" required></div>
            <button class="btn btn-primary">Simpan Password</button>
        </form>
    </div>
</div>
