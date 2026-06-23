<?php

namespace App\Services\Payments;

/**
 * Decodes a StoreKit2 JWS transaction receipt WITHOUT verifying its signature.
 *
 * Used to bind a receipt to a listing (via appAccountToken) and to dedup on the
 * real transactionId. Signature verification (x5c -> Apple Root CA) is a tracked
 * follow-up; until then, treat decoded values as untrusted hints, sufficient to
 * prevent ACCIDENTAL cross-listing publishes, not malicious spoofing.
 */
class Sk2ReceiptDecoder
{
    public static function decode(?string $jws): ?array
    {
        if (empty($jws)) {
            return null;
        }
        $parts = explode('.', $jws);
        if (count($parts) !== 3) {
            return null;
        }
        $json = self::base64UrlDecode($parts[1]);
        if ($json === null) {
            return null;
        }
        $data = json_decode($json, true);
        if (!is_array($data)) {
            return null;
        }

        return [
            'transaction_id' => isset($data['transactionId']) ? (string) $data['transactionId'] : null,
            'original_transaction_id' => isset($data['originalTransactionId']) ? (string) $data['originalTransactionId'] : null,
            'product_id' => $data['productId'] ?? null,
            'app_account_token' => $data['appAccountToken'] ?? null,
            'environment' => $data['environment'] ?? null,
        ];
    }

    private static function base64UrlDecode(string $segment): ?string
    {
        $normalized = strtr($segment, '-_', '+/');
        $pad = strlen($normalized) % 4;
        if ($pad > 0) {
            $normalized .= str_repeat('=', 4 - $pad);
        }
        $decoded = base64_decode($normalized, true);
        return $decoded === false ? null : $decoded;
    }
}
