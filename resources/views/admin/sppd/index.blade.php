@extends('adminlte::page')

@section('title', 'e-SPPD')

@section('content_header')
    <h1>e-SPPD (Surat Perintah Perjalanan Dinas)</h1>
@stop

@section('content')

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">
    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
    <h5><i class="icon fas fa-check"></i> Success!</h5>
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
                        <option value="approved">Approved</option>
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
        <h3 class="card-title">Daftar SPPD</h3>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="sppdTable" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th width="5%">#</th>
                        <th>Client</th>
                        <th>Nama Karyawan</th>
                        <th>Emp ID</th>
                        <th>Kode</th>
                        <th>Tgl Berangkat</th>
                        <th>Tgl Kembali</th>
                        <th>Durasi</th>
                        <th>Status</th>
                        <th width="15%">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

{{-- Detail Modal --}}
@include('admin.sppd._detail_modal')

{{-- Reject Modal --}}
<div class="modal fade" id="rejectModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tolak SPPD</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="rejectId">
                <div class="form-group">
                    <label>Alasan Penolakan <span class="text-danger">*</span></label>
                    <textarea id="rejectNotes" class="form-control" rows="3" placeholder="Isi alasan penolakan..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger" onclick="submitReject()">
                    <i class="fas fa-times"></i> Tolak
                </button>
            </div>
        </div>
    </div>
</div>

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
var sppdTable;

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

    sppdTable = $('#sppdTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("admin.sppd.index") }}',
            data: function (d) {
                d.nama_filter   = $('#filterNama').val();
                d.start_date    = $('#filterStartDate').val();
                d.end_date      = $('#filterEndDate').val();
                d.status_filter = $('#filterStatus').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'client_nama', name: 'client_nama', orderable: false, searchable: false },
            { data: 'karyawan', name: 'karyawan' },
            { data: 'empid', name: 'empid' },
            { data: 'kode', name: 'kode' },
            { data: 'tanggal_berangkat', name: 'tanggal_berangkat' },
            { data: 'tanggal_kembali', name: 'tanggal_kembali' },
            { data: 'durasi', name: 'durasi', orderable: false },
            { data: 'status_badge', name: 'status_badge', orderable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ],
        language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/id.json' },
        order: [[0, 'asc']],
    });

    $('#btnCari').on('click', function () { sppdTable.ajax.reload(); });
    $('#btnReset').on('click', function () {
        $('#filterNama').val('');
        $('#filterDateRange').val('');
        $('#filterStartDate').val('');
        $('#filterEndDate').val('');
        $('#filterStatus').val('');
        sppdTable.ajax.reload();
    });
});

