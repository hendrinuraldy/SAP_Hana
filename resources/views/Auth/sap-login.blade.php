
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SAP Business One</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            min-height: 100vh;
            background-color: #ffffff;
        }

        /* Branding Panel (Kiri) */
        .brand-section {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0d6efd 100%);
            color: #ffffff;
            position: relative;
            overflow: hidden;
        }

        .brand-section::before {
            content: '';
            position: absolute;
            top: -20%;
            left: -20%;
            width: 140%;
            height: 140%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.05) 0%, transparent 60%);
            pointer-events: none;
        }

        /* Custom Form Input styling */
        .form-floating > .form-control {
            border-radius: 0.75rem;
            border: 1px solid #e2e8f0;
            background-color: #f8fafc;
        }

        .form-floating > .form-control:focus {
            background-color: #ffffff;
            border-color: #0d6efd;
            box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.1);
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%);
            border: none;
            border-radius: 0.75rem;
            padding: 0.8rem;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .btn-primary-custom:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.25);
        }
    </style>
</head>
   @include('partials.loading-overlay')
<body>

    <div class="container-fluid p-0">
        <div class="row g-0 min-vh-100">
            
            <!-- Side Visual/Branding Panel (Hidden di HP) -->
            <div class="col-lg-7 d-none d-lg-flex flex-column justify-content-between p-5 brand-section">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-white bg-opacity-10 p-2 rounded-3 text-white">
                        <i class="bi bi-box-seam fs-4"></i>
                    </div>
                    <span class="fw-bold fs-5 tracking-wide">SAP Integration Gateway</span>
                </div>

                <div class="my-auto py-5" style="max-width: 540px;">
                    <span class="badge bg-blue bg-opacity-20 text-white mb-3 px-3 py-2 rounded-pill small">
                        Enterprise Portal v2.0
                    </span>
                    <h1 class="display-5 fw-bold mb-3">Kelola & Analisis Data SAP Lebih Cepat.</h1>
                    <p class="text-white-50 fs-6 leading-relaxed">
                        Akses real-time ke modul Sales, Purchasing, hingga Inventory SAP Business One dalam satu dasbor terintegrasi.
                    </p>
                </div>

                <div class="d-flex align-items-center justify-content-between text-white-50 small border-top border-white border-opacity-10 pt-4">
                    <span>&copy; {{ date('Y') }} SAP Business One HANA Integration</span>
                    <span class="d-flex align-items-center gap-1"><i class="bi bi-shield-check text-success"></i> SSL Encrypted</span>
                </div>
            </div>

            <!-- Form Login Section (Kanan) -->
            <div class="col-lg-5 d-flex align-items-center justify-content-center p-4 p-sm-5 bg-white">
                <div class="w-100" style="max-width: 400px;">
                    
                    <!-- Mobile Logo Header -->
                    <div class="d-lg-none text-center mb-4">
                        <div class="d-inline-flex bg-primary bg-opacity-10 text-primary p-3 rounded-circle mb-2">
                            <i class="bi bi-box-seam fs-3"></i>
                        </div>
                        <h4 class="fw-bold">SAP Business One</h4>
                    </div>

                    <div class="mb-4">
                        <h3 class="fw-bold text-dark mb-1">Selamat Datang 👋</h3>
                        <p class="text-muted small">Silakan masuk menggunakan kredensial SAP HANA Anda.</p>
                    </div>

                    <!-- Alerts -->
                    @if ($errors->any())
                        <div class="alert alert-danger border-0 rounded-3 p-3 small mb-4 bg-danger bg-opacity-10 text-danger">
                            <div class="fw-bold mb-1"><i class="bi bi-exclamation-octagon-fill me-1"></i> Gagal Masuk</div>
                            <ul class="mb-0 ps-3">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-warning border-0 rounded-3 p-3 small d-flex align-items-center gap-2 mb-4 bg-warning bg-opacity-10 text-warning-emphasis">
                            <i class="bi bi-exclamation-triangle-fill fs-6"></i>
                            <div>{{ session('error') }}</div>
                        </div>
                    @endif

                    <!-- Form Floating Input -->
                    <form method="POST" action="{{ url('/login') }}">
                        @csrf

                        <div class="form-floating mb-3">
                            <input type="text" name="username" value="{{ old('username') }}" class="form-control" id="floatingUsername" placeholder="IT" required autofocus>
                            <label for="floatingUsername" class="text-muted">Username SAP</label>
                        </div>

                        <div class="form-floating mb-4">
                            <input type="password" name="password" class="form-control" id="floatingPassword" placeholder="Password" required>
                            <label for="floatingPassword" class="text-muted">Password</label>
                        </div>

                        <button type="submit" class="btn btn-primary-custom btn-primary w-100 mb-3">
                            Masuk ke Sistem <i class="bi bi-arrow-right ms-1"></i>
                        </button>
                    </form>

                    <div class="text-center mt-4 pt-3 border-top">
                        <p class="text-muted mb-0" style="font-size: 0.8rem;">
                            <i class="bi bi-cpu me-1 text-primary"></i> Powered by <strong>SAP B1 Service Layer API</strong>
                        </p>
                    </div>

                </div>
            </div>

        </div>
    </div>

</body>

</html>