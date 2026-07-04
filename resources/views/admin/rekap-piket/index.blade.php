@extends('adminlte::page')

@section('title', 'Rekap Piket')

@section('content_header')
    <h1>Rekap Piket</h1>
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
            <h3 class="card-title">Rekap Piket Bulanan</h3>
            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalAddRekap">
                <i class="fas fa-plus"></i> Add Rekapitulasi
            </button>
        </div>
        <div class="card-body">
            @if($rekaps->isEmpty())
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Belum ada rekap piket. Klik tombol <strong>Add Rekapitulasi</strong> untuk memulai.
                </div>
            @else
                <div class="table-responsive">
                    <table id="rekapTable" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Periode</th>
                                <th>Jumlah Piket</th>
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
                                    <a href="{{ route('admin.rekap-piket.detail', $r->id) }}"
                                       class="btn btn-info btn-sm">
                                        <i class="fas fa-list-alt"></i> Detail
                                    </a>
                                    <a href="{{ route('admin.rekap-piket.form', ['month' => \Carbon\Carbon::parse($r->period_start)->format('Y-m')]) }}"
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

    {{-- Modal: Add Rekap --}}
    <div class="modal fade" id="modalAddRekap" tabindex="-1" role="dialog" aria-labelledby="modalAddRekapLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalAddRekapLabel">Pilih Periode Rekap</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="rekap-warning" class="alert alert-warning d-none">
                        <i class="fas fa-exclamation-triangle"></i>
                        Bulan ini sudah memiliki rekap <strong>approved</strong>. Jika dilanjutkan, data rekap lama akan ditimpa.
                    </div>
                    <div class="form-group">
                        <label for="inputMonth">Bulan Rekap</label>
                        <input type="month" id="inputMonth" class="form-control"
                               value="{{ \Carbon\Carbon::now()->format('Y-m') }}" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary" id="btnPilihPeriode">
                        <i class="fas fa-arrow-right"></i> Lanjut
                    </button>
                </div>
            </div>
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

            var approvedMonths = @json($approvedMonths);
            var formUrl = '{{ route('admin.rekap-piket.form') }}';

            $('#inputMonth').on('change', function () {
                var selected = $(this).val();
                if (approvedMonths.indexOf(selected) !== -1) {
                    $('#rekap-warning').removeClass('d-none');
                } else {
                    $('#rekap-warning').addClass('d-none');
                }
            });

            $('#btnPilihPeriode').on('click', function () {
                var month = $('#inputMonth').val();
                if (!month) {
                    alert('Pilih bulan terlebih dahulu.');
                    return;
                }
                window.location.href = formUrl + '?month=' + month;
            });
        });
    </script>
@stop
