<?php

namespace App\Datatables;

use App\Helpers\SppdStatusBadge;
use App\Models\Sppd;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class SppdDatatable
{
    /**
     * Build and return a DataTables response for the SPPD listing, scoped to the
     * current client user's client_id.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function render(Request $request)
    {
        $user      = User::find(Auth::id());
        $clientIds = $user?->accessibleClientIds() ?? [];

        $namaFilter   = trim((string) $request->get('nama_filter', ''));
        $statusFilter = (string) $request->get('status_filter', '');
        $startDate    = $request->get('start_date');
        $endDate      = $request->get('end_date');

        $query = Sppd::query()
            ->with(['karyawan', 'client', 'approvalConfig.steps'])
            ->when($clientIds, fn ($q) => $q->whereIn('client_id', $clientIds))
            ->when($namaFilter !== '', function ($q) use ($namaFilter) {
                $q->whereHas('karyawan', function ($uq) use ($namaFilter) {
                    $uq->whereRaw("CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,'')) LIKE ?", ['%' . $namaFilter . '%']);
                });
            })
            ->when($statusFilter !== '', fn ($q) => $q->where('status', $statusFilter))
            ->when($startDate, fn ($q) => $q->whereDate('tanggal_berangkat', '>=', $startDate))
            ->when($endDate,   fn ($q) => $q->whereDate('tanggal_berangkat', '<=', $endDate));

        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->addColumn('karyawan', function (Sppd $row) {
                $u = $row->karyawan;
                return $u ? trim($u->first_name . ' ' . $u->last_name) : '-';
            })
            ->addColumn('empid', function (Sppd $row) {
                return $row->karyawan?->empid ?? '-';
            })
            ->addColumn('client_nama', function (Sppd $row) {
                return $row->client->nama ?? '-';
            })
            ->addColumn('kode', function (Sppd $row) {
                return '<b>' . e($row->kode) . '</b>';
            })
            ->addColumn('tanggal_berangkat', function (Sppd $row) {
                return $row->tanggal_berangkat ? date('d/m/Y', strtotime($row->tanggal_berangkat)) : '-';
            })
            ->addColumn('tanggal_kembali', function (Sppd $row) {
                return $row->tanggal_kembali ? date('d/m/Y', strtotime($row->tanggal_kembali)) : '-';
            })
            ->addColumn('durasi', function (Sppd $row) {
                return $row->durasi !== null ? $row->durasi . ' hari' : '-';
            })
            ->addColumn('status_badge', fn (Sppd $row) => SppdStatusBadge::render($row))
            ->addColumn('action', function (Sppd $row) {
                $buttons = '<button type="button" class="btn btn-sm btn-info" onclick="detailSppd(' . $row->id . ')" title="Detail"><i class="fas fa-eye"></i></button> ';

                if ($row->status === 'waiting_approval') {
                    $canAct = false;
                    if ($row->approval_config_id && $row->approvalConfig) {
                        $step = $row->approvalConfig->steps
                            ->where('step_order', $row->current_approval_step)
                            ->first();
                        $canAct = $step && $step->actor_type === 'client';
                    }

                    if ($canAct) {
                        $buttons .= '<button type="button" class="btn btn-sm btn-success" onclick="approveSppd(' . $row->id . ')" title="Approve"><i class="fas fa-check"></i></button> ';
                        $buttons .= '<button type="button" class="btn btn-sm btn-danger" onclick="rejectSppd(' . $row->id . ')" title="Reject"><i class="fas fa-times"></i></button>';
                    }
                }

                return $buttons;
            })
            ->order(function ($q) {
                $q->orderBy('created_at', 'desc');
            })
            ->rawColumns(['kode', 'status_badge', 'action'])
            ->make(true);
    }
}
