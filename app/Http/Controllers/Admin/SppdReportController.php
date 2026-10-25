<?php

namespace App\Http\Controllers\Admin;

use App\Datatables\SppdReportDatatable;
use App\Http\Controllers\Controller;
use App\Models\LemburApprovalConfig;
use App\Models\Sppd;
use App\Services\SppdDetailPresenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Report SPPD for the Perekap Lembur: a read-only list of the SPPD of karyawan covered by
 * their Konfigurasi Lembur. No approve/reject here; that stays with the approvers on e-SPPD.
 */
class SppdReportController extends Controller
{
    protected SppdDetailPresenter $detailPresenter;

    /**
     * @param  SppdDetailPresenter  $detailPresenter
     */
    public function __construct(SppdDetailPresenter $detailPresenter)
    {
        $this->detailPresenter = $detailPresenter;
    }

    /**
     * Show the report page, or the DataTables JSON for the AJAX call.
     *
     * @param  Request  $request
     * @param  SppdReportDatatable  $datatable
     * @return \Illuminate\Http\JsonResponse|\Illuminate\View\View
     */
    public function index(Request $request, SppdReportDatatable $datatable)
    {
        $this->ensureRecapAccess();

        if ($request->ajax()) {
            return $datatable->render($request);
        }

        return view('admin.report-sppd.index');
    }

    /**
     * Detail of one SPPD for the modal. Only SPPD covered by this Perekap Lembur are found, so ids of
     * other karyawan cannot be enumerated; the payload never offers approve/reject.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(int $id)
    {
        $this->ensureRecapAccess();

        $sppd = Sppd::query()
            ->coveredByRecapUser((int) Auth::id())
            ->with(SppdDetailPresenter::relations())
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $this->detailPresenter->present($sppd, false),
        ]);
    }

    /**
     * Abort with 403 unless the logged-in user is a Perekap Lembur on at least one Konfigurasi Lembur.
     *
     * @return void
     */
    private function ensureRecapAccess(): void
    {
        if (!LemburApprovalConfig::where('recap_user_id', Auth::id())->exists()) {
            abort(403, 'Anda tidak memiliki akses Report SPPD');
        }
    }
}
