<?php
/** Modal konfirmasi hapus. Variabel: $deleteUrl (wajib), $deleteWarning (opsional). */
?>
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" action="<?= e($deleteUrl) ?>" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="">
            <div class="modal-header">
                <h5 class="modal-title">Konfirmasi Hapus</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                Yakin ingin menghapus <strong class="delete-name"></strong>?
                <div class="text-muted small mt-2"><?= e($deleteWarning ?? 'Tindakan ini tidak dapat dibatalkan.') ?></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button class="btn btn-danger">Ya, Hapus</button>
            </div>
        </form>
    </div>
</div>
