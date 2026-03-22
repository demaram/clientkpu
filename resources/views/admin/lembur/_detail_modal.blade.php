<!-- Detail Modal -->
<div class="modal fade" id="detailModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h5 class="modal-title">Detail Lembur Karyawan</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <!-- Informasi Karyawan & Lembur -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="fas fa-user"></i> Informasi Karyawan</h6>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm table-borderless">
                                    <tr>
                                        <th width="45%">Client</th>
                                        <td id="detail-client">-</td>
                                    </tr>
                                    <tr>
                                        <th>Nama Karyawan</th>
                                        <td id="detail-karyawan">-</td>
                                    </tr>
                                    <tr>
                                        <th>EmpId</th>
                                        <td id="detail-empid">-</td>
                                    </tr>
                                    <tr>
                                        <th>Jabatan</th>
                                        <td id="detail-jabatan">-</td>
                                    </tr>
                                    <tr>
                                        <th>Nomor Rekening</th>
                                        <td id="detail-rekening">-</td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <div class="card mt-3">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="fas fa-clock"></i> Informasi Lembur</h6>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm table-borderless">
                                    <tr>
                                        <th width="45%">Tipe</th>
                                        <td id="detail-type">-</td>
                                    </tr>
                                    <tr>
                                        <th>Tanggal Lembur</th>
                                        <td id="detail-tanggal">-</td>
                                    </tr>
                                    <tr>
                                        <th>Durasi</th>
                                        <td id="detail-durasi">-</td>
                                    </tr>
                                    <tr>
                                        <th>Overtime Pay</th>
                                        <td id="detail-overtime-pay" class="font-weight-bold text-success">-</td>
                                    </tr>
                                    <tr>
                                        <th>Status</th>
                                        <td id="detail-status">-</td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <div class="card mt-3">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="fas fa-file-alt"></i> Alasan / Keterangan</h6>
                            </div>
                            <div class="card-body">
                                <p id="detail-alasan" class="mb-0">-</p>
                            </div>
                        </div>

                        <div class="card mt-3 border-primary">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0"><i class="fas fa-chart-bar"></i> Rekapitulasi Lembur (Approved)</h6>
                            </div>
                            <div class="card-body p-0">
                                <table class="table table-sm mb-0">
                                    <tr class="bg-light">
                                        <th class="pl-3" width="55%"><i class="fas fa-calendar-alt text-muted mr-1"></i> Jam / Bulan</th>
                                        <td class="font-weight-bold text-primary" id="detail-monthly-hours">-</td>
                                    </tr>
                                    <tr>
                                        <td colspan="2" class="py-0 pl-3">
                                            <small class="text-muted" id="detail-monthly-period">-</small>
                                        </td>
                                    </tr>
                                    <tr class="bg-light">
                                        <th class="pl-3"><i class="fas fa-calendar-week text-muted mr-1"></i> Jam / Minggu</th>
                                        <td class="font-weight-bold text-primary" id="detail-weekly-hours">-</td>
                                    </tr>
                                    <tr>
                                        <td colspan="2" class="py-0 pl-3">
                                            <small class="text-muted" id="detail-weekly-period">-</small>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Check-in & Check-out Details -->
                    <div class="col-md-6">
                        <!-- Check-in Section -->
                        <div class="card border-success">
                            <div class="card-header bg-success text-white">
                                <h6 class="mb-0"><i class="fas fa-sign-in-alt"></i> Check-in</h6>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm table-borderless mb-3">
                                    <tr>
                                        <th width="40%">Jam Mulai</th>
                                        <td id="detail-start">-</td>
                                    </tr>
                                </table>
                                
                                <h6 class="text-muted small mb-2">Foto Mulai:</h6>
                                <div id="detail-start-photo" class="border rounded text-center mb-3" style="min-height: 150px; background-color: #f8f9fa;">
                                    <img src="" alt="Start Photo" class="img-fluid rounded" style="max-height: 250px; display: none;">
                                    <p class="text-muted mb-0 py-5">Tidak ada foto</p>
                                </div>
                                
                                <h6 class="text-muted small mb-2">Koordinat Mulai:</h6>
                                <div class="border rounded p-2" style="background-color: #f8f9fa;">
                                    <p id="detail-checkin-address" class="small mb-1">-</p>
                                    <p id="detail-checkin-coords" class="small text-muted mb-0"><i class="fas fa-map-marker-alt"></i> -</p>
                                </div>
                            </div>
                        </div>

                        <!-- Check-out Section -->
                        <div class="card border-danger mt-3">
                            <div class="card-header bg-danger text-white">
                                <h6 class="mb-0"><i class="fas fa-sign-out-alt"></i> Check-out</h6>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm table-borderless mb-3">
                                    <tr>
                                        <th width="40%">Jam Selesai</th>
                                        <td id="detail-end">-</td>
                                    </tr>
                                </table>
                                
                                <h6 class="text-muted small mb-2">Foto Selesai:</h6>
                                <div id="detail-end-photo" class="border rounded text-center mb-3" style="min-height: 150px; background-color: #f8f9fa;">
                                    <img src="" alt="End Photo" class="img-fluid rounded" style="max-height: 250px; display: none;">
                                    <p class="text-muted mb-0 py-5">Tidak ada foto</p>
                                </div>
                                
                                <h6 class="text-muted small mb-2">Koordinat Selesai:</h6>
                                <div class="border rounded p-2" style="background-color: #f8f9fa;">
                                    <p id="detail-checkout-address" class="small mb-1">-</p>
                                    <p id="detail-checkout-coords" class="small text-muted mb-0"><i class="fas fa-map-marker-alt"></i> -</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" onclick="approveFromModal()">
                    <i class="fas fa-check"></i> Approve
                </button>
                <button type="button" class="btn btn-danger" onclick="rejectFromModal()">
                    <i class="fas fa-times-circle"></i> Reject
                </button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Tutup
                </button>
            </div>
        </div>
    </div>
</div>
