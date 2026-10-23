@extends('layouts.app')

@section('title', 'Query Manual (Ad-Hoc)')

@section('content')

    <h4 class="mb-3 fw-bold">Query Manual (Ad-Hoc)</h4>
    <p class="text-muted small">
        Tulis query SQL SELECT langsung ke database SAP HANA. Hasilnya akan tampil di bawah.
        Hanya query SELECT yang diperbolehkan (read-only).
    </p>

    <form method="POST" action="/reports/adhoc-query" class="mb-4">
        @csrf
        <textarea
            name="sql"
            rows="8"
            class="form-control font-monospace"
            placeholder="select ..."
            required
        >{{ $sql }}</textarea>

        @error('sql')
            <div class="text-danger small mt-1">{{ $message }}</div>
        @enderror

        <button type="submit" class="btn btn-primary mt-3">Jalankan Query</button>
    </form>

    @if ($error)
        <div class="alert alert-danger">
            Gagal menjalankan query: {{ $error }}
        </div>
    @endif

    @if ($ran && !$error)
        <div class="d-flex justify-content-between align-items-center mb-2">
            <strong>Hasil</strong>
            <span class="text-muted small">{{ count($rows) }} baris</span>
        </div>

        <div class="card shadow-sm border-0">
            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            @foreach ($columns as $col)
                                <th>{{ $col }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr>
                                @foreach ($columns as $col)
                                    <td>{{ $row[$col] }}</td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ max(count($columns), 1) }}" class="text-center text-muted py-4">
                                    Tidak ada data.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

@endsection