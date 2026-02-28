<?php

namespace App\Datatables;

use App\Models\LemburKaryawan;
use App\Models\Project;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class LemburDatatable
{
	public function render(Request $request)
	{
		$user = User::with('areas')->find(Auth::id());

		if (!$user) {
			abort(401);
		}

		$areaIds = $user->areas->pluck('id')->toArray();
		$clientId = $user->id_client ?? null;
		$projectIds = Project::whereIn('master_area_id', $areaIds)->pluck('id')->toArray();

		$namaKaryawan = trim((string) $request->get('nama_karyawan', ''));
		$statusFilter = (string) $request->get('status', 'all_status');
		$startDate = $request->get('start_date');
		$endDate = $request->get('end_date');

		$statusMap = [
			'approve' => 'approved',
			'reject' => 'rejected',
		];

		if (isset($statusMap[$statusFilter])) {
			$statusFilter = $statusMap[$statusFilter];
		}

		$startDateTime = null;
		$endDateTime = null;

		try {
			if (!empty($startDate)) {
				$startDateTime = Carbon::createFromFormat('Y-m-d H:i', $startDate)->startOfMinute();
			}

			if (!empty($endDate)) {
				$endDateTime = Carbon::createFromFormat('Y-m-d H:i', $endDate)->endOfMinute();
			}
		} catch (\Throwable $e) {
			$startDateTime = null;
			$endDateTime = null;
		}

		$query = LemburKaryawan::query()
			->with(['user', 'client'])
			->when($clientId, function ($query) use ($clientId) {
				return $query->where('client_id', $clientId);
			})
			->whereIn('project_id', $projectIds)
			->where('type', 'lembur')
			->when($namaKaryawan !== '', function ($query) use ($namaKaryawan) {
				$query->whereHas('user', function ($userQuery) use ($namaKaryawan) {
					$userQuery->whereRaw("CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,'')) LIKE ?", ['%' . $namaKaryawan . '%'])
						->orWhere('first_name', 'like', '%' . $namaKaryawan . '%')
						->orWhere('last_name', 'like', '%' . $namaKaryawan . '%');
				});
			})
			->when($statusFilter !== 'all_status' && $statusFilter !== '', function ($query) use ($statusFilter) {
				$query->where('status', $statusFilter);
			})
			->when($startDateTime, function ($query) use ($startDateTime) {
				$query->where('start', '>=', $startDateTime);
			})
			->when($endDateTime, function ($query) use ($endDateTime) {
				$query->where('start', '<=', $endDateTime);
			});

		$totalDurasiMenit = (clone $query)
			->whereNotNull('end')
			->selectRaw('COALESCE(SUM(TIMESTAMPDIFF(MINUTE, start, `end`)), 0) as total_minutes')
			->value('total_minutes');

		return DataTables::eloquent($query)
			->addIndexColumn()
			->addColumn('karyawan', function ($row) {
				$user = $row->user;
				return $user ? $user->first_name . ' ' . $user->last_name : '-';
			})
			->addColumn('empid', function ($row) {
				$user = $row->user;
				return $user ? $user->empid : '-';
			})
			->addColumn('kode', function ($row) {
				return '<b>' . $row->kode . '</b>' ?? '-';
			})
			->addColumn('overtime_pay', function ($row) {
				return $row->overtime_pay ? 'Rp ' . number_format($row->overtime_pay, 0, ',', '.') : '-';
			})
			->addColumn('tanggal', function ($row) {
				return date('d/m/Y', strtotime($row->start));
			})
			->addColumn('waktu', function ($row) {
				$start = date('H:i', strtotime($row->start));
				$end = $row->end ? date('H:i', strtotime($row->end)) : '-';
				return $start . ' - ' . $end;
			})
			->addColumn('durasi', function ($row) {
				if (!$row->start || !$row->end) {
					return '-';
				}

				$startTime = strtotime($row->start);
				$endTime = strtotime($row->end);
				$durasiDetik = $endTime - $startTime;

				if ($durasiDetik <= 0) {
					return '-';
				}

				$hours = floor($durasiDetik / 3600);
				$minutes = floor(($durasiDetik % 3600) / 60);

				return $hours . ' jam ' . $minutes . ' menit';
			})
			->addColumn('counted_hours', function ($row) {
				if (!$row->counted_hours) {
					return '-';
				}

				$totalMinutes = (int) round($row->counted_hours * 60);
				$hours = intdiv($totalMinutes, 60);
				$minutes = $totalMinutes % 60;

				return $hours . ' jam ' . $minutes . ' menit';
			})
			->addColumn('status_badge', function ($row) {
				$badges = [
					'waiting_approval' => '<span class="badge badge-secondary">Waiting Approval</span>',
					'pending' => '<span class="badge badge-warning">On Process</span>',
					'approved' => '<span class="badge badge-success">Approved</span>',
					'rejected' => '<span class="badge badge-danger">Rejected</span>',
				];

				return $badges[$row->status] ?? '<span class="badge badge-secondary">' . ucfirst($row->status) . '</span>';
			})
			->addColumn('action', function ($row) {
				$buttons = '<div class="btn-group" role="group">';

				$buttons .= '<button type="button" class="btn btn-sm btn-info" onclick="detailLembur(' . $row->id . ')" title="Detail">
					<i class="fas fa-eye"></i>
				</button>';

				if ($row->status === 'waiting_approval') {
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
			->order(function ($query) {
				$orders = request()->input('order', []);
				$columns = request()->input('columns', []);

				if (empty($orders)) {
					$query->orderBy('created_at', 'desc');
					return;
				}

				foreach ($orders as $item) {
					$columnIndex = (int) ($item['column'] ?? -1);
					$direction = strtolower((string) ($item['dir'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';

					$columnData = $columns[$columnIndex]['data'] ?? '';
					$columnName = $columns[$columnIndex]['name'] ?? '';
					$key = $columnName !== '' ? $columnName : $columnData;

					switch ($key) {
						case 'karyawan':
							$query->orderByRaw("(SELECT CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,'')) FROM users WHERE users.id = lembur_karyawan_project.user_id LIMIT 1) {$direction}");
							break;
						case 'empid':
							$query->orderByRaw("(SELECT empid FROM users WHERE users.id = lembur_karyawan_project.user_id LIMIT 1) {$direction}");
							break;
						case 'kode':
							$query->orderBy('kode', $direction);
							break;
						case 'tanggal':
							$query->orderBy('start', $direction);
							break;
						case 'waktu':
							$query->orderBy('start', $direction);
							break;
						case 'durasi':
							$query->orderByRaw("TIMESTAMPDIFF(SECOND, start, `end`) {$direction}");
							break;
						case 'counted_hours':
							$query->orderBy('counted_hours', $direction);
							break;
						case 'alasan':
							$query->orderBy('alasan', $direction);
							break;
						case 'status':
						case 'status_badge':
							$query->orderBy('status', $direction);
							break;
						case 'overtime_pay':
							$query->orderBy('overtime_pay', $direction);
							break;
						default:
                            $query->orderBy('start','desc');
							break;
					}
				}
			})
			->rawColumns(['status_badge', 'action', 'kode'])
			->with([
				'total_durasi_menit' => (int) $totalDurasiMenit,
				'total_durasi_label' => $this->formatDurationFromMinutes((int) $totalDurasiMenit),
			])
			->make(true);
	}

	private function formatDurationFromMinutes(int $minutes): string
	{
		if ($minutes <= 0) {
			return '0 jam 0 menit';
		}

		$hours = intdiv($minutes, 60);
		$remainingMinutes = $minutes % 60;

		return $hours . ' jam ' . $remainingMinutes . ' menit';
	}
}
