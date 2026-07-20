@extends('adminlte::page')

@section('title', 'Data Lembur')

@section('content_header')
    <h1>Data Lembur Karyawan</h1>
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
            <h3 class="card-title">Filter Data Lembur</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="filterNama">Nama Karyawan</label>
                        <input type="text" id="filterNama" class="form-control" placeholder="Masukkan nama karyawan">
                    </div>
                </div>
                <div class="col-md-6">
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
                            <option value="pending">On Process</option>
                            <option value="waiting_approval">Waiting Approval</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
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

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Daftar Lembur</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="lemburTable" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th>Client</th>
                            <th>Nama Pegawai</th>
                            <th>EmpId</th>
                            <th>Pekerjaan</th>
                            <th>Kode</th>
                            <th>Tanggal</th>
                            <th>Waktu</th>
                            <th>Durasi</th>
                            <th>Terhitung</th>
                            <th>Alasan</th>
                            <th>Status</th>
                            <th>Overtime Pay</th>
                            <th width="15%">Action</th>
                        </tr>
                    </thead>    
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @include('admin.lembur._detail_modal')
@stop

@section('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.25/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        .btn-group .btn {
            margin-right: 2px;
        }

        #lemburTable th,
        #lemburTable td {
            white-space: nowrap;
        }
    </style>
@stop

