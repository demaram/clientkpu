@extends('adminlte::page')

@section('title', 'Rekap Lembur')

@section('content_header')
    <h1>Rekap Lembur</h1>
@stop

@section('content')
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
            <h5><i class="icon fas fa-check"></i> Berhasil!</h5>
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
            <h5><i class="icon fas fa-times"></i> Error!</h5>
            {{ session('error') }}
        </div>
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title">Rekap Lembur Bulanan</h3>
            @if(!$hasApprovedThisMonth)
                <a href="{{ route('admin.rekap-lembur.form') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Rekap Bulan Ini
                </a>
            @endif
        </div>
        <div class="card-body">
            @if($rekaps->isEmpty())
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Belum ada rekap lembur. Klik tombol <strong>Rekap Bulan Ini</strong> untuk memulai.
                </div>
            @else
                <div class="table-responsive">
                    <table id="rekapTable" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Periode</th>
                                <th>Jumlah Lembur</th>
                                <th>Total Pay</th>
                                <th>Status</th>
                                <th>Tanggal Rekap</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rekaps as $i => $r)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>{{ \Carbon\Carbon::parse($r->period_start)->translatedFormat('F Y') }}</td>
                                <td>{{ $r->total_lembur }}</td>
                                <td class="text-success font-weight-bold">Rp {{ number_format($r->total_pay, 0, ',', '.') }}</td>
                                <td>
                                    @if($r->status === 'approved')
                                        <span class="badge badge-success">Approved</span>
                                    @else
                                        <span class="badge badge-danger">Rejected</span>
                                    @endif
                                </td>
                                <td>{{ \Carbon\Carbon::parse($r->actioned_at)->format('d/m/Y H:i') }}</td>
                                <td>
                                    <a href="{{ route('admin.rekap-lembur.form', ['month' => \Carbon\Carbon::parse($r->period_start)->format('Y-m')]) }}"
                                       class="btn btn-warning btn-sm"
                                       onclick="return confirm('Re-rekap akan menimpa data rekap lama untuk periode ini. Lanjutkan?')">
                                        <i class="fas fa-redo"></i> Re-rekap
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@stop

@section('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.25/css/dataTables.bootstrap4.min.css">
@stop

@section('js')
    <script src="https://cdn.datatables.net/1.10.25/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.25/js/dataTables.bootstrap4.min.js"></script>
    <script>
        $(document).ready(function () {
            $('#rekapTable').DataTable({
                order: [[1, 'desc']],
                language: { url: '//cdn.datatables.net/plug-ins/1.10.25/i18n/Indonesian.json' }
            });
        });
    </script>
@stop