function detailSppd(id) {
    $.get('{{ url("admin/sppd") }}/' + id, function (res) {
        if (!res.success) { alert(res.message ?? 'Gagal memuat data.'); return; }
        var d = res.data;

        $('#detail-kode').text(d.kode);
        $('#detail-karyawan').text(d.karyawan);
        $('#detail-empid').text(d.empid);
        $('#detail-jabatan').text(d.jabatan);
        $('#detail-lokasi').text(d.lokasi);
        $('#detail-keterangan').text(d.keterangan);
        $('#detail-tgl-berangkat').text(d.tanggal_berangkat);
        $('#detail-tgl-kembali').text(d.tanggal_kembali);
        $('#detail-status').html(statusBadge(d.status, d.step_progress));
        $('#detail-total-biaya').text(d.total_biaya);
        $('#detail-total-diterima').text(d.total_diterima_pegawai);

        // Rejection info
        if (d.status === 'rejected' && d.rejection_notes) {
            $('#detail-rejection-box').removeClass('d-none').find('p').text(d.rejection_notes);
        } else {
            $('#detail-rejection-box').addClass('d-none');
        }

        // Costs
        var costsHtml = '';
        if (d.costs && d.costs.length) {
            d.costs.forEach(function (c) {
                costsHtml += '<tr>' +
                    '<td>' + (c.uraian ?? '-') + '</td>' +
                    '<td class="text-right">Rp ' + c.nominal + '</td>' +
                    '<td class="text-center">' + (c.hari ?? '-') + '</td>' +
                    '<td class="text-right">Rp ' + c.subtotal + '</td>' +
                    '<td class="text-center">' + (c.diterima_pegawai ? '<i class="fas fa-check text-success"></i>' : '') + '</td>' +
                    '</tr>';
            });
        } else {
            costsHtml = '<tr><td colspan="5" class="text-center">Belum ada rincian biaya.</td></tr>';
        }
        $('#detail-costs-body').html(costsHtml);

        // Attachments
        var attHtml = '';
        if (d.attachments && d.attachments.length) {
            d.attachments.forEach(function (att) {
                attHtml += '<tr><td>' + (att.jenis ?? '-') + '</td>' +
                    '<td>' + (att.url ? '<a href="' + att.url + '" target="_blank">' + (att.file_name ?? 'Lihat File') + '</a>' : (att.file_name ?? '-')) + '</td></tr>';
            });
        } else {
            attHtml = '<tr><td colspan="2" class="text-center">Tidak ada dokumen.</td></tr>';
        }
        $('#detail-attachments-body').html(attHtml);

        // Approval logs
        var logHtml = '';
        if (d.approval_logs && d.approval_logs.length) {
            d.approval_logs.forEach(function (log) {
                logHtml += '<tr>' +
                    '<td>' + (log.step_order ?? '-') + '</td>' +
                    '<td>' + (log.step_name ?? '-') + '</td>' +
                    '<td>' + statusBadge(log.status) + '</td>' +
                    '<td>' + (log.notes ?? '-') + '</td>' +
                    '<td>' + (log.acted_at ?? '-') + '</td>' +
                    '</tr>';
            });
        } else {
            logHtml = '<tr><td colspan="5" class="text-center">Belum ada log approval.</td></tr>';
        }
        $('#detail-logs-body').html(logHtml);

        // Action buttons
        $('#detail-approve-btn').addClass('d-none');
        $('#detail-reject-btn').addClass('d-none');
        if (d.can_act) {
            $('#detail-approve-btn').removeClass('d-none').off('click').on('click', function () {
                $('#detailModal').modal('hide');
                approveSppd(d.id);
            });
            $('#detail-reject-btn').removeClass('d-none').off('click').on('click', function () {
                $('#detailModal').modal('hide');
                rejectSppd(d.id);
            });
        }

        $('#detailModal').modal('show');
    }).fail(function () {
        alert('Gagal memuat detail SPPD.');
    });
}

function statusBadge(status, stepProgress) {
    var label = status;
    if (status === 'waiting_approval') label = 'Waiting Approval' + (stepProgress ? ' (' + stepProgress + ')' : '');
    else if (status === 'approved') label = 'Approved';
    else if (status === 'rejected') label = 'Rejected';

    var cls = status === 'approved' ? 'success' : (status === 'rejected' ? 'danger' : 'secondary');
    return '<span class="badge badge-' + cls + '">' + label + '</span>';
}

function approveSppd(id) {
    if (!confirm('Approve SPPD ini?')) return;
    $.post('{{ url("admin/sppd") }}/' + id + '/approve', { _token: '{{ csrf_token() }}' })
        .done(function (res) {
            alert(res.message ?? (res.success ? 'Berhasil.' : 'Gagal.'));
            sppdTable.ajax.reload();
        })
        .fail(function (xhr) { alert(xhr.responseJSON?.message ?? 'Terjadi kesalahan.'); });
}

function rejectSppd(id) {
    $('#rejectId').val(id);
    $('#rejectNotes').val('');
    $('#rejectModal').modal('show');
}

function submitReject() {
    var id    = $('#rejectId').val();
    var notes = $('#rejectNotes').val().trim();
    if (!notes) { alert('Alasan penolakan wajib diisi.'); return; }

    $.post('{{ url("admin/sppd") }}/' + id + '/reject', { _token: '{{ csrf_token() }}', notes: notes })
        .done(function (res) {
            if (res.success) {
                $('#rejectModal').modal('hide');
                alert(res.message ?? 'SPPD berhasil di-reject.');
                sppdTable.ajax.reload();
            } else {
                alert(res.message ?? 'Gagal melakukan penolakan.');
            }
        })
        .fail(function (xhr) { alert(xhr.responseJSON?.message ?? 'Terjadi kesalahan.'); });
}
</script>
@stop