@section('js')
    <script src="https://cdn.datatables.net/1.10.25/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.25/js/dataTables.bootstrap4.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script>
        const showOvertimePay = {{ $showOvertimePay ? 'true' : 'false' }};

        $(document).ready(function() {
            if (!showOvertimePay) {
                $('#row-overtime-pay').hide();
            }
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
                        'Januari',
                        'Februari',
                        'Maret',
                        'April',
                        'Mei',
                        'Juni',
                        'Juli',
                        'Agustus',
                        'September',
                        'Oktober',
                        'November',
                        'Desember'
                    ]
                }
            });

            $('#filterDateRange').on('apply.daterangepicker', function(ev, picker) {
                var start = picker.startDate.format('YYYY-MM-DD HH:mm');
                var end = picker.endDate.format('YYYY-MM-DD HH:mm');
                $(this).val(start + ' - ' + end);
                $('#filterStart').val(start);
                $('#filterEnd').val(end);
                table.ajax.reload();
            });

            $('#filterDateRange').on('cancel.daterangepicker', function() {
                $(this).val('');
                $('#filterStart').val('');
                $('#filterEnd').val('');
                table.ajax.reload();
            });

            let totalDurasiLabel = '0 jam 0 menit';

            // Initialize DataTable
            var table = $('#lemburTable').DataTable({
                processing: true,
                serverSide: true,
                scrollX: true,
                scrollCollapse: true,
                ajax: {
                    url: '{{ route("admin.lembur.index") }}',
                    data: function(d) {
                        d.nama_karyawan = $('#filterNama').val();
                        d.start_date = $('#filterStart').val();
                        d.end_date = $('#filterEnd').val();
                        d.status = $('#filterStatus').val();
                    },
                    dataSrc: function(json) {
                        totalDurasiLabel = json.total_durasi_label || '0 jam 0 menit';
                        return json.data;
                    }
                },
                columns: [
                    {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                    {data: 'client_nama', name: 'client_nama', orderable: false, searchable: false},
                    {data: 'karyawan', name: 'karyawan'},
                    {data: 'empid', name: 'empid'},
                    {data: 'pekerjaan', name: 'pekerjaan'},
                    {data: 'kode', name: 'kode'},
                    {data: 'tanggal', name: 'tanggal'},
                    {data: 'waktu', name: 'waktu'},
                    {data: 'durasi', name: 'durasi', searchable: false},
                    {data: 'counted_hours', name: 'counted_hours',},
                    {data: 'alasan', name: 'alasan'},
                    {data: 'status_badge', name: 'status'},
                    {data: 'overtime_pay', name: 'overtime_pay', visible: showOvertimePay},
                    {data: 'action', name: 'action', orderable: false, searchable: false}
                ],
                drawCallback: function() {
                    $('#totalDurasiCell').text(totalDurasiLabel);
                },
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.10.25/i18n/Indonesian.json'
                }
            });

            $('#btnApplyFilter').on('click', function() {
                table.ajax.reload();
            });

            $('#btnResetFilter').on('click', function() {
                $('#filterNama').val('');
                $('#filterDateRange').val('');
                $('#filterStart').val('');
                $('#filterEnd').val('');
                $('#filterStatus').val('all_status');
                table.ajax.reload();
            });

            $('#filterStatus').on('change', function() {
                table.ajax.reload();
            });

            let debounceTimer;
            $('#filterNama').on('keyup', function() {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(function() {
                    table.ajax.reload();
                }, 400);
            });
        });

        // Detail Lembur
        function detailLembur(id) {
            // Store the current lembur ID for approve/reject actions
            $('#detailModal').data('lembur-id', id);
            
            $.ajax({
                url: '{{ url("admin/lembur") }}/' + id,
                type: 'GET',
                success: function(response) {
                    if (response.success) {
                        var data = response.data;
                        
                        // Informasi Karyawan
                        $('#detail-client').text(data.client || '-');
                        $('#detail-karyawan').text(data.karyawan || '-');
                        $('#detail-empid').text(data.empid || '-');
                        $('#detail-jabatan').text(data.jabatan || '-');
                        $('#detail-rekening').text(data.rekening || '-');
                        
                        // Informasi Lembur
                        $('#detail-type').text(data.type || '-');
                        $('#detail-tanggal').text(data.tanggal || '-');
                        $('#detail-durasi').text(data.durasi || '-');
                        $('#detail-overtime-pay').text(data.overtime_pay || '-');
                        $('#detail-status').html('<span class="badge badge-' + 
                            (data.status === 'Approved' ? 'success' : (data.status === 'Rejected' ? 'danger' : 'warning')) + 
                            '">' + data.status + '</span>');

                        // Show step progress for multi-step
                        if (data.step_progress) {
                            $('#detail-step-progress').text('Step ' + data.step_progress);
                            $('#row-step-progress').show();
                        } else {
                            $('#row-step-progress').hide();
                        }

                        // Show approved/rejected at & by
                        var statusLower = (data.status || '').toLowerCase();
                        if (statusLower === 'approved' || statusLower === 'rejected') {
                            var statusLabel = statusLower === 'approved' ? 'Approved' : 'Rejected';
                            $('#label-status-at').text(statusLabel + ' At');
                            $('#label-status-by').text(statusLabel + ' By');
                            $('#label-status-from').text(statusLabel + ' From');
                            $('#detail-status-at').text(data.status_at || '-');
                            $('#detail-status-by').text(data.status_by_name || '-');
                            $('#detail-status-from').text(data.status_from || '-');
                            $('#row-status-at').show();
                            $('#row-status-by').show();
                            $('#row-status-from').show();
                            $('#btn-approve-modal, #btn-reject-modal').hide();
                        } else {
                            $('#row-status-at').hide();
                            $('#row-status-by').hide();
                            $('#row-status-from').hide();
                            if (data.can_act) {
                                $('#btn-approve-modal, #btn-reject-modal').show();
                            } else {
                                $('#btn-approve-modal, #btn-reject-modal').hide();
                            }
                        }

                        // Rejection notes row
                        if (statusLower === 'rejected') {
                            $('#detail-rejection-notes').text(data.rejection_notes || '-');
                            $('#row-rejection-notes').show();
                        } else {
                            $('#row-rejection-notes').hide();
                        }

                        // Reopen ("Request Update") notes row — shown when the recap user
                        // sent this record back to waiting_approval step 1
                        if (data.reopen_notes) {
                            $('#detail-reopen-notes').text(data.reopen_notes);
                            $('#row-reopen-notes').show();
                        } else {
                            $('#row-reopen-notes').hide();
                        }

                        // Last edited by/at row
                        if (data.edited_by_name) {
                            $('#detail-edited-by').text(data.edited_by_name);
                            $('#detail-edited-at').text(data.edited_at || '-');
                            $('#row-edited-by').show();
                            $('#row-edited-at').show();
                        } else {
                            $('#row-edited-by').hide();
                            $('#row-edited-at').hide();
                        }

                        $('#detail-alasan').text(data.alasan || '-');
                        
                        // Live lembur statistics
                        $('#detail-monthly-hours').text(data.monthly_counted_hours || '0 jam 0 menit');
                        $('#detail-monthly-period').text(data.monthly_period || '-');
                        $('#detail-weekly-hours').text(data.weekly_counted_hours || '0 jam 0 menit');
                        $('#detail-weekly-period').text(data.weekly_period || '-');
                        
                        // Check-in Details
                        $('#detail-start').text(data.start_time || '-');
                        
                        // Handle start photo
                        if (data.start_photo) {
                            $('#detail-start-photo img').attr('src', data.start_photo).show();
                            $('#detail-start-photo p').hide();
                        } else {
                            $('#detail-start-photo img').hide();
                            $('#detail-start-photo p').show();
                        }
                        
                        // Handle check-in location
                        if (data.check_in_location) {
                            $('#detail-checkin-address').text(data.check_in_location.address || 'Alamat tidak tersedia');
                            $('#detail-checkin-coords').html('<i class="fas fa-map-marker-alt"></i> ' + data.check_in_location.latitude + ', ' + data.check_in_location.longitude);
                        } else {
                            $('#detail-checkin-address').text('Lokasi tidak tersedia');
                            $('#detail-checkin-coords').html('<i class="fas fa-map-marker-alt"></i> -');
                        }
                        
                        // Check-out Details
                        $('#detail-end').text(data.end_time || '-');
                        
                        // Handle end photo
                        if (data.end_photo) {
                            $('#detail-end-photo img').attr('src', data.end_photo).show();
                            $('#detail-end-photo p').hide();
                        } else {
                            $('#detail-end-photo img').hide();
                            $('#detail-end-photo p').show();
                        }
                        
                        // Handle check-out location
                        if (data.check_out_location) {
                            $('#detail-checkout-address').text(data.check_out_location.address || 'Alamat tidak tersedia');
                            $('#detail-checkout-coords').html('<i class="fas fa-map-marker-alt"></i> ' + data.check_out_location.latitude + ', ' + data.check_out_location.longitude);
                        } else {
                            $('#detail-checkout-address').text('Lokasi tidak tersedia');
                            $('#detail-checkout-coords').html('<i class="fas fa-map-marker-alt"></i> -');
                        }
                        
                        $('#detailModal').modal('show');
                    }
                },
                error: function(xhr) {
                    alert('Error: ' + (xhr.responseJSON?.message || 'Gagal mengambil data'));
                }
            });
        }

        // Approve Lembur
        function approveLembur(id) {
            Swal.fire({
                title: 'Approve Lembur?',
                text: 'Apakah Anda yakin ingin menyetujui lembur ini?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Approve',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#28a745',
            }).then(function(result) {
                if (!result.isConfirmed) return;

                $.ajax({
                    url: '{{ url("admin/lembur") }}/' + id + '/approve',
                    type: 'POST',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                icon: 'success',
                                title: response.message,
                                showConfirmButton: false,
                                timer: 1500,
                            });
                            $('#lemburTable').DataTable().ajax.reload();
                        } else {
                            Swal.fire('Gagal', response.message, 'error');
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Error', xhr.responseJSON?.message || 'Gagal approve lembur', 'error');
                    }
                });
            });
        }

        // Reject Lembur
        function rejectLembur(id) {
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
                    url: '{{ url("admin/lembur") }}/' + id + '/reject',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
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
                            });
                            $('#lemburTable').DataTable().ajax.reload();
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

        // Approve from Modal
        function approveFromModal() {
            var id = $('#detailModal').data('lembur-id');
            if (id) {
                $('#detailModal').modal('hide');
                approveLembur(id);
            }
        }

        // Reject from Modal
        function rejectFromModal() {
            var id = $('#detailModal').data('lembur-id');
            if (id) {
                $('#detailModal').modal('hide');
                rejectLembur(id);
            }
        }
    </script>
@stop
