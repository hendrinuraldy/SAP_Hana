@extends('layouts.app')

@section('title', 'Business Partners')

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0 fw-bold">Business Partners</h4>
        <span class="text-muted small">Total: {{ $partners->total() }} partner</span>
    </div>

    <form method="GET" action="/business-partners" class="mb-3">
        <div class="input-group" style="max-width: 400px;">
            <input
                type="text"
                name="q"
                value="{{ $search }}"
                class="form-control"
                placeholder="Cari kode atau nama partner..."
            >
            <button class="btn btn-primary" type="submit">Cari</button>
            @if($search)
                <a href="/business-partners" class="btn btn-outline-secondary">Reset</a>
            @endif
        </div>
    </form>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Kode</th>
                        <th>Nama</th>
                        <th>Tipe</th>
                        <th>Telp</th>
                        <th>Email</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($partners as $bp)
                        <tr>
                            <td><code>{{ $bp->card_code }}</code></td>
                            <td>{{ $bp->card_name }}</td>
                            <td>
                                @if($bp->card_type === 'C')
                                    <span class="badge bg-info text-dark">Customer</span>
                                @elseif($bp->card_type === 'S')
                                    <span class="badge bg-warning text-dark">Supplier</span>
                                @else
                                    <span class="badge bg-secondary">{{ $bp->card_type }}</span>
                                @endif
                            </td>
                            <td>{{ $bp->phone }}</td>
                            <td>{{ $bp->email }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                Belum ada data. Jalankan <code>php artisan sap:sync-business-partners</code> dulu.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $partners->links('pagination::bootstrap-5') }}
    </div>

@endsection