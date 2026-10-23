<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') - SAP Business One</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root {
            --sidebar-width: 260px;
            --sidebar-bg: #111827;
            --sidebar-bg-hover: #1f2937;
            --sidebar-active: #2563eb;
            --topbar-h: 64px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            background: #f3f4f6;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            overflow-x: hidden;
        }

        /* ===== SIDEBAR ===== */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--sidebar-bg) 0%, #0b1120 100%);
            transform: translateX(-100%);
            transition: transform 0.3s ease;
            z-index: 1040;
            display: flex;
            flex-direction: column;
            box-shadow: 4px 0 24px rgba(0,0,0,0.25);
        }

        .sidebar.show {
            transform: translateX(0);
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 20px;
            height: var(--topbar-h);
            border-bottom: 1px solid rgba(255,255,255,0.08);
            text-decoration: none;
        }

        .sidebar-brand .logo-badge {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: #fff;
            flex-shrink: 0;
        }

        .sidebar-brand span {
            color: #fff;
            font-weight: 600;
            font-size: 1rem;
            letter-spacing: 0.3px;
        }

        .sidebar-nav {
            flex: 1;
            padding: 16px 12px;
            overflow-y: auto;
        }

        .sidebar-nav .nav-section-label {
            color: #6b7280;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 8px 12px 6px;
            font-weight: 600;
        }

        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            border-radius: 10px;
            color: #cbd5e1;
            text-decoration: none;
            font-size: 0.9rem;
            margin-bottom: 4px;
            transition: all 0.15s ease;
        }

        .sidebar-nav a i {
            font-size: 1.05rem;
            width: 20px;
            text-align: center;
        }

        .sidebar-nav a:hover {
            background: var(--sidebar-bg-hover);
            color: #fff;
        }

        .sidebar-nav a.active {
            background: var(--sidebar-active);
            color: #fff;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.35);
        }

        .sidebar-footer {
            padding: 16px 20px;
            border-top: 1px solid rgba(255,255,255,0.08);
        }

        .sidebar-footer .user-chip {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #e5e7eb;
            font-size: 0.85rem;
        }

        .sidebar-footer .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #374151;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            color: #fff;
            flex-shrink: 0;
        }

        /* ===== OVERLAY (mobile) ===== */
        .sidebar-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.45);
            z-index: 1030;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease;
        }

        .sidebar-overlay.show {
            opacity: 1;
            visibility: visible;
        }

        /* ===== TOPBAR ===== */
        .topbar {
            position: sticky;
            top: 0;
            z-index: 1020;
            height: var(--topbar-h);
            background: #fff;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
            transition: margin-left 0.3s ease;
        }

        .btn-toggle {
            background: transparent;
            border: none;
            font-size: 1.4rem;
            color: #111827;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 8px;
            transition: background 0.15s ease;
        }

        .btn-toggle:hover {
            background: #f3f4f6;
        }

        /* ===== MAIN CONTENT ===== */
        .main-content {
            transition: margin-left 0.3s ease;
            min-height: 100vh;
        }

        /* Desktop: sidebar pinned open, content shifts */
        @media (min-width: 992px) {
            .sidebar {
                transform: translateX(0);
            }

            .main-content,
            .topbar {
                margin-left: var(--sidebar-width);
            }

            .sidebar.collapsed {
                transform: translateX(-100%);
            }

            .main-content.expanded,
            .topbar.expanded {
                margin-left: 0;
            }
        }

        .page-wrap {
            padding: 28px 24px 40px;
        }

        code {
            background: #f3f4f6;
            padding: 2px 6px;
            border-radius: 6px;
            font-size: 0.8rem;
        }
    </style>

    @stack('styles')
