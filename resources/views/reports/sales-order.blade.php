@extends('layouts.app')

@section('title', 'Sales Order Report')

@section('content')
<div class="container-fluid py-4 px-4">
    <!-- Header Section -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h3 class="fw-bold text-dark mb-1">Sales Order Report</h3>
            <p class="text-muted small mb-0">
                <i class="bi bi-arrow-repeat me-1 text-primary"></i> Data real-time dari SAP Business One (Sales - A/R)
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            @if (!empty($cachedAt))
                <span class="badge bg-light text-secondary border px-3 py-2 rounded-pill fs-7 me-1">
                    <i class="bi bi-clock-history me-1"></i> Dimuat {{ $cachedAt->diffForHumans() }}
                </span>
            @endif
            <a href="{{ request()->fullUrlWithQuery(['refresh' => 1, 'page' => null]) }}" class="btn btn-outline-secondary btn-sm rounded-3 d-flex align-items-center gap-2">
                <i class="bi bi-arrow-clockwise"></i> Refresh Data
            </a>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill fs-7">
                <i class="bi bi-cart-check me-1"></i> {{ $rows->total() }} Baris
            </span>
        </div>
    </div>

    <!-- Error Alert Section -->
    @if ($error)
        <div class="alert alert-danger border-0 shadow-sm rounded-4 mb-4" role="alert">
            <div class="d-flex align-items-start gap-3">
                <i class="bi bi-x-octagon-fill fs-4 text-danger"></i>
                <div>
                    <h6 class="fw-bold mb-1">Gagal Mengambil Data SAP</h6>
                    <p class="mb-1 text-secondary small">{{ $error }}</p>
                    <div class="small text-muted border-top border-danger-subtle pt-2 mt-2">
                        <i class="bi bi-info-circle me-1"></i> Pastikan user SAP memiliki izin akses ke modul Sales - A/R (Sales Order).
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Debug Drawer (Collapsible) -->
    @if (!empty($debugRaw))
        <div class="accordion mb-4" id="debugAccordion">
            <div class="accordion-item border-0 shadow-sm rounded-4 overflow-hidden">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed bg-light text-muted small py-2" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDebug">
                        <i class="bi bi-bug me-2"></i> Inspect Raw Response SAP (Debug Mode)
                    </button>
                </h2>
                <div id="collapseDebug" class="accordion-collapse collapse">
                    <div class="accordion-body bg-dark text-light p-3">
                        <pre class="mb-0 text-success font-monospace small" style="max-height: 350px; overflow: auto;">{{ json_encode($debugRaw[0] ?? [], JSON_PRETTY_PRINT) }}</pre>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Main Data Card -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <!-- Table Filter / Header Bar -->
        <div class="card-header bg-white border-0 py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                <i class="bi bi-bag-check text-primary"></i> Daftar Transaksi Sales Order
            </h6>
            <!-- Quick Client Search Filter -->
            <div class="input-group input-group-sm" style="max-width: 280px;">
                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                <input type="text" id="tableSearchInput" class="form-control bg-light border-start-0" placeholder="Cari transaksi/pelanggan..." onkeyup="filterTable()">
            </div>
        </div>

        <!-- Table Responsive Wrapper -->
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="reportTable" style="font-size: 0.875rem;">
                <thead class="table-light border-bottom">
                    <tr>
                        @foreach ($columns as $col)
                            <th scope="col" class="py-3 px-3 text-uppercase text-secondary fw-bold text-nowrap" style="letter-spacing: 0.5px; font-size: 0.8rem;">
                                {{ $col }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($rows as $row)
                        <tr>
                            @foreach ($columns as $col)
                                <td class="py-3 px-3 text-nowrap text-dark">
                                    @if ($col === 'Status')
                                        @php
                                            $statusMap = [
                                                'bost_Open'  => 'primary',
                                                'bost_Close' => 'secondary',
                                                'bost_Paid'  => 'success',
                                            ];
                                            $badgeClass = $statusMap[$row[$col]] ?? 'light text-dark border';
                                        @endphp
                                        @if (!empty($row[$col]))
                                            <span class="badge bg-{{ $badgeClass }} px-2 py-1 fs-7 fw-semibold">{{ $row[$col] }}</span>
                                        @else
                                            <span class="text-muted">&mdash;</span>
                                        @endif
                                    @elseif (in_array($col, ['Doc Total', 'Line Total', 'Price']) && $row[$col] !== null)
                                        <span class="fw-semibold text-body-emphasis">Rp {{ number_format((float) $row[$col], 2, ',', '.') }}</span>
                                    @elseif (str_contains(strtolower($col), 'no') || str_contains(strtolower($col), 'code') || str_contains(strtolower($col), 'docnum'))
                                        <span class="badge bg-light text-dark border font-monospace">{{ $row[$col] }}</span>
                                    @else
                                        {{ $row[$col] ?? '' }}
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ max(count($columns), 1) }}" class="text-center text-muted py-5">
                                <div class="py-4">
                                    <i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i>
                                    <p class="mb-0 fw-medium">Tidak ada data sales order ditemukan.</p>
                                    <small class="text-secondary">Silakan periksa kembali jadwal transaksi atau filter akses modul di SAP.</small>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination Footer -->
        @if ($rows->hasPages())
            <div class="card-footer bg-white border-top py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span class="text-muted small">
                    Menampilkan <strong>{{ $rows->firstItem() }}</strong>&ndash;<strong>{{ $rows->lastItem() }}</strong> dari <strong>{{ $rows->total() }}</strong> baris
                </span>
                <div>
                    {{ $rows->onEachSide(1)->links('pagination::bootstrap-5') }}
                </div>
            </div>
        @endif
    </div>
</div>

{{-- Script fitur pencarian tabel instan --}}
<script>
    function filterTable() {
        const input = document.getElementById('tableSearchInput');
        const filter = input.value.toLowerCase();
        const table = document.getElementById('reportTable');
        const tr = table.getElementsByTagName('tr');

        for (let i = 1; i < tr.length; i++) {
            let showRow = false;
            const td = tr[i].getElementsByTagName('td');
            for (let j = 0; j < td.length; j++) {
                if (td[j]) {
                    if (td[j].textContent.toLowerCase().indexOf(filter) > -1) {
                        showRow = true;
                        break;
                    }
                }
            }
            if (tr[i].getElementsByTagName('td').length > 1) {
                tr[i].style.display = showRow ? "" : "none";
            }
        }
    }
</script>
@endsection