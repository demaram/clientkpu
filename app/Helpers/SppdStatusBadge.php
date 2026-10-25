<?php

namespace App\Helpers;

use App\Models\Sppd;

/**
 * HTML badge for the status of an SPPD, shared by the e-SPPD list and the Report SPPD.
 */
class SppdStatusBadge
{
    /**
     * Badge HTML. A waiting SPPD with an approval config also shows its step progress, e.g. "(2/3)".
     *
     * Needs `approvalConfig.steps` loaded on the SPPD to show the progress without extra queries.
     *
     * @param  Sppd  $row
     * @return string
     */
    public static function render(Sppd $row): string
    {
        if ($row->status === 'waiting_approval' && $row->approval_config_id && $row->approvalConfig) {
            $total = $row->approvalConfig->steps->count();

            return '<span class="badge badge-secondary">Waiting Approval (' . $row->current_approval_step . '/' . $total . ')</span>';
        }

        $badges = [
            'waiting_approval' => '<span class="badge badge-secondary">Waiting Approval</span>',
            'approved'         => '<span class="badge badge-success">Approved</span>',
            'rejected'         => '<span class="badge badge-danger">Rejected</span>',
        ];

        return $badges[$row->status] ?? '<span class="badge badge-secondary">' . e(ucfirst($row->status)) . '</span>';
    }
}
