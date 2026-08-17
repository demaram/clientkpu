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

    @if(!$lemburs->isEmpty())
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Filter Data Lembur</h3>
            </div>
            <div class="card-body">
                @php
                    $karyawanOptions = $lemburs->map(function ($l) {
                        return $l->user ? trim($l->user->first_name . ' ' . $l->user->last_name) : null;
                    })->filter()->unique()->sort()->values();
                @endphp
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="filterNama">Nama Karyawan</label>
                            <select id="filterNama" class="form-control" style="width:100%">
                                <option value=""></option>
                                @foreach($karyawanOptions as $nama)
                                    <option value="{{ $nama }}">{{ $nama }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="form-group">
                            <label for="filterDateRange">Range Waktu</label>
                            <input type="text" id="filterDateRange" class="form-control" placeholder="Pilih range waktu" autocomplete="off" />
                            <input type="hidden" id="filterStart" />
                            <input type="hidden" id="filterEnd" />
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="filterStatus">Status</label>
                            <select id="filterStatus" class="form-control">
                                <option value="all_status">Semua Status</option>
                                <option value="approved">Approved</option>
                                <option value="waiting_approval">Waiting Approval</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-end">
                    <button type="button" id="btnResetFilter" class="btn btn-default mr-2">Reset</button>
                    <button type="button" id="btnApplyFilter" class="btn btn-primary">Cari</button>
                </div>
            </div>
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
                    <table id="rekapLemburTable" class="table table-bordered table-striped table-sm">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Kode</th>
                                <th>Nama Karyawan</th>
                                <th>EmpID</th>
                                <th>Pekerjaan</th>
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
                            @php
                                $namaKaryawan = $l->user ? trim($l->user->first_name . ' ' . $l->user->last_name) : '-';
                                $durasiJam = $l->counted_hours ?? 0;
                            @endphp
                            <tr class="{{ $l->status === 'waiting_approval' ? 'table-warning' : '' }}"
                                data-nama="{{ $namaKaryawan }}"
                                data-status="{{ $l->status }}"
                                data-start="{{ $l->start }}"
                                data-overtime-pay="{{ $l->overtime_pay ?? 0 }}">
                                <td>{{ $i + 1 }}</td>
                                <td>{{ $l->kode }}</td>
                                <td>{{ $namaKaryawan }}</td>
                                <td>{{ $l->user->empid ?? '-' }}</td>
                                <td>{{ $l->history->jabatan ?? '-' }}</td>
                                <td data-order="{{ strtotime($l->start) }}">{{ date('d/m/Y', strtotime($l->start)) }}</td>
                                <td data-order="{{ strtotime($l->start) }}">{{ date('H:i', strtotime($l->start)) }}</td>
                                <td data-order="{{ $l->end ? strtotime($l->end) : 0 }}">{{ $l->end ? date('H:i', strtotime($l->end)) : '-' }}</td>
                                <td data-order="{{ $durasiJam }}">
                                    @if($l->counted_hours)
                                        @php
                                            $h = intdiv((int)($l->counted_hours * 60), 60);
                                            $m = (int)($l->counted_hours * 60) % 60;
                                        @endphp
                                        {{ $h }}j {{ $m }}m
                                    @else -
                                    @endif
                                </td>
                                <td data-order="{{ $l->status }}">
                                    @if($l->status === 'approved')
                                        <span class="badge badge-success">Approved</span>
                                    @else
                                        <span class="badge badge-warning">Waiting Approval ({{ $l->current_approval_step }}/{{ $l->total_steps }})</span>
                                    @endif
                                </td>
                                <td class="text-success font-weight-bold" data-order="{{ $l->overtime_pay ?? 0 }}">
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
                                <td colspan="10" class="text-right font-weight-bold">Total Overtime Pay:</td>
                                <td colspan="2" class="font-weight-bold text-success" id="footerTotalPay">Rp {{ number_format($totalPay, 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="mt-3 text-center">
                    <form method="POST" action="{{ route('admin.rekap-lembur.approve') }}" class="d-inline" id="formApproveRekap">
                        @csrf
                        <input type="hidden" name="month" value="{{ $month }}">
                        <button type="button" name="action" value="approve" class="btn btn-success"
                            onclick="confirmApproveRekap('{{ $periodStart->translatedFormat('F Y') }}')">
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
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.25/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        tfoot td { font-weight: bold; }
        #rekapLemburTable th, #rekapLemburTable td { white-space: nowrap; }

        /* Match Select2's rendered height/alignment to Bootstrap 4 .form-control
           (calc(2.25rem + 2px)) — no select2-bootstrap4-theme is loaded, so
           Select2's own 28px default otherwise looks shorter than the other filters. */
        .select2-container .select2-selection--single {
            height: calc(2.25rem + 2px);
            border: 1px solid #ced4da;
            border-radius: 0.25rem;
        }
        .select2-container .select2-selection--single .select2-selection__rendered {
            line-height: calc(2.25rem);
            padding-left: 0.75rem;
            color: #495057;
        }
        .select2-container .select2-selection--single .select2-selection__arrow {
            height: calc(2.25rem);
            right: 3px;
        }
        .select2-container--default .select2-selection--single .select2-selection__clear {
            margin-right: 0.5rem;
        }
    </style>
@stop

@section('js')
    <script src="https://cdn.datatables.net/1.10.25/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.25/js/dataTables.bootstrap4.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script>
        // Format a number as "Rp x.xxx.xxx" (Indonesian thousands separator).
        function formatRupiah(num) {
            return 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(num || 0));
        }

        var rekapLemburTable;

        $(document).ready(function() {
            if ($('#rekapLemburTable').length === 0) {
                return; // no data for this period — table/filters weren't rendered
            }

            $('#filterNama').select2({
                width: '100%',
                placeholder: 'Semua Karyawan',
                allowClear: true,
            });

            $('#filterDateRange').daterangepicker({
                autoUpdateInput: false,
                timePicker: true,
                timePicker24Hour: true,
                timePickerSeconds: false,
                locale: {
                    format: 'YYYY-MM-DD HH:mm',
                    separator: ' - ',
                    applyLabel: 'Pilih',
                    cancelLabel: 'Hapus',
                    fromLabel: 'Dari',
                    toLabel: 'Sampai',
                    customRangeLabel: 'Custom',
                    weekLabel: 'M',
                    daysOfWeek: ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],
                    monthNames: [
                        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
                    ]
                }
            });

            $('#filterDateRange').on('apply.daterangepicker', function(ev, picker) {
                var start = picker.startDate.format('YYYY-MM-DD HH:mm');
                var end = picker.endDate.format('YYYY-MM-DD HH:mm');
                $(this).val(start + ' - ' + end);
                $('#filterStart').val(start);
                $('#filterEnd').val(end);
                rekapLemburTable.draw();
            });

            $('#filterDateRange').on('cancel.daterangepicker', function() {
                $(this).val('');
                $('#filterStart').val('');
                $('#filterEnd').val('');
                rekapLemburTable.draw();
            });

            // Client-side filter: reads the raw data-* attributes on each <tr>
            // (set from the un-formatted PHP values) rather than parsing the
            // rendered/formatted cell text, so badges/currency formatting/etc.
            // don't interfere with matching.
            $.fn.dataTable.ext.search.push(function(settings, searchData, index, rowData, counter) {
                if (settings.nTable.id !== 'rekapLemburTable') {
                    return true;
                }

                var row = $(rekapLemburTable.row(index).node());
                var namaFilter = $('#filterNama').val();
                var statusFilter = $('#filterStatus').val();
                var startFilter = $('#filterStart').val();
                var endFilter = $('#filterEnd').val();

                if (namaFilter && row.data('nama') !== namaFilter) {
                    return false;
                }

                if (statusFilter && statusFilter !== 'all_status' && String(row.data('status')) !== statusFilter) {
                    return false;
                }

                if (startFilter && endFilter) {
                    var rowStart = moment(String(row.data('start')));
                    var rangeStart = moment(startFilter, 'YYYY-MM-DD HH:mm');
                    var rangeEnd = moment(endFilter, 'YYYY-MM-DD HH:mm');
                    if (!rowStart.isBetween(rangeStart, rangeEnd, null, '[]')) {
                        return false;
                    }
                }

                return true;
            });

            rekapLemburTable = $('#rekapLemburTable').DataTable({
                pageLength: 25,
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'Semua']],
                columnDefs: [
                    { targets: [0, 11], orderable: false, searchable: false }
                ],
                order: [[5, 'asc']],
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.10.25/i18n/Indonesian.json'
                },
                drawCallback: function() {
                    var api = this.api();

                    // Renumber the "No" column against the filtered+sorted result,
                    // not the original server-rendered order.
                    var startIndex = api.context[0]._iDisplayStart;
                    api.column(0, { page: 'current' }).nodes().each(function(cell, i) {
                        cell.innerHTML = startIndex + i + 1;
                    });

                    // Total Overtime Pay always follows the CURRENT filtered result
                    // (across all pages, not just the visible one) — only approved
                    // rows carry a non-null overtime_pay, so this naturally excludes
                    // waiting_approval rows without needing an extra status check.
                    var total = 0;
                    api.rows({ filter: 'applied' }).nodes().each(function(node) {
                        total += parseFloat($(node).data('overtime-pay')) || 0;
                    });
                    $('#footerTotalPay').text(formatRupiah(total));
                }
            });

            $('#btnApplyFilter').on('click', function() {
                rekapLemburTable.draw();
            });

            $('#btnResetFilter').on('click', function() {
                $('#filterNama').val(null).trigger('change');
                $('#filterDateRange').val('');
                $('#filterStart').val('');
                $('#filterEnd').val('');
                $('#filterStatus').val('all_status');
                rekapLemburTable.draw();
            });

            $('#filterStatus').on('change', function() {
                rekapLemburTable.draw();
            });

            $('#filterNama').on('change', function() {
                rekapLemburTable.draw();
            });
        });

        // Approve Rekap confirmation — shows the currently DISPLAYED (filtered) total,
        // but warns explicitly when a filter is active, since Approve always processes
        // the whole month's data regardless of what's filtered on screen right now.
        function confirmApproveRekap(periodLabel) {
            var namaFilter = $('#filterNama').val();
            var statusFilter = $('#filterStatus').val();
            var dateFilterActive = !!$('#filterStart').val();
            var filterActive = !!namaFilter || (statusFilter && statusFilter !== 'all_status') || dateFilterActive;

            var totalText = $('#footerTotalPay').text();

            var warningHtml = filterActive
                ? '<div class="alert alert-warning text-left mt-2 mb-0">' +
                  '<i class="fas fa-exclamation-triangle"></i> Filter sedang aktif — Total di atas adalah hasil filter, ' +
                  'namun Approve akan tetap memproses <strong>SEMUA</strong> data bulan ini.</div>'
                : '';

            Swal.fire({
                title: 'Approve Rekap Lembur?',
                html: 'Approve rekap lembur bulan <strong>' + periodLabel + '</strong>?<br>' +
                      'Total: <strong>' + totalText + '</strong>' + warningHtml +
                      '<div class="mt-2"><small>Peringatan: setelah di-approve, data yang sudah direkap tidak bisa di-reject atau di-request perubahan.</small></div>',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Approve',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#28a745',
            }).then(function(result) {
                if (result.isConfirmed) {
                    document.getElementById('formApproveRekap').submit();
                }
            });
        }

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
