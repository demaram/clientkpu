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
            <h3 class="card-title">Daftar Lembur</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="lemburTable" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th>Karyawan</th>
                            <th>Client</th>
                            <th>Tanggal</th>
                            <th>Waktu</th>
                            <th>Type</th>
                            <th>Alasan</th>
                            <th>Status</th>
                            <th width="15%">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Detail Modal -->
    <div class="modal fade" id="detailModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-info">
                    <h5 class="modal-title">Detail Lembur</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr>
                                    <th width="40%">Karyawan</th>
                                    <td id="detail-karyawan">-</td>
                                </tr>
                                <tr>
                                    <th>Client</th>
                                    <td id="detail-client">-</td>
                                </tr>
                                <tr>
                                    <th>Type</th>
                                    <td id="detail-type">-</td>
                                </tr>
                                <tr>
                                    <th>Mulai</th>
                                    <td id="detail-start">-</td>
                                </tr>
                                <tr>
                                    <th>Selesai</th>
                                    <td id="detail-end">-</td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td id="detail-status">-</td>
                                </tr>
                                <tr>
                                    <th>Upah Lembur</th>
                                    <td id="detail-overtime-pay">-</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>Alasan:</h6>
                            <p id="detail-alasan" class="border p-2 rounded">-</p>
                            
                            <h6 class="mt-3">Foto Check-in:</h6>
                            <div id="detail-start-photo" class="border p-2 rounded text-center">
                                <img src="" alt="Start Photo" class="img-fluid" style="max-height: 200px; display: none;">
                                <p class="text-muted mb-0">Tidak ada foto</p>
                            </div>
                            
                            <h6 class="mt-3">Foto Check-out:</h6>
                            <div id="detail-end-photo" class="border p-2 rounded text-center">
                                <img src="" alt="End Photo" class="img-fluid" style="max-height: 200px; display: none;">
                                <p class="text-muted mb-0">Tidak ada foto</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-3" id="location-section" style="display: none;">
                        <div class="col-md-12">
                            <h6>Lokasi Check-in:</h6>
                            <p id="detail-checkin-address" class="small mb-1">-</p>
                            <p id="detail-checkin-coords" class="small text-muted mb-2">-</p>
                            
                            <h6 class="mt-2">Lokasi Check-out:</h6>
                            <p id="detail-checkout-address" class="small mb-1">-</p>
                            <p id="detail-checkout-coords" class="small text-muted">-</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.25/css/dataTables.bootstrap4.min.css">
    <style>
        .btn-group .btn {
            margin-right: 2px;
        }
    </style>
@stop

@section('js')
    <script src="https://cdn.datatables.net/1.10.25/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.25/js/dataTables.bootstrap4.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            var table = $('#lemburTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route("admin.lembur.index") }}',
                columns: [
                    {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                    {data: 'karyawan', name: 'karyawan'},
                    {data: 'client', name: 'client'},
                    {data: 'tanggal', name: 'tanggal'},
                    {data: 'waktu', name: 'waktu'},
                    {data: 'type', name: 'type'},
                    {data: 'alasan', name: 'alasan'},
                    {data: 'status_badge', name: 'status'},
                    {data: 'action', name: 'action', orderable: false, searchable: false}
                ],
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.10.25/i18n/Indonesian.json'
                }
            });
        });

        // Detail Lembur
        function detailLembur(id) {
            $.ajax({
                url: '{{ url("admin/lembur") }}/' + id,
                type: 'GET',
                success: function(response) {
                    if (response.success) {
                        var data = response.data;
                        
                        $('#detail-karyawan').text(data.karyawan);
                        $('#detail-client').text(data.client);
                        $('#detail-type').text(data.type);
                        $('#detail-start').text(data.start);
                        $('#detail-end').text(data.end);
                        $('#detail-status').html('<span class="badge badge-' + 
                            (data.status === 'Approved' ? 'success' : (data.status === 'Rejected' ? 'danger' : 'warning')) + 
                            '">' + data.status + '</span>');
                        $('#detail-overtime-pay').text(data.overtime_pay);
                        $('#detail-alasan').text(data.alasan || '-');
                        
                        // Handle photos
                        if (data.start_photo) {
                            $('#detail-start-photo img').attr('src', data.start_photo).show();
                            $('#detail-start-photo p').hide();
                        } else {
                            $('#detail-start-photo img').hide();
                            $('#detail-start-photo p').show();
                        }
                        
                        if (data.end_photo) {
                            $('#detail-end-photo img').attr('src', data.end_photo).show();
                            $('#detail-end-photo p').hide();
                        } else {
                            $('#detail-end-photo img').hide();
                            $('#detail-end-photo p').show();
                        }
                        
                        // Handle locations
                        if (data.check_in_location || data.check_out_location) {
                            $('#location-section').show();
                            
                            if (data.check_in_location) {
                                $('#detail-checkin-address').text(data.check_in_location.address || '-');
                                $('#detail-checkin-coords').text('(' + data.check_in_location.latitude + ', ' + data.check_in_location.longitude + ')');
                            } else {
                                $('#detail-checkin-address').text('-');
                                $('#detail-checkin-coords').text('-');
                            }
                            
                            if (data.check_out_location) {
                                $('#detail-checkout-address').text(data.check_out_location.address || '-');
                                $('#detail-checkout-coords').text('(' + data.check_out_location.latitude + ', ' + data.check_out_location.longitude + ')');
                            } else {
                                $('#detail-checkout-address').text('-');
                                $('#detail-checkout-coords').text('-');
                            }
                        } else {
                            $('#location-section').hide();
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
            if (!confirm('Apakah Anda yakin ingin approve lembur ini?')) {
                return;
            }

            $.ajax({
                url: '{{ url("admin/lembur") }}/' + id + '/approve',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.message);
                        $('#lemburTable').DataTable().ajax.reload();
                    } else {
                        alert(response.message);
                    }
                },
                error: function(xhr) {
                    alert('Error: ' + (xhr.responseJSON?.message || 'Gagal approve lembur'));
                }
            });
        }

        // Reject Lembur
        function rejectLembur(id) {
            if (!confirm('Apakah Anda yakin ingin reject lembur ini?')) {
                return;
            }

            $.ajax({
                url: '{{ url("admin/lembur") }}/' + id + '/reject',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.message);
                        $('#lemburTable').DataTable().ajax.reload();
                    } else {
                        alert(response.message);
                    }
                },
                error: function(xhr) {
                    alert('Error: ' + (xhr.responseJSON?.message || 'Gagal reject lembur'));
                }
            });
        }
    </script>
@stop
