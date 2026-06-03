@extends('adminlte::page')

@section('title', 'Detail Rekap Lembur')

@section('content_header')
    <h1>Detail Rekap Lembur</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title">Detail Rekap Lembur</h3>
            <a href="{{ route('admin.rekap-lembur.index') }}" class="btn btn-default btn-sm">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
        <div class="card-body">

            {{-- Summary block --}}
            <div class="row mb-4">
                <div class="col-md-6">
                    <table class="table table-sm table-borderless">
                        <tr>
                            <th width="40%">Client</th>
                            <td>{{ $rekap->client->nama ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Periode</th>
                            <td>{{ \Carbon\Carbon::parse($rekap->period_start)->translatedFormat('F Y') }}</td>
                        </tr>
                        <tr>
                            <th>Tanggal Rekap</th>
                            <td>{{ \Carbon\Carbon::parse($rekap->actioned_at)->format('d/m/Y H:i') }}</td>
                        </tr>
                        <tr>
                            <th>Direkap Oleh</th>
                            <td>{{ $rekap->recapUser ? $rekap->recapUser->first_name . ' ' . $rekap->recapUser->last_name : '-' }}</td>
                        </tr>
                        <tr>
                            <th>Jumlah Lembur</th>
                            <td>{{ $rekap->total_lembur }}</td>
                        </tr>
                        <tr>
                            <th>Total Pay</th>
                            <td class="text-success font-weight-bold">Rp {{ number_format($rekap->total_pay, 0, ',', '.') }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <h5>Daftar Lembur per Karyawan</h5>

            {{-- Outer table: one row per employee. DataTable intentionally NOT used here —
                 mixed colspan rows (detail-row) break DT cell indexing. --}}
            <table class="table table-striped table-bordered table-hover" width="100%">
                <thead>
                    <tr>
                        <th width="30px"></th>
                        <th>Nama Karyawan</th>
                        <th>EmpID</th>
                        <th>Jumlah Lembur</th>
                        <th>Total Overtime Pay</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($grouped as $userId => $userItems)
                        @php
                            $firstItem = $userItems->first();
                            $user      = $firstItem->lembur?->user;
                            $fullName  = $user ? ($user->first_name . ' ' . $user->last_name) : '-';
                            $empId     = $user->empid ?? '-';
                            $totalPay  = $userItems->sum('overtime_pay');
                            $rowId     = 'detail-' . ($userId ?? 'null');
                        @endphp
                        <tr class="employee-row" data-target="{{ $rowId }}">
                            <td class="text-center">
                                <i class="fas fa-chevron-right toggle-icon"></i>
                            </td>
                            <td>{{ $fullName }}</td>
                            <td>{{ $empId }}</td>
                            <td>{{ $userItems->count() }}</td>
                            <td class="text-success font-weight-bold">Rp {{ number_format($totalPay, 0, ',', '.') }}</td>
                        </tr>
                        <tr class="detail-row" id="{{ $rowId }}">
                            <td colspan="5">
                                <div class="detail-inner">
                                    <table class="table table-sm table-bordered mb-0" width="100%">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>No</th>
                                                <th>Kode</th>
                                                <th>Tanggal</th>
                                                <th>Jam Mulai</th>
                                                <th>Jam Selesai</th>
                                                <th>Durasi (jam)</th>
                                                <th>Overtime Pay</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($userItems->sortBy('lembur.start') as $item)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $item->lembur->kode ?? '-' }}</td>
                                                <td>{{ $item->lembur ? date('d/m/Y', strtotime($item->lembur->start)) : '-' }}</td>
                                                <td>{{ $item->lembur ? date('H:i', strtotime($item->lembur->start)) : '-' }}</td>
                                                <td>{{ ($item->lembur && $item->lembur->end) ? date('H:i', strtotime($item->lembur->end)) : '-' }}</td>
                                                <td>{{ $item->counted_hours ? number_format($item->counted_hours, 2) : '-' }}</td>
                                                <td class="text-success">Rp {{ number_format($item->overtime_pay ?? 0, 0, ',', '.') }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="table-success">
                        <td colspan="4" class="text-right font-weight-bold">Total Overtime Pay:</td>
                        <td class="font-weight-bold text-success">Rp {{ number_format($rekap->total_pay, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>

        </div>
    </div>
@stop

@section('css')
    <style>
        .employee-row { cursor: pointer; }
        .employee-row:hover { background-color: #f5f5f5; }
        .detail-row { display: none; }
        .detail-row td { padding: 0 !important; }
        .detail-inner { padding: 10px 20px; background: #fafafa; border-top: 1px solid #ddd; }
        .toggle-icon { transition: transform 0.2s; }
        .employee-row.open .toggle-icon { transform: rotate(90deg); }
    </style>
@stop

@section('js')
    <script>
        $(document).ready(function () {
            $(document).on('click', '.employee-row', function () {
                var target = $(this).data('target');
                var $detailRow = $('#' + target);
                $(this).toggleClass('open');
                $detailRow.toggle();
            });
        });
    </script>
@stop
