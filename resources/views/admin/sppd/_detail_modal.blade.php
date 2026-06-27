<div class="modal fade" id="detailModal" tabindex="-1" role="dialog" aria-labelledby="detailModalLabel">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="detailModalLabel">
                    <i class="fas fa-plane"></i> Detail SPPD &mdash; <span id="detail-kode"></span>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">

                {{-- Rejection Alert --}}
                <div id="detail-rejection-box" class="alert alert-danger d-none">
                    <strong><i class="fas fa-times-circle"></i> Alasan Penolakan:</strong>
                    <p class="mb-0 mt-1"></p>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-sm table-bordered">
                            <tr><th style="width:40%">Kode SPPD</th><td id="detail-kode"></td></tr>
                            <tr><th>Nama Karyawan</th><td id="detail-karyawan"></td></tr>
                            <tr><th>Emp ID</th><td id="detail-empid"></td></tr>
                            <tr><th>Jabatan</th><td id="detail-jabatan"></td></tr>
                            <tr><th>Tgl Berangkat</th><td id="detail-tgl-berangkat"></td></tr>
                            <tr><th>Tgl Kembali</th><td id="detail-tgl-kembali"></td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-sm table-bordered">
                            <tr><th style="width:40%">Lokasi</th><td id="detail-lokasi"></td></tr>
                            <tr><th>Keterangan</th><td id="detail-keterangan"></td></tr>
                            <tr><th>Status</th><td id="detail-status"></td></tr>
                            <tr><th>Total Biaya</th><td id="detail-total-biaya"></td></tr>
                            <tr><th>Total Diterima Pegawai</th><td id="detail-total-diterima"></td></tr>
                        </table>
                    </div>
                </div>

                {{-- Rincian Biaya --}}
                <h6 class="mt-3"><strong>Rincian Biaya</strong></h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="thead-light">
                            <tr>
                                <th>Uraian</th>
                                <th class="text-right">Nominal</th>
                                <th class="text-center">Hari</th>
                                <th class="text-right">Subtotal</th>
                                <th class="text-center">Diterima Pegawai</th>
                            </tr>
                        </thead>
                        <tbody id="detail-costs-body">
                            <tr><td colspan="5" class="text-center">Memuat...</td></tr>
                        </tbody>
                    </table>
                </div>

                {{-- Dokumen Pendukung --}}
                <h6 class="mt-3"><strong>Dokumen Pendukung</strong></h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="thead-light">
                            <tr><th>Jenis</th><th>File</th></tr>
                        </thead>
                        <tbody id="detail-attachments-body">
                            <tr><td colspan="2" class="text-center">Memuat...</td></tr>
                        </tbody>
                    </table>
                </div>

                {{-- Log Approval --}}
                <h6 class="mt-3"><strong>Log Approval</strong></h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="thead-light">
                            <tr>
                                <th>Step</th>
                                <th>Nama Step</th>
                                <th>Status</th>
                                <th>Catatan</th>
                                <th>Waktu</th>
                            </tr>
                        </thead>
                        <tbody id="detail-logs-body">
                            <tr><td colspan="5" class="text-center">Memuat...</td></tr>
                        </tbody>
                    </table>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" id="detail-reject-btn" class="btn btn-danger d-none">
                    <i class="fas fa-times"></i> Tolak
                </button>
                <button type="button" id="detail-approve-btn" class="btn btn-success d-none">
                    <i class="fas fa-check"></i> Approve
                </button>
                <button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
