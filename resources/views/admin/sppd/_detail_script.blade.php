{{--
    Script modal Detail SPPD, dipakai e-SPPD approver dan Report SPPD Perekap Lembur.
    Variabel: $detailUrl = URL dasar endpoint detail (GET {detailUrl}/{id} mengembalikan JSON presenter).
    Tombol Approve/Reject hanya muncul bila JSON membawa can_act = true; halaman yang
    tidak mendefinisikan approveSppd()/rejectSppd() (Report SPPD) tidak pernah mendapat can_act = true.
--}}
<script>
/**
 * Load one SPPD into the detail modal and show it.
 *
 * @param {number} id SPPD id
 * @return {void}
 */
function detailSppd(id) {
    $.get('{{ $detailUrl }}/' + id, function (res) {
        if (!res.success) { alert(res.message ?? 'Gagal memuat data.'); return; }
        var d = res.data;

        $('#detail-kode-title, #detail-kode').text(d.kode);
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
                    '<td>' + escapeHtml(c.uraian ?? '-') + '</td>' +
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
                attHtml += '<tr><td>' + escapeHtml(att.jenis ?? '-') + '</td>' +
                    '<td>' + (att.url ? '<a href="' + att.url + '" target="_blank">' + escapeHtml(att.file_name ?? 'Lihat File') + '</a>' : escapeHtml(att.file_name ?? '-')) + '</td></tr>';
            });
        } else {
            attHtml = '<tr><td colspan="2" class="text-center">Tidak ada dokumen.</td></tr>';
        }
        $('#detail-attachments-body').html(attHtml);

        // Approval logs (a Pengembalian SPPD row has no step number or name)
        var logHtml = '';
        if (d.approval_logs && d.approval_logs.length) {
            d.approval_logs.forEach(function (log) {
                logHtml += '<tr>' +
                    '<td>' + (log.step_order ?? '-') + '</td>' +
                    '<td>' + escapeHtml(log.step_name ?? '-') + '</td>' +
                    '<td>' + statusBadge(log.status) + '</td>' +
                    '<td>' + escapeHtml(log.notes ?? '-') + '</td>' +
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

/**
 * Escape text before it goes into an HTML string.
 *
 * @param {*} value
 * @return {string}
 */
function escapeHtml(value) {
    return $('<div>').text(value).html();
}

/**
 * Status badge HTML for an SPPD or an approval log row.
 *
 * @param {string} status waiting_approval | approved | rejected | reset
 * @param {string|null} stepProgress "current/total" shown on waiting_approval
 * @return {string}
 */
function statusBadge(status, stepProgress) {
    var label = status;
    if (status === 'waiting_approval') label = 'Waiting Approval' + (stepProgress ? ' (' + stepProgress + ')' : '');
    else if (status === 'approved') label = 'Approved';
    else if (status === 'rejected') label = 'Rejected';
    else if (status === 'reset') label = 'Dikembalikan ke Waiting Approval';

    var cls = status === 'approved' ? 'success' : (status === 'rejected' ? 'danger' : (status === 'reset' ? 'warning' : 'secondary'));
    return '<span class="badge badge-' + cls + '">' + label + '</span>';
}
</script>
