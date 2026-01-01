<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LemburKaryawan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class LemburController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            // Get user's client_id
            $user = Auth::user();
            $clientId = $user->id_client ?? null;

            // Query lembur karyawan with client_id filter
            $data = LemburKaryawan::with(['user', 'client'])
                ->when($clientId, function ($query) use ($clientId) {
                    return $query->where('client_id', $clientId);
                })
                ->orderBy('created_at', 'desc')
                ->get();

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('karyawan', function ($row) {
                    $user = $row->user;
                    return $user ? $user->first_name . ' ' . $user->last_name : '-';
                })
                ->addColumn('client', function ($row) {
                    return $row->client ? $row->client->description : '-';
                })
                ->addColumn('tanggal', function ($row) {
                    return date('d/m/Y', strtotime($row->start));
                })
                ->addColumn('waktu', function ($row) {
                    $start = date('H:i', strtotime($row->start));
                    $end = $row->end ? date('H:i', strtotime($row->end)) : '-';
                    return $start . ' - ' . $end;
                })
                ->addColumn('status_badge', function ($row) {
                    $badges = [
                        'pending' => '<span class="badge badge-warning">Pending</span>',
                        'approved' => '<span class="badge badge-success">Approved</span>',
                        'rejected' => '<span class="badge badge-danger">Rejected</span>',
                    ];
                    return $badges[$row->status] ?? '<span class="badge badge-secondary">' . ucfirst($row->status) . '</span>';
                })
                ->addColumn('action', function ($row) {
                    $buttons = '<div class="btn-group" role="group">';
                    
                    // Detail button
                    $buttons .= '<button type="button" class="btn btn-sm btn-info" onclick="detailLembur(' . $row->id . ')" title="Detail">
                        <i class="fas fa-eye"></i>
                    </button>';
                    
                    // Only show approve/reject if status is pending
                    if ($row->status === 'pending') {
                        $buttons .= '<button type="button" class="btn btn-sm btn-success" onclick="approveLembur(' . $row->id . ')" title="Approve">
                            <i class="fas fa-check"></i>
                        </button>';
                        $buttons .= '<button type="button" class="btn btn-sm btn-danger" onclick="rejectLembur(' . $row->id . ')" title="Reject">
                            <i class="fas fa-times"></i>
                        </button>';
                    }
                    
                    $buttons .= '</div>';
                    return $buttons;
                })
                ->rawColumns(['status_badge', 'action'])
                ->make(true);
        }

        return view('admin.lembur.index');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $lembur = LemburKaryawan::with(['user', 'client', 'checkInLocation', 'checkOutLocation'])
            ->findOrFail($id);

        // Check if user has access to this lembur data
        $user = Auth::user();
        if ($user->id_client && $lembur->client_id != $user->id_client) {
            abort(403, 'Unauthorized access');
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $lembur->id,
                'karyawan' => $lembur->user ? $lembur->user->first_name . ' ' . $lembur->user->last_name : '-',
                'client' => $lembur->client ? $lembur->client->description : '-',
                'type' => ucfirst($lembur->type),
                'alasan' => $lembur->alasan,
                'start' => date('d/m/Y H:i', strtotime($lembur->start)),
                'end' => $lembur->end ? date('d/m/Y H:i', strtotime($lembur->end)) : '-',
                'status' => ucfirst($lembur->status),
                'overtime_pay' => $lembur->overtime_pay ? 'Rp ' . number_format($lembur->overtime_pay, 0, ',', '.') : '-',
                'start_photo' => $lembur->start_photo,
                'end_photo' => $lembur->end_photo,
                'check_in_location' => $lembur->checkInLocation ? [
                    'latitude' => $lembur->checkInLocation->latitude,
                    'longitude' => $lembur->checkInLocation->longitude,
                    'address' => $lembur->checkInLocation->address,
                ] : null,
                'check_out_location' => $lembur->checkOutLocation ? [
                    'latitude' => $lembur->checkOutLocation->latitude,
                    'longitude' => $lembur->checkOutLocation->longitude,
                    'address' => $lembur->checkOutLocation->address,
                ] : null,
            ]
        ]);
    }

    /**
     * Approve lembur request
     */
    public function approve($id)
    {
        $lembur = LemburKaryawan::findOrFail($id);

        // Check if user has access to this lembur data
        $user = Auth::user();
        if ($user->id_client && $lembur->client_id != $user->id_client) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        if ($lembur->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Lembur sudah diproses sebelumnya'
            ]);
        }

        $lembur->status = 'approved';
        $lembur->save();

        return response()->json([
            'success' => true,
            'message' => 'Lembur berhasil di-approve'
        ]);
    }

    /**
     * Reject lembur request
     */
    public function reject($id)
    {
        $lembur = LemburKaryawan::findOrFail($id);

        // Check if user has access to this lembur data
        $user = Auth::user();
        if ($user->id_client && $lembur->client_id != $user->id_client) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        if ($lembur->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Lembur sudah diproses sebelumnya'
            ]);
        }

        $lembur->status = 'rejected';
        $lembur->save();

        return response()->json([
            'success' => true,
            'message' => 'Lembur berhasil di-reject'
        ]);
    }
}
