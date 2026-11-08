@extends('adminlte::page')

@section('title', 'Report SPPD')

@section('content_header')
    <h1>Report SPPD</h1>
@stop

@section('content')

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Filter</h3>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label>Nama Karyawan</label>
                    <input type="text" id="filterNama" class="form-control" placeholder="Cari nama...">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Periode (Tgl Berangkat)</label>
                    <input type="text" id="filterDateRange" class="form-control" placeholder="Pilih tanggal..." autocomplete="off" readonly>
                    <input type="hidden" id="filterStartDate">
                    <input type="hidden" id="filterEndDate">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Status</label>
                    <select id="filterStatus" class="form-control">
                        <option value="">Semua Status</option>
                        <option value="waiting_approval">Waiting Approval</option>
                        <option value="approved" selected>Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
            </div>
            <div class="col-md-3 d-flex align-items-end pb-3">
                <button id="btnCari" class="btn btn-primary mr-2"><i class="fas fa-search"></i> Cari</button>
                <button id="btnReset" class="btn btn-default"><i class="fas fa-redo"></i> Reset</button>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">SPPD Karyawan Konfigurasi Lembur Anda</h3>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="reportSppdTable" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th width="4%">#</th>
                        <th>Nama Karyawan</th>
                        <th>Emp ID</th>
                        <th>Kode</th>
                        <th>Tgl Berangkat</th>
                        <th>Tgl Kembali</th>
                        <th>Durasi</th>
                        <th>Lokasi</th>
                        <th>Status</th>
                        <th class="text-right">Total Biaya</th>
                        <th class="text-right">Diterima Pegawai</th>
                        <th class="text-right">Diterima Perusahaan</th>
                        <th width="6%">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
                <tfoot>
                    <tr class="font-weight-bold">
                        <td colspan="9" class="text-right">Total (semua baris sesuai filter)</td>
                        <td class="text-right" id="footTotalBiaya">-</td>
                        <td class="text-right" id="footTotalPegawai">-</td>
                        <td class="text-right" id="footTotalPerusahaan">-</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

{{-- Detail Modal (baca-saja: tombol Approve/Reject tidak pernah muncul di sini) --}}
@include('admin.sppd._detail_modal')

@stop

@section('css')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">
@stop

@section('js')
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/moment/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<script>
var reportTable;

$(document).ready(function () {
    $('#filterDateRange').daterangepicker({
        autoUpdateInput: false,
        locale: { cancelLabel: 'Clear', format: 'DD/MM/YYYY' }
    });
    $('#filterDateRange').on('apply.daterangepicker', function (ev, picker) {
        $(this).val(picker.startDate.format('DD/MM/YYYY') + ' - ' + picker.endDate.format('DD/MM/YYYY'));
        $('#filterStartDate').val(picker.startDate.format('YYYY-MM-DD'));
        $('#filterEndDate').val(picker.endDate.format('YYYY-MM-DD'));
    });
    $('#filterDateRange').on('cancel.daterangepicker', function () {
        $(this).val('');
        $('#filterStartDate').val('');
        $('#filterEndDate').val('');
    });

    reportTable = $('#reportSppdTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("admin.report-sppd.index") }}',
            data: function (d) {
                d.nama_filter   = $('#filterNama').val();
                d.start_date    = $('#filterStartDate').val();
                d.end_date      = $('#filterEndDate').val();
                d.status_filter = $('#filterStatus').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'karyawan', name: 'karyawan', orderable: false, searchable: false },
            { data: 'empid', name: 'empid', orderable: false, searchable: false },
            { data: 'kode', name: 'kode', orderable: false, searchable: false },
            { data: 'tanggal_berangkat', name: 'tanggal_berangkat', orderable: false, searchable: false },
            { data: 'tanggal_kembali', name: 'tanggal_kembali', orderable: false, searchable: false },
            { data: 'durasi', name: 'durasi', orderable: false, searchable: false },
            { data: 'lokasi', name: 'lokasi', orderable: false, searchable: false },
            { data: 'status_badge', name: 'status_badge', orderable: false, searchable: false },
            { data: 'total_biaya', name: 'total_biaya', orderable: false, searchable: false, className: 'text-right' },
            { data: 'total_diterima_pegawai', name: 'total_diterima_pegawai', orderable: false, searchable: false, className: 'text-right' },
            { data: 'total_diterima_perusahaan', name: 'total_diterima_perusahaan', orderable: false, searchable: false, className: 'text-right' },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ],
        language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json' },
        searching: false,
        ordering: false,
    });

    // Footer totals come with every response and cover all rows matching the filters, not just this page.
    reportTable.on('xhr.dt', function (e, settings, json) {
        if (!json || !json.totals) { return; }
        $('#footTotalBiaya').text(json.totals.total_biaya);
        $('#footTotalPegawai').text(json.totals.total_diterima_pegawai);
        $('#footTotalPerusahaan').text(json.totals.total_diterima_perusahaan);
    });

    $('#btnCari').on('click', function () { reportTable.ajax.reload(); });
    $('#btnReset').on('click', function () {
        $('#filterNama').val('');
        $('#filterDateRange').val('');
        $('#filterStartDate').val('');
        $('#filterEndDate').val('');
        $('#filterStatus').val('approved');
        reportTable.ajax.reload();
    });
});
</script>
@include('admin.sppd._detail_script', ['detailUrl' => url('admin/report-sppd')])
@stop
