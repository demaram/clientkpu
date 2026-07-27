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

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
            <h5><i class="icon fas fa-check"></i> Berhasil!</h5>
            {{ session('success') }}
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

    @php
        $isRecapped = $existingRekap && $existingRekap->status === 'approved';
    @endphp

    @if($existingRekap)
        <div class="alert alert-{{ $existingRekap->status === 'approved' ? 'success' : 'warning' }} alert-dismissible">
            <i class="icon fas fa-{{ $existingRekap->status === 'approved' ? 'check' : 'exclamation-triangle' }}"></i>
            Rekap untuk periode <strong>{{ $periodStart->translatedFormat('F Y') }}</strong>
            sudah ada dengan status <strong>{{ strtoupper($existingRekap->status) }}</strong>.
            @if($isRecapped)
                Data yang sudah direkap tidak bisa di-reject atau di-request perubahan.
            @else
                Jika Anda menyetujui kembali, data rekap lama akan ditimpa.
            @endif
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Data Lembur — {{ $periodStart->translatedFormat('F Y') }}</h3>
        </div>
        <div class="card-body">
            @if($lemburs->isEmpty())
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Tidak ada data lembur untuk periode
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
                                <th>Status</th>
                                <th>Overtime Pay</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($lemburs as $i => $l)
                            <tr class="{{ $l->status === 'waiting_approval' ? 'table-warning' : '' }}">
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
                                <td>
                                    @if($l->status === 'approved')
                                        <span class="badge badge-success">Approved</span>
                                    @else
                                        <span class="badge badge-warning">Waiting Approval ({{ $l->current_approval_step }}/{{ $l->total_steps }})</span>
                                    @endif
                                </td>
                                <td class="text-success font-weight-bold">
                                    @if($l->overtime_pay)
                                        Rp {{ number_format($l->overtime_pay, 0, ',', '.') }}
                                    @else -
                                    @endif
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-info" title="Detail"
                                        onclick="showLemburDetail({{ $l->id }})">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    @if(!$isRecapped)
                                        @if($l->status === 'approved')
                                            <button type="button" class="btn btn-sm btn-warning" title="Request Update"
                                                onclick="openRequestUpdateModal({{ $l->id }})">
                                                <i class="fas fa-undo"></i>
                                            </button>
                                        @endif
                                        @if($l->status === 'waiting_approval' || $l->status === 'approved')
                                            <button type="button" class="btn btn-sm btn-danger" title="Reject"
                                                onclick="rejectLemburRow({{ $l->id }})">
                                                <i class="fas fa-times-circle"></i>
                                            </button>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="table-success">
                                <td colspan="9" class="text-right font-weight-bold">Total Overtime Pay:</td>
                                <td colspan="2" class="font-weight-bold text-success">Rp {{ number_format($totalPay, 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="mt-3 text-center">
                    <form method="POST" action="{{ route('admin.rekap-lembur.approve') }}" class="d-inline">
                        @csrf
                        <input type="hidden" name="month" value="{{ $month }}">
                        <button type="submit" name="action" value="approve" class="btn btn-success"
                            onclick="return confirm('Approve rekap lembur bulan {{ $periodStart->translatedFormat('F Y') }}? Total: Rp {{ number_format($totalPay, 0, '.', '.') }}\n\nPeringatan: setelah di-approve, data yang sudah direkap tidak bisa di-reject atau di-request perubahan.')">
                            <i class="fas fa-check-circle"></i> Approve Rekap
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>

    {{-- Detail Modal (AJAX) --}}
    <div class="modal fade" id="rekapDetailModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-info">
                    <h5 class="modal-title">Detail Lembur Karyawan</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <table class="table table-sm table-borderless">
                        <tr>
                            <th width="35%">Nama Karyawan</th>
                            <td id="rd-karyawan">-</td>
                        </tr>
                        <tr>
                            <th>EmpId</th>
                            <td id="rd-empid">-</td>
                        </tr>
                        <tr>
                            <th>Jabatan</th>
                            <td id="rd-jabatan">-</td>
                        </tr>
                        <tr>
                            <th>Tanggal</th>
                            <td id="rd-tanggal">-</td>
                        </tr>
                        <tr>
                            <th>Jam Mulai — Selesai</th>
                            <td><span id="rd-start">-</span> — <span id="rd-end">-</span></td>
                        </tr>
                        <tr>
                            <th>Durasi</th>
                            <td id="rd-durasi">-</td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td id="rd-status">-</td>
                        </tr>
                        <tr id="rd-row-step-progress" style="display:none;">
                            <th>Progres Step</th>
                            <td id="rd-step-progress">-</td>
                        </tr>
                        <tr>
                            <th>Overtime Pay</th>
                            <td id="rd-overtime-pay" class="font-weight-bold text-success">-</td>
                        </tr>
                        <tr id="rd-row-reopen-notes" style="display:none;">
                            <th>Alasan Request Update</th>
                            <td id="rd-reopen-notes" class="text-warning">-</td>
                        </tr>
                        <tr>
                            <th>Alasan / Keterangan</th>
                            <td id="rd-alasan">-</td>
                        </tr>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Request Update Modal --}}
    <form method="POST" action="{{ route('admin.rekap-lembur.request-update') }}" id="formRequestUpdate">
        @csrf
        <input type="hidden" name="month" value="{{ $month }}">
        <input type="hidden" name="lembur_id" id="requestUpdateLemburId" value="">
        <div class="modal fade" id="requestUpdateModal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-warning">
                        <h5 class="modal-title">Request Update Lembur</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p>Data ini akan dikembalikan ke status <strong>Waiting Approval</strong> pada step approval
                            pertama. Approval sebelumnya perlu diulang dari awal.</p>
                        <div class="form-group">
                            <label for="requestUpdateReason">Alasan <span class="text-danger">*</span></label>
                            <textarea name="reason" id="requestUpdateReason" class="form-control" rows="3" required
                                placeholder="Jelaskan alasan data ini perlu di-update ulang..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-undo"></i> Kirim Request Update
                        </button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@stop

@section('css')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        tfoot td { font-weight: bold; }
    </style>
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script>
        // Show the Detail modal for a single lembur row via AJAX.
        function showLemburDetail(id) {
            $.ajax({
                url: '{{ url("admin/rekap-lembur/lembur") }}/' + id + '/detail-ajax',
                type: 'GET',
                success: function(response) {
                    if (!response.success) return;
                    var data = response.data;

                    $('#rd-karyawan').text(data.karyawan || '-');
                    $('#rd-empid').text(data.empid || '-');
                    $('#rd-jabatan').text(data.jabatan || '-');
                    $('#rd-tanggal').text(data.tanggal || '-');
                    $('#rd-start').text(data.start_time || '-');
                    $('#rd-end').text(data.end_time || '-');
                    $('#rd-durasi').text(data.durasi || '-');
                    $('#rd-status').html('<span class="badge badge-' +
                        (data.status === 'Approved' ? 'success' : 'warning') +
                        '">' + data.status + '</span>');
                    $('#rd-overtime-pay').text(data.overtime_pay || '-');
                    $('#rd-alasan').text(data.alasan || '-');

                    if (data.step_progress) {
                        $('#rd-step-progress').text(data.step_progress);
                        $('#rd-row-step-progress').show();
                    } else {
                        $('#rd-row-step-progress').hide();
                    }

                    if (data.reopen_notes) {
                        $('#rd-reopen-notes').text(data.reopen_notes);
                        $('#rd-row-reopen-notes').show();
                    } else {
                        $('#rd-row-reopen-notes').hide();
                    }

                    $('#rekapDetailModal').modal('show');
                },
                error: function(xhr) {
                    Swal.fire('Error', xhr.responseJSON?.message || 'Gagal mengambil detail lembur', 'error');
                }
            });
        }

        // Open the Request Update modal for a single approved lembur row.
        function openRequestUpdateModal(id) {
            $('#requestUpdateLemburId').val(id);
            $('#requestUpdateReason').val('');
            $('#requestUpdateModal').modal('show');
        }

        // Reject a single waiting_approval lembur row — mirrors rejectLembur() on
        // the Data Lembur page (same reason prompt, same step-approver rule).
        function rejectLemburRow(id) {
            Swal.fire({
                title: 'Reject Lembur',
                html: '<p class="text-left mb-2">Masukkan alasan penolakan:</p>' +
                      '<textarea id="swal-reject-notes" class="swal2-textarea" placeholder="Alasan penolakan..." style="height:100px;"></textarea>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, Reject',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#dc3545',
                preConfirm: function() {
                    var notes = document.getElementById('swal-reject-notes').value.trim();
                    if (!notes) {
                        Swal.showValidationMessage('Alasan penolakan wajib diisi');
                        return false;
                    }
                    return notes;
                }
            }).then(function(result) {
                if (!result.isConfirmed) return;

                $.ajax({
                    url: '{{ route("admin.rekap-lembur.reject-record") }}',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        lembur_id: id,
                        notes: result.value,
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                icon: 'success',
                                title: response.message,
                                showConfirmButton: false,
                                timer: 1500,
                            }).then(function() {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Gagal', response.message, 'error');
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Error', xhr.responseJSON?.message || 'Gagal reject lembur', 'error');
                    }
                });
            });
        }

        @if(session('pending_list'))
            // Approve Rekap was blocked because some rows are still waiting_approval —
            // show the offending rows via SweetAlert instead of the plain alert above.
            $(function() {
                var pending = @json(session('pending_list'));
                var listHtml = '<ul class="text-left mb-0">' +
                    pending.map(function(p) {
                        return '<li>' + p.kode + ' a.n. ' + p.nama + '</li>';
                    }).join('') +
                    '</ul>';

                Swal.fire({
                    icon: 'error',
                    title: 'Approve Rekap Gagal',
                    html: 'Masih ada data yang belum di-approve/reject sepenuhnya:' + listHtml,
                });
            });
        @endif
    </script>
@stop
