<?php

namespace App\Services;

use App\Models\LemburApprovalConfigAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Shared "can this client user see this lembur/piket row" rule, used by
 * LemburDatatable, PiketDatatable, and the dashboard status cards so all
 * three surfaces stay in lockstep with the same visibility decision instead
 * of drifting apart across separately maintained copies.
 */
class LemburApprovalVisibilityService
{
    /**
     * Restrict the query to rows the given user is allowed to see.
     *
     * `approval_config_id` is only stamped onto a row at checkout time (see
     * `LemburService::resolveApprovalConfig()` in portalkpu) — a row still
     * `status = 'pending'` (checked in, not yet checked out) always has
     * `approval_config_id = NULL`, even when the karyawan already has an active
     * Assignment. Relying on `approval_config_id` alone therefore hid every
     * not-yet-checked-out row from every client user, not just genuinely
     * Unassigned karyawan (no active Assignment at all).
     *
     * A row is visible when either:
     *  - it already has a resolved `approval_config_id`, and the user is one of
     *    the approver steps in that config (any step, not just the current one); or
     *  - `approval_config_id` is still NULL, but the karyawan currently has an
     *    active Assignment for this row's client, and the user is one of the
     *    approver steps in that Assignment's config.
     *
     * Genuinely Unassigned karyawan (no active Assignment at all) still fall
     * through both branches and stay hidden, matching the 2026-07-11 decision.
     *
     * @see development/features/lembur/lembur_existing_flow.md (Section 10)
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  User  $user
     * @return void
     */
    public function apply(Builder $query, User $user): void
    {
        $assignedKaryawanIdsByClient = LemburApprovalConfigAssignment::query()
            ->where('is_active', true)
            ->whereHas('config.steps', function ($stepQuery) use ($user) {
                $stepQuery->where('approver_user_id', $user->id);
            })
            ->get(['karyawan_id', 'client_id'])
            ->groupBy('client_id');

        $query->where(function ($query) use ($user, $assignedKaryawanIdsByClient) {
            $query->where(function ($q) use ($user) {
                $q->whereNotNull('approval_config_id')
                    ->whereHas('approvalConfig.steps', function ($stepQuery) use ($user) {
                        $stepQuery->where('approver_user_id', $user->id);
                    });
            });

            foreach ($assignedKaryawanIdsByClient as $clientId => $rows) {
                $query->orWhere(function ($q) use ($clientId, $rows) {
                    $q->whereNull('approval_config_id')
                        ->where('client_id', $clientId)
                        ->whereIn('user_id', $rows->pluck('karyawan_id'));
                });
            }
        });
    }
}
