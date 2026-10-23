@extends('layouts.app')

@section('title', 'Master Items')

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0 fw-bold">Master Items</h4>
        <span class="text-muted small">Total: {{ $items->total() }} item</span>
    </div>

    <form method="GET" action="/items" class="mb-3">
        <div class="input-group" style="max-width: 400px;">
            <input
                type="text"
                name="q"
                value="{{ $search }}"
                class="form-control"
                placeholder="Cari kode atau nama item..."
            >
            <button class="btn btn-primary" type="submit">Cari</button>
            @if($search)
                <a href="/items" class="btn btn-outline-secondary">Reset</a>
            @endif
        </div>
    </form>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Kode Item</th>
                        <th>Nama Item</th>
                        <th>Group</th>
                        <th class="text-end">Stok</th>
                        <th class="text-end">Avg Price</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr>
                            <td><code>{{ $item->item_code }}</code></td>
                            <td>{{ $item->item_name }}</td>
                            <td>{{ $item->item_group_code }}</td>
                            <td class="text-end">{{ number_format($item->in_stock, 2) }}</td>
                            <td class="text-end">{{ number_format($item->avg_price, 2) }}</td>
                            <td>
                                @if($item->valid_status === 'tYES')
                                    <span class="badge bg-success">Aktif</span>
                                @else
                                    <span class="badge bg-secondary">Nonaktif</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                Belum ada data. Jalankan <code>php artisan sap:sync-items</code> dulu.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $items->links('pagination::bootstrap-5') }}
    </div>

@endsection