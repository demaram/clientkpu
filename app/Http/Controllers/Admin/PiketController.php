<?php

namespace App\Http\Controllers\Admin;

use App\Datatables\PiketDatatable;
use App\Http\Controllers\Controller;
use App\Models\LemburKaryawan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PiketController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, PiketDatatable $datatable)
    {
        if ($request->ajax()) {
            return $datatable->render($request);
        }

        return view('admin.piket.index');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $piket = LemburKaryawan::with(['user', 'client', 'checkInLocation', 'checkOutLocation'])
            ->findOrFail($id);

        // Check if user has access to this Piket data
        $user = Auth::user()->load('areas');
        if ($user->id_client && $piket->client_id != $user->id_client) {
            abort(403, 'Unauthorized access');
        }

        // Generate photo URLs using custom_public disk
        $startPhotoUrl = null;
        if ($piket->start_photo) {
            if (Storage::disk('custom_public')->exists($piket->start_photo)) {
                $startPhotoUrl = Storage::disk('custom_public')->url($piket->start_photo);
            }
        }

        $endPhotoUrl = null;
        if ($piket->end_photo) {
            if (Storage::disk('custom_public')->exists($piket->end_photo)) {
                $endPhotoUrl = Storage::disk('custom_public')->url($piket->end_photo);
            }
        }

        // Calculate duration
        $durasi = '-';
        if ($piket->start && $piket->end) {
            $startTime = strtotime($piket->start);
            $endTime = strtotime($piket->end);
            $durasiDetik = $endTime - $startTime;
            
            $hours = floor($durasiDetik / 3600);
            $minutes = floor(($durasiDetik % 3600) / 60);
            
            $durasi = $hours . ' jam ' . $minutes . ' menit';
        }

        // Get employee details
        $karyawan = $piket->user;
        $empId = $karyawan->emp_id ?? '-';
        $jabatan = $karyawan->jabatan ?? '-';
        $nomorRekening = $karyawan->no_rekening ?? '-';

        return response()->json([
            'success' => true,
            'data' => [
                'id'                => $piket->id,
                'client'            => $piket->client->nama ?? '-',
                'karyawan'          => $karyawan ? $karyawan->first_name . ' ' . $karyawan->last_name : '-',
                'empid'             => $empId,
                'jabatan'           => $jabatan,
                'rekening'          => $nomorRekening,
                'type'              => ucfirst($piket->type),
                'tanggal'           => date('d/m/Y', strtotime($piket->start)),
                'start_time'        => date('H:i', strtotime($piket->start)),
                'end_time'          => $piket->end ? date('H:i', strtotime($piket->end)) : '-',
                'durasi'            => $durasi,
                'overtime_pay'      => $piket->overtime_pay ? 'Rp ' . number_format($piket->overtime_pay, 0, ',', '.') : '-',
                'status'            => ucfirst($piket->status),
                'alasan'            => $piket->alasan ?? '-',
                'start_photo'       => $startPhotoUrl,
                'end_photo'         => $endPhotoUrl,
                'check_in_location' => $piket->checkInLocation ? [
                    'latitude'  => $piket->checkInLocation->latitude,
                    'longitude' => $piket->checkInLocation->longitude,
                    'address'   => $piket->checkInLocation->address,
                ] : null,
                'check_out_location' => $piket->checkOutLocation ? [
                    'latitude'  => $piket->checkOutLocation->latitude,
                    'longitude' => $piket->checkOutLocation->longitude,
                    'address'   => $piket->checkOutLocation->address,
                ] : null,
            ]
        ]);
    }

    /**
     * Approve Piket request
     */
    public function approve($id)
    {
        $piket = LemburKaryawan::findOrFail($id);

        // Check if user has access to this Piket data
        $user = Auth::user();
        if ($user->id_client && $piket->client_id != $user->id_client) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        if ($piket->status !== 'waiting_approval') {
            return response()->json([
                'success' => false,
                'message' => 'Piket sudah diproses sebelumnya'
            ]);
        }

        $piket->status = 'approved';
        $piket->save();

        return response()->json([
            'success' => true,
            'message' => 'Piket berhasil di-approve'
        ]);
    }

    /**
     * Reject Piket request
     */
    public function reject($id)
    {
        $piket = LemburKaryawan::findOrFail($id);

        // Check if user has access to this Piket data
        $user = Auth::user();
        if ($user->id_client && $piket->client_id != $user->id_client) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        if ($piket->status !== 'waiting_approval') {
            return response()->json([
                'success' => false,
                'message' => 'Piket sudah diproses sebelumnya'
            ]);
        }

        $piket->status = 'rejected';
        $piket->save();

        return response()->json([
            'success' => true,
            'message' => 'Piket berhasil di-reject'
        ]);
    }
}