</head>
<body>

    <!-- Overlay untuk mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- ===== SIDEBAR ===== -->
    <aside class="sidebar" id="sidebar">
        <a href="{{ url('/dashboard') }}" class="sidebar-brand">
            <div class="logo-badge">S1</div>
            <span>SAP Business One</span>
        </a>

        <nav class="sidebar-nav">
            <div class="nav-section-label">Menu Utama</div>
            <a href="{{ url('/dashboard') }}" class="{{ request()->is('dashboard') ? 'active' : '' }}">
                <i class="bi bi-grid-1x2-fill"></i> Dashboard
            </a>
            <a href="{{ url('/items') }}" class="{{ request()->is('items*') ? 'active' : '' }}">
                <i class="bi bi-box-seam"></i> Master Items
            </a>
            <a href="{{ url('/business-partners') }}" class="{{ request()->is('business-partners*') ? 'active' : '' }}">
                <i class="bi bi-people-fill"></i> Business Partners
            </a>

            <div class="nav-section-label mt-3">Laporan</div>
            <a href="{{ url('/reports/picking-list') }}" class="{{ request()->is('reports/picking-list*') ? 'active' : '' }}">
                <i class="bi bi-clipboard-check"></i> Picking List Report
            </a>
            <a href="{{ url('/reports/sales-order') }}" class="{{ request()->is('reports/sales-order*') ? 'active' : '' }}">
                <i class="bi bi-cart-check"></i> Sales Order Report
            </a>
            <a href="{{ url('/reports/purchase-order') }}" class="{{ request()->is('reports/purchase-order*') ? 'active' : '' }}">
                <i class="bi bi-truck"></i> Purchase Order Report
            </a>
            <a href="{{ url('/reports/adhoc-query') }}" class="{{ request()->is('reports/adhoc-query*') ? 'active' : '' }}">
                <i class="bi bi-terminal"></i> Query Manual (Ad-Hoc)
            </a>
        </nav>

        <div class="sidebar-footer">
            <div class="user-chip">
                <div class="user-avatar">
                    <i class="bi bi-person-fill"></i>
                </div>
                <div>
                    <div class="fw-semibold">{{ session('sap_username') }}</div>
                    <div style="font-size:0.75rem; color:#9ca3af;">Online</div>
                </div>
            </div>
        </div>
    </aside>

    <!-- ===== TOPBAR ===== -->
    <div class="topbar" id="topbar">
        <div class="d-flex align-items-center gap-2">
            <button class="btn-toggle" id="sidebarToggle" title="Toggle sidebar">
                <i class="bi bi-list"></i>
            </button>
            <span class="fw-semibold d-none d-sm-inline">@yield('title', 'Dashboard')</span>
        </div>

        <form method="POST" action="{{ url('/logout') }}" class="d-flex align-items-center gap-2 m-0" id="logoutForm">
            @csrf
            <span class="text-muted small d-none d-md-inline">
                Login sebagai: <strong>{{ session('sap_username') }}</strong>
            </span>
            <button type="button" class="btn btn-outline-danger btn-sm" id="logoutBtn">
                <i class="bi bi-box-arrow-right"></i> Logout
            </button>
        </form>
    </div>

    <!-- ===== MAIN CONTENT ===== -->
    <div class="main-content" id="mainContent">
        <div class="page-wrap">
            @yield('content')
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const toggleBtn = document.getElementById('sidebarToggle');
        const mainContent = document.getElementById('mainContent');
        const topbar = document.getElementById('topbar');

        function isDesktop() {
            return window.innerWidth >= 992;
        }

        toggleBtn.addEventListener('click', () => {
            if (isDesktop()) {
                sidebar.classList.toggle('collapsed');
                mainContent.classList.toggle('expanded');
                topbar.classList.toggle('expanded');
            } else {
                sidebar.classList.toggle('show');
                overlay.classList.toggle('show');
            }
        });

        overlay.addEventListener('click', () => {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        });

        window.addEventListener('resize', () => {
            if (isDesktop()) {
                overlay.classList.remove('show');
                sidebar.classList.remove('show');
            } else {
                sidebar.classList.remove('collapsed');
                mainContent.classList.remove('expanded');
                topbar.classList.remove('expanded');
            }
        });

        document.querySelectorAll('.sidebar-nav a').forEach(link => {
            link.addEventListener('click', () => {
                if (!isDesktop()) {
                    sidebar.classList.remove('show');
                    overlay.classList.remove('show');
                }
            });
        });

        // ===== KONFIRMASI LOGOUT PAKAI SWEETALERT =====
        document.getElementById('logoutBtn').addEventListener('click', () => {
            Swal.fire({
                title: 'Yakin mau logout?',
                text: 'Sesi kamu di SAP Business One akan diakhiri.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Logout',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                reverseButtons: true,
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('logoutForm').submit();
                }
            });
        });
    </script>

    @include('partials.loading-overlay')

    @stack('scripts')
</body>
</html>