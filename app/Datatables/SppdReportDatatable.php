<?php

namespace App\Datatables;

use App\Helpers\SppdStatusBadge;
use App\Models\Sppd;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

/**
 * DataTables response of the Report SPPD: the SPPD of karyawan covered by the logged-in
 * Perekap Lembur, with Total Biaya, Total Diterima Pegawai and Total Diterima Perusahaan per row
 * and as a footer total over every row matching the filters (not only the current page).
 */
class SppdReportDatatable
{
    /**
     * Build the DataTables JSON for the current Perekap Lembur and the request's filters.
     *
     * @param  Request  $request  Optional: nama_filter, status_filter, start_date, end_date
     * @return \Illuminate\Http\JsonResponse
     */
    public function render(Request $request)
    {
        $filtered = $this->filtered($request);

        $query = (clone $filtered)
            ->with(['karyawan', 'approvalConfig.steps'])
            ->withSum('costs as biaya_sum', 'subtotal')
            ->withSum(['costs as pegawai_sum' => fn ($q) => $q->where('diterima_pegawai', true)], 'subtotal');

        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->addColumn('karyawan', function (Sppd $row) {
                $u = $row->karyawan;
                return $u ? trim($u->first_name . ' ' . $u->last_name) : '-';
            })
            ->addColumn('empid', fn (Sppd $row) => $row->karyawan?->empid ?? '-')
            ->addColumn('kode', fn (Sppd $row) => '<b>' . e($row->kode) . '</b>')
            ->addColumn('tanggal_berangkat', fn (Sppd $row) => $row->tanggal_berangkat ? date('d/m/Y', strtotime($row->tanggal_berangkat)) : '-')
            ->addColumn('tanggal_kembali', fn (Sppd $row) => $row->tanggal_kembali ? date('d/m/Y', strtotime($row->tanggal_kembali)) : '-')
            ->addColumn('durasi', fn (Sppd $row) => $row->durasi !== null ? $row->durasi . ' hari' : '-')
            ->addColumn('lokasi', fn (Sppd $row) => e($row->lokasi_penugasan ?: '-'))
            ->addColumn('status_badge', fn (Sppd $row) => SppdStatusBadge::render($row))
            ->addColumn('total_biaya', fn (Sppd $row) => $this->rupiah((float) $row->biaya_sum))
            ->addColumn('total_diterima_pegawai', fn (Sppd $row) => $this->rupiah((float) $row->pegawai_sum))
            ->addColumn('total_diterima_perusahaan', fn (Sppd $row) => $this->rupiah((float) $row->biaya_sum - (float) $row->pegawai_sum))
            ->addColumn('action', fn (Sppd $row) => '<button type="button" class="btn btn-sm btn-info" onclick="detailSppd(' . $row->id . ')" title="Detail"><i class="fas fa-eye"></i></button>')
            ->order(function ($q) {
                $q->orderBy('tanggal_berangkat', 'desc')->orderBy('id', 'desc');
            })
            ->with('totals', $this->totals($filtered))
            ->rawColumns(['kode', 'status_badge', 'action'])
            ->make(true);
    }

    /**
     * The SPPD covered by the logged-in Perekap Lembur, narrowed by the request's filters.
     *
     * @param  Request  $request
     * @return Builder<Sppd>
     */
    private function filtered(Request $request): Builder
    {
        $nama   = trim((string) $request->get('nama_filter', ''));
        $status = (string) $request->get('status_filter', '');

        return Sppd::query()
            ->coveredByRecapUser((int) Auth::id())
            ->when($nama !== '', function ($q) use ($nama) {
                $q->whereHas('karyawan', function ($uq) use ($nama) {
                    $uq->whereRaw("CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,'')) LIKE ?", ['%' . $nama . '%']);
                });
            })
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($request->get('start_date'), fn ($q, $date) => $q->whereDate('tanggal_berangkat', '>=', $date))
            ->when($request->get('end_date'), fn ($q, $date) => $q->whereDate('tanggal_berangkat', '<=', $date));
    }

    /**
     * Footer totals over every SPPD matching the filters, in one query.
     *
     * @param  Builder<Sppd>  $filtered
     * @return array{total_biaya: string, total_diterima_pegawai: string, total_diterima_perusahaan: string}
     */
    private function totals(Builder $filtered): array
    {
        $sum = DB::table('sppd_costs')
            ->whereNull('deleted_at')
            ->whereIn('sppd_id', (clone $filtered)->select('sppd.id'))
            ->selectRaw('COALESCE(SUM(subtotal), 0) AS biaya, COALESCE(SUM(CASE WHEN diterima_pegawai = 1 THEN subtotal ELSE 0 END), 0) AS pegawai')
            ->first();

        return [
            'total_biaya'               => $this->rupiah((float) $sum->biaya),
            'total_diterima_pegawai'    => $this->rupiah((float) $sum->pegawai),
            'total_diterima_perusahaan' => $this->rupiah((float) $sum->biaya - (float) $sum->pegawai),
        ];
    }

    /**
     * Format an amount as "Rp 1.250.000".
     *
     * @param  float  $amount
     * @return string
     */
    private function rupiah(float $amount): string
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }
}
