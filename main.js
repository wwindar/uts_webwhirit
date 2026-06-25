document.addEventListener('DOMContentLoaded', function () {
    const toggle = document.getElementById('navToggle');
    const navLinks = document.getElementById('navLinks');

    if (toggle && navLinks) {
        toggle.addEventListener('click', function () {
            navLinks.classList.toggle('open');
        });

        document.addEventListener('click', function (e) {
            if (!toggle.contains(e.target) && !navLinks.contains(e.target)) {
                navLinks.classList.remove('open');
            }
        });
    }

    const starInputs = document.querySelectorAll('.star-select input[type="radio"]');
    starInputs.forEach(function (input) {
        if (input.checked) {
            input.parentElement.querySelectorAll('label').forEach(function (lbl, i) {
                const val = parseInt(input.value);
                const lblVal = parseInt(lbl.htmlFor.replace('rating', '')) || (i + 1);
                if (i < val) lbl.style.opacity = '1';
            });
        }
    });

    const deleteLinks = document.querySelectorAll('.btn-hapus');
    deleteLinks.forEach(function (link) {
        link.addEventListener('click', function (e) {
            if (!confirm('Yakin ingin menghapus resensi ini? Tindakan tidak dapat dibatalkan.')) {
                e.preventDefault();
            }
        });
    });

    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(function () { alert.remove(); }, 500);
        }, 4000);
    });

    const modal = $('#crudModal');
    const btnTambah = $('#btnTambah');
    const closeElements = $('.close-modal');

    if ($('#bukuSelect').length) {
        $('#bukuSelect').select2({
            placeholder: "Cari judul buku...",
            allowClear: true,
            dropdownParent: modal
        });
    }

    if (btnTambah.length) {
        btnTambah.on('click', function () {
            modal.addClass('open');
        });
    }

    closeElements.on('click', function () {
        tutupDanResetModal();
    });

    $(window).on('click', function (e) {
        if ($(e.target).is(modal)) {
            tutupDanResetModal();
        }
    });

    function tutupDanResetModal() {
        modal.removeClass('open');
        if ($('#crudForm').length) {
            $('#crudForm')[0].reset();
        }
        $('#bukuSelect').val(null).trigger('change');
        $('#coverUrlInput').val('');
    }

    $('#crudForm').on('submit', function (e) {
        e.preventDefault();
        
        const coverUrl = $('#coverUrlInput').val().trim() || 'https://images.unsplash.com/photo-1543002588-bfa74002ed7e?w=500';
        const judulTerpilih = $('#bukuSelect').find(':selected').text();
        
        tutupDanResetModal();
        buatAlert(`Resensi untuk buku "${judulTerpilih}" berhasil disimpan!`, 'success');
    });

    function buatAlert(pesan, tipe) {
        const alertHtml = `
            <div class="alert alert-${tipe}" style="position: fixed; top: 20px; right: 20px; z-index: 1100; min-width: 300px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                ${pesan}
            </div>
        `;
        
        const $alertElement = $(alertHtml).appendTo('#alertContainer');

        setTimeout(function () {
            $alertElement.css({
                'transition': 'opacity 0.5s',
                'opacity': '0'
            });
            setTimeout(function () { 
                $alertElement.remove(); 
            }, 500);
        }, 4000);
    }
});