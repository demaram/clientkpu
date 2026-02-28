<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayrollApiService
{
    protected $baseUrl;
    protected $timeout;

    public function __construct()
    {
        $this->baseUrl = env('PAYROLL_URL', 'https://sip-dev.kpusahatama.id/');
        $this->timeout = 30;
    }

    /**
     * Login ke Payroll API
     * 
     * @param string $email
     * @param string $password
     * @return array
     */
    public function login($email, $password)
    {
        try {
            $response = Http::timeout($this->timeout)
                ->post($this->baseUrl . '/auth/login', [
                    'email' => $email,
                    'password' => $password,
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'message' => $response->json('message') ?? 'Login gagal',
                'errors' => $response->json('errors') ?? []
            ];

        } catch (\Exception $e) {
            Log::error('PayrollApiService Login Error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Terjadi kesalahan saat menghubungi server',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Logout dari Payroll API
     * 
     * @param string $token
     * @return array
     */
    public function logout($token)
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withToken($token)
                ->post($this->baseUrl . '/auth/logout');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'message' => $response->json('message') ?? 'Logout gagal'
            ];

        } catch (\Exception $e) {
            Log::error('PayrollApiService Logout Error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Terjadi kesalahan saat logout',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get user info dari token
     * 
     * @param string $token
     * @return array
     */
    public function me($token)
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withToken($token)
                ->get($this->baseUrl . '/auth/me');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'message' => $response->json('message') ?? 'Gagal mengambil data user'
            ];

        } catch (\Exception $e) {
            Log::error('PayrollApiService Me Error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Terjadi kesalahan',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Refresh token
     * 
     * @param string $token
     * @return array
     */
    public function refreshToken($token)
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withToken($token)
                ->post($this->baseUrl . '/auth/refresh');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'message' => $response->json('message') ?? 'Gagal refresh token'
            ];

        } catch (\Exception $e) {
            Log::error('PayrollApiService Refresh Token Error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Terjadi kesalahan',
                'error' => $e->getMessage()
            ];
        }
    }
}
