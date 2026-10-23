
 <a href="..." data-no-loading>...</a>

<div id="globalLoadingOverlay" class="global-loading-overlay">
    <div class="global-loading-box">
        <img src="{{ asset('images/Logo.png') }}" alt="Logo Perusahaan" class="global-loading-logo-img">
        <div class="global-loading-spinner"></div>
        <div class="global-loading-text">Memuat data...</div>
    </div>
</div>

<style>
    .global-loading-overlay {
        position: fixed;
        inset: 0;
        z-index: 2000; /* di atas sidebar (1040) dan topbar (1020) */
        background: rgba(17, 24, 39, 0.55);
        backdrop-filter: blur(2px);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.15s ease;
    }

    .global-loading-overlay.show {
        opacity: 1;
        visibility: visible;
    }

    .global-loading-box {
        background: #fff;
        border-radius: 16px;
        padding: 32px 40px;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 14px;
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
        min-width: 220px;
    }

    .global-loading-logo,
    .global-loading-logo-img {
        width: 56px;
        height: 56px;
        border-radius: 14px;
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1.2rem;
        color: #fff;
        animation: global-loading-pulse 1.4s ease-in-out infinite;
        object-fit: contain;
    }

    @keyframes global-loading-pulse {
        0%, 100% { transform: scale(1); opacity: 1; }
        50%      { transform: scale(1.08); opacity: 0.85; }
    }

    .global-loading-spinner {
        width: 30px;
        height: 30px;
        border: 3px solid #e5e7eb;
        border-top-color: #2563eb;
        border-radius: 50%;
        animation: global-loading-spin 0.7s linear infinite;
    }

    @keyframes global-loading-spin {
        to { transform: rotate(360deg); }
    }

    .global-loading-text {
        font-size: 0.85rem;
        color: #6b7280;
        font-weight: 500;
    }
</style>

<script>
    (function () {
        const overlay = document.getElementById('globalLoadingOverlay');

        function showGlobalLoading() {
            overlay.classList.add('show');
        }

        function hideGlobalLoading() {
            overlay.classList.remove('show');
        }

        // Expose secara global kalau ada kode lain (mis. tombol custom,
        // AJAX request) yang mau munculin/hilangin overlay ini secara manual.
        window.showGlobalLoading = showGlobalLoading;
        window.hideGlobalLoading = hideGlobalLoading;

        // ===== Tampilkan overlay otomatis saat klik link navigasi biasa =====
        document.addEventListener('click', function (e) {
            const link = e.target.closest('a[href]');
            if (!link) return;

            // Lewati kalau: link punya data-no-loading, buka tab baru,
            // anchor doang (#...), javascript:, atau user Ctrl/Cmd/Shift+klik
            // (biasanya maksudnya buka di tab baru, halaman ini gak ikut pindah).
            if (link.hasAttribute('data-no-loading')) return;
            if (link.target === '_blank') return;
            if (e.ctrlKey || e.metaKey || e.shiftKey || e.button !== 0) return;

            const href = link.getAttribute('href') || '';
            if (href === '' || href.startsWith('#') || href.startsWith('javascript:')) return;

            showGlobalLoading();
        });

        // ===== Tampilkan overlay otomatis saat form di-submit =====
        document.addEventListener('submit', function (e) {
            const form = e.target;
            if (form.hasAttribute('data-no-loading')) return;
            showGlobalLoading();
        });

        // ===== Selalu sembunyikan overlay saat halaman baru selesai tampil =====
        // 'pageshow' juga menangkap kasus tombol Back/Forward browser (bfcache),
        // supaya overlay tidak nyangkut kebawa dari halaman sebelumnya.
        window.addEventListener('pageshow', function () {
            hideGlobalLoading();
        });
    })();
</script>