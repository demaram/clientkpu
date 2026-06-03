@extends('adminlte::page')

@section('title', 'Form Rekap Lembur')

@section('content_header')
    <h1>Form Rekap Lembur</h1>
@stop

@section('content')
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
            <h5><i class="icon fas fa-times"></i> Error!</h5>
            {{ session('error') }}
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Pilih Periode Rekap</h3>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.rekap-lembur.form') }}" class="form-inline">
                <div class="form-group mr-3">
                    <label class="mr-2">Bulan:</label>
                    <input type="month" name="month" class="form-control" value="{{ $month }}" required>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Lihat Data
                </button>
                <a href="{{ route('admin.rekap-lembur.index') }}" class="btn btn-default ml-2">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
            </form>
        </div>
    </div>

    @if($existingRekap)
        <div class="alert alert-{{ $existingRekap->status === 'approved' ? 'success' : 'warning' }} alert-dismissible">
            <i class="icon fas fa-{{ $existingRekap->status === 'approved' ? 'check' : 'exclamation-triangle' }}"></i>
            Rekap untuk periode <strong>{{ $periodStart->translatedFormat('F Y') }}</strong>
            sudah ada dengan status <strong>{{ strtoupper($existingRekap->status) }}</strong>.
            Jika Anda menyetujui kembali, data rekap lama akan ditimpa.
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Data Lembur — {{ $periodStart->translatedFormat('F Y') }}</h3>
        </div>
        <div class="card-body">
            @if($lemburs->isEmpty())
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Tidak ada data lembur yang sudah disetujui untuk periode
                    <strong>{{ $periodStart->translatedFormat('F Y') }}</strong>.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-sm">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Kode</th>
                                <th>Nama Karyawan</th>
                                <th>EmpID</th>
                                <th>Tanggal</th>
                                <th>Jam Mulai</th>
                                <th>Jam Selesai</th>
                                <th>Durasi (jam)</th>
                                <th>Overtime Pay</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($lemburs as $i => $l)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>{{ $l->kode }}</td>
                                <td>
                                    @if($l->user)
                                        {{ $l->user->first_name }} {{ $l->user->last_name }}
                                    @else -
                                    @endif
                                </td>
                                <td>{{ $l->user->empid ?? '-' }}</td>
                                <td>{{ date('d/m/Y', strtotime($l->start)) }}</td>
                                <td>{{ date('H:i', strtotime($l->start)) }}</td>
                                <td>{{ $l->end ? date('H:i', strtotime($l->end)) : '-' }}</td>
                                <td>
                                    @if($l->counted_hours)
                                        @php
                                            $h = intdiv((int)($l->counted_hours * 60), 60);
                                            $m = (int)($l->counted_hours * 60) % 60;
                                        @endphp
                                        {{ $h }}j {{ $m }}m
                                    @else -
                                    @endif
                                </td>
                                <td class="text-success font-weight-bold">Rp {{ number_format($l->overtime_pay ?? 0, 0, ',', '.') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="table-success">
                                <td colspan="8" class="text-right font-weight-bold">Total Overtime Pay:</td>
                                <td class="font-weight-bold text-success">Rp {{ number_format($totalPay, 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="mt-3">
                    <form method="POST" action="{{ route('admin.rekap-lembur.approve') }}">
                        @csrf
                        <input type="hidden" name="month" value="{{ $month }}">
                        <button type="submit" name="action" value="approve" class="btn btn-success mr-2"
                            onclick="return confirm('Approve rekap lembur bulan {{ $periodStart->translatedFormat('F Y') }}? Total: Rp {{ number_format($totalPay, 0, '.', '.') }}')">
                            <i class="fas fa-check-circle"></i> Approve Rekap
                        </button>
                    </form>
                    <form method="POST" action="{{ route('admin.rekap-lembur.reject') }}" class="d-inline">
                        @csrf
                        <input type="hidden" name="month" value="{{ $month }}">
                        <button type="submit" class="btn btn-danger"
                            onclick="return confirm('Reject rekap lembur bulan {{ $periodStart->translatedFormat('F Y') }}?')">
                            <i class="fas fa-times-circle"></i> Reject Rekap
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>
@stop

@section('css')
    <style>
        tfoot td { font-weight: bold; }
    </style>
@stop
