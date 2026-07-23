<?php

namespace App\Services;

use App\Models\LemburApprovalConfigStep;
use App\Models\LemburKaryawan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Shared workflow logic for LemburKaryawan records, used by both LemburController
 * (type=lembur) and PiketController (type=piket) so step-validation, photo
 * handling, and the Payroll API proxy calls aren't duplicated between the two.
 */
class LemburKaryawanWorkflowService
{
    public function __construct(
        private readonly SubscriptionCipherService $subscriptionCipher
    ) {}

    /**
     * Human label for a record type, used in user-facing messages ("Lembur"/"Piket").
     */
    public function label(string $type): string
    {
        return $type === 'piket' ? 'Piket' : 'Lembur';
    }

    /**
     * Route name for the type's listing page.
     */
    public function indexRoute(string $type): string
    {
        return $type === 'piket' ? 'admin.piket.index' : 'admin.lembur.index';
    }

    /**
     * The active approval step for a record, or null if it's on the legacy
     * (non-multi-step) flow or the step row can't be found.
     */
    public function getActiveStep(LemburKaryawan $record): ?LemburApprovalConfigStep
    {
        if (!$record->approval_config_id) {
            return null;
        }

        return LemburApprovalConfigStep::where('lembur_approval_config_id', $record->approval_config_id)
            ->where('step_order', $record->current_approval_step)
            ->first();
    }

    /**
     * Store an uploaded photo to the shared custom_public disk, using the same
     * "lembur/{prefix}_{userId}_{timestamp}" convention portalkpu already uses
     * for both lembur and piket check-in/out photos (prefix is always
     * 'lembur_in'/'lembur_out' regardless of type — this matches existing data).
     */
    public function storePhoto($file, int $userId, string $prefix): string
    {
        $filename = "{$prefix}_{$userId}_" . date('YmdHis') . '.' . $file->extension();
        $path     = "lembur/{$filename}";
        Storage::disk('custom_public')->put($path, file_get_contents($file->getRealPath()));
        return $path;
    }

    /**
     * Delete photos stored during an update that subsequently failed.
     */
    public function rollbackPhotos(?string $startPhoto, ?string $endPhoto): void
    {
        if ($startPhoto) {
            Storage::disk('custom_public')->delete($startPhoto);
        }

        if ($endPhoto) {
            Storage::disk('custom_public')->delete($endPhoto);
        }
    }

    /**
     * Proxy an approve action to the Payroll API for the given type ('lembur'/'piket').
     *
     * @return array{success:bool, status:int, body:array}
     */
    public function proxyApprove(string $type, int $id, int $actorUserId): array
    {
        return $this->proxy('post', $this->apiPrefix($type) . "/approve/{$id}", [
            'status_by'   => $actorUserId,
            'status_from' => 'client',
        ]);
    }

    /**
     * Proxy a reject action to the Payroll API for the given type ('lembur'/'piket').
     *
     * @param  bool  $skipActorCheck  Bypass payroll's step-approver ownership check —
     *                                used by the recap "Reject" row action, since a
     *                                recap_user is not necessarily an approver on the
     *                                record's config. The normal Lembur/Piket page
     *                                reject leaves this false, so step ownership is
     *                                still enforced there.
     * @return array{success:bool, status:int, body:array}
     */
    public function proxyReject(string $type, int $id, int $actorUserId, ?string $notes, bool $skipActorCheck = false): array
    {
        return $this->proxy('post', $this->apiPrefix($type) . "/reject/{$id}", [
            'status_by'        => $actorUserId,
            'status_from'      => 'client',
            'notes'            => $notes,
            'skip_actor_check' => $skipActorCheck,
        ]);
    }

    /**
     * Proxy an update (PUT) action to the Payroll API for the given type ('lembur'/'piket').
     *
     * @return array{success:bool, status:int, body:array}
     */
    public function proxyUpdate(string $type, int $id, array $payload): array
    {
        return $this->proxy('put', $this->apiPrefix($type) . "/{$id}", $payload);
    }

    /**
     * Proxy a request-update ("reopen") action to the Payroll API for the given
     * type ('lembur'/'piket') — resets an approved record back to waiting_approval
     * at its first step. Used by the recap "Request Update" button.
     *
     * @return array{success:bool, status:int, body:array}
     */
    public function proxyRequestUpdate(string $type, int $id, int $actorUserId, string $notes): array
    {
        return $this->proxy('post', $this->apiPrefix($type) . "/request-update/{$id}", [
            'status_by'   => $actorUserId,
            'status_from' => 'client',
            'notes'       => $notes,
        ]);
    }

    /**
     * Build encrypted subscription headers, call the Payroll API, and normalize
     * the result — uniformly handling missing config and network failures so
     * callers don't need their own try/catch per action.
     *
     * @return array{success:bool, status:int, body:array}
     */
    private function proxy(string $method, string $endpoint, array $payload): array
    {
        $payrollBaseUrl  = rtrim((string) config('services.payroll.api_url'));
        $subscriptionKey = (string) config('services.payroll.subscription_key', env('SUBSCRIPTION_KEY'));

        if (!$payrollBaseUrl || !$subscriptionKey) {
            return [
                'success' => false,
                'status'  => 500,
                'body'    => ['message' => 'Konfigurasi Payroll API atau Subscription Key belum diatur'],
            ];
        }

        $headers = $this->subscriptionCipher->buildHeaders($subscriptionKey, $method, $endpoint);

        if (empty($headers['X-Subscription-Encrypted'])) {
            return [
                'success' => false,
                'status'  => 500,
                'body'    => ['message' => 'Gagal mengenkripsi subscription payload'],
            ];
        }

        try {
            $response = Http::timeout(30)
                ->acceptJson()
                ->withHeaders($headers)
                ->{$method}($payrollBaseUrl . $endpoint, $payload);

            return [
                'success' => $response->successful(),
                'status'  => $response->status(),
                'body'    => $response->json() ?? [],
            ];
        } catch (\Throwable $e) {
            Log::error('Payroll API proxy gagal', [
                'endpoint' => $endpoint,
                'error'    => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'status'  => 500,
                'body'    => ['message' => 'Terjadi kesalahan saat menghubungi Payroll API'],
            ];
        }
    }

    /**
     * API path prefix for the type ('api/data-lembur' / 'api/data-piket').
     */
    private function apiPrefix(string $type): string
    {
        return $type === 'piket' ? 'api/data-piket' : 'api/data-lembur';
    }
}
