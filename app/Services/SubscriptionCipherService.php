<?php

namespace App\Services;

use Illuminate\Support\Str;

class SubscriptionCipherService
{
    protected string $cipher = 'AES-256-CBC';

    /**
     * Build the signed HTTP headers required by the Payroll API subscription middleware.
     *
     * Header set: X-Subscription-Key, X-Subscription-Timestamp, X-Subscription-Nonce,
     * X-Subscription-Encrypted (AES-256-CBC of key|METHOD|endpoint|timestamp),
     * X-Subscription-Signature (HMAC-SHA256).
     *
     * @param  string  $subscriptionKey  Raw subscription key from config
     * @param  string  $method           HTTP method (GET, POST, etc.)
     * @param  string  $endpoint         Request path (e.g. 'api/data-lembur/approve/1')
     * @return array
     */
    public function buildHeaders(string $subscriptionKey, string $method, string $endpoint): array
    {
        $timestamp = (string) now()->timestamp;
        $nonce = (string) Str::uuid();
        $payload = sprintf('%s|%s|%s|%s', $subscriptionKey, strtoupper($method), $endpoint, $timestamp);
        $encryptedSubscription = $this->encrypt($payload, $subscriptionKey);

        return [
            'X-Subscription-Key' => $subscriptionKey,
            'X-Subscription-Timestamp' => $timestamp,
            'X-Subscription-Nonce' => $nonce,
            'X-Subscription-Encrypted' => $encryptedSubscription,
            'X-Subscription-Signature' => hash_hmac('sha256', $payload . '|' . $nonce, $subscriptionKey),
        ];
    }

    /**
     * Encrypt a plain-text string with AES-256-CBC using a SHA-256-derived key.
     * Returns base64(IV + ciphertext), or empty string on failure.
     *
     * @param  string  $plainText
     * @param  string  $subscriptionKey
     * @return string
     */
    public function encrypt(string $plainText, string $subscriptionKey): string
    {
        $key = hash('sha256', $subscriptionKey, true);
        $ivLength = openssl_cipher_iv_length($this->cipher);
        $iv = random_bytes($ivLength);

        $encrypted = openssl_encrypt($plainText, $this->cipher, $key, OPENSSL_RAW_DATA, $iv);

        if ($encrypted === false) {
            return '';
        }

        return base64_encode($iv . $encrypted);
    }

    /**
     * Decrypt a base64(IV + ciphertext) payload produced by encrypt().
     * Returns null if the payload is malformed or decryption fails.
     *
     * @param  string  $encryptedPayload
     * @param  string  $subscriptionKey
     * @return string|null
     */
    public function decrypt(string $encryptedPayload, string $subscriptionKey): ?string
    {
        $decoded = base64_decode($encryptedPayload, true);
        if ($decoded === false) {
            return null;
        }

        $key = hash('sha256', $subscriptionKey, true);
        $ivLength = openssl_cipher_iv_length($this->cipher);
        if (strlen($decoded) <= $ivLength) {
            return null;
        }

        $iv = substr($decoded, 0, $ivLength);
        $cipherText = substr($decoded, $ivLength);

        $decrypted = openssl_decrypt($cipherText, $this->cipher, $key, OPENSSL_RAW_DATA, $iv);

        return $decrypted === false ? null : $decrypted;
    }
}