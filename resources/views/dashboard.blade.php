@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')

    <h4 class="fw-bold text-dark mb-1">Selamat datang 👋</h4>
    <p class="text-muted mb-4">Ringkasan akses cepat ke modul SAP Business One kamu.</p>

    <style>
        .menu-card {
            border: none;
            border-radius: 16px;
            background: #fff;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            height: 100%;
        }
        .menu-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(17, 24, 39, 0.08) !important;
        }
        .menu-card .icon-box {
            width: 46px;
            height: 46px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            margin-bottom: 14px;
        }
        .icon-blue   { background: #dbeafe; color: #2563eb; }
        .icon-green  { background: #d1fae5; color: #059669; }
        .icon-purple { background: #ede9fe; color: #7c3aed; }
        .icon-orange { background: #ffedd5; color: #ea580c; }
        .icon-gray   { background: #e5e7eb; color: #4b5563; }
        .menu-card h6 { font-weight: 600; color: #111827; }
        .menu-card p { font-size: 0.85rem; min-height: 40px; }
        .badge-soft { background: #f3f4f6; color: #374151; font-weight: 500; font-size: 0.72rem; }
    </style>

    <div class="row g-3">
        <div class="col-sm-6 col-lg-4">
            <div class="card menu-card shadow-sm p-3">
                <div class="card-body">
                    <div class="icon-box icon-blue"><i class="bi bi-box-seam"></i></div>
                    <h6>Master Items</h6>
                    <p class="text-muted mb-3">Data item dari cache MySQL (hasil sync SAP)</p>
                    <a href="/items" class="btn btn-sm btn-primary">Lihat Items <i class="bi bi-arrow-right ms-1"></i></a>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-4">
            <div class="card menu-card shadow-sm p-3">
                <div class="card-body">
                    <div class="icon-box icon-green"><i class="bi bi-people-fill"></i></div>
                    <h6>Business Partners</h6>
                    <p class="text-muted mb-3">Data customer/supplier dari cache MySQL</p>
                    <a href="/business-partners" class="btn btn-sm btn-success">Lihat Business Partners <i class="bi bi-arrow-right ms-1"></i></a>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-4">
            <div class="card menu-card shadow-sm p-3">
                <div class="card-body">
                    <div class="icon-box icon-purple"><i class="bi bi-clipboard-check"></i></div>
                    <h6>Picking List Report</h6>
                    <p class="text-muted mb-3">Data real-time dari SAP lewat endpoint resmi /PickLists</p>
                    <a href="/reports/picking-list" class="btn btn-sm" style="background:#7c3aed;color:#fff;">Lihat Picking List <i class="bi bi-arrow-right ms-1"></i></a>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-4">
            <div class="card menu-card shadow-sm p-3">
                <div class="card-body">
                    <div class="icon-box icon-orange"><i class="bi bi-terminal"></i></div>
                    <h6>Query Manual (Ad-Hoc)</h6>
                    <p class="text-muted mb-3">Tulis SQL SELECT sendiri ke tabel SAP HANA (butuh izin per tabel)</p>
                    <a href="/reports/adhoc-query" class="btn btn-sm btn-warning text-white">Buka Query Manual <i class="bi bi-arrow-right ms-1"></i></a>
                </div>
            </div>
        </div>
    </div>

@endsection