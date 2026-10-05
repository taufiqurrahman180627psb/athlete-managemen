// Isi modal konfirmasi hapus dari atribut data-* tombol pemicu.
document.addEventListener('DOMContentLoaded', function () {
    var m = document.getElementById('deleteModal');
    if (m) {
        m.addEventListener('show.bs.modal', function (ev) {
            var b = ev.relatedTarget;
            if (!b) { return; }
            m.querySelector('input[name=id]').value = b.getAttribute('data-id');
            m.querySelector('.delete-name').textContent = b.getAttribute('data-name');
        });
    }
});
