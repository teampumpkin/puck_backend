<?php

namespace Tests\Unit\Payments;

use App\Services\Payments\Sk2ReceiptDecoder;
use PHPUnit\Framework\TestCase;

class Sk2ReceiptDecoderTest extends TestCase
{
    private function jws(array $payload): string
    {
        $b64 = fn (string $s) => rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
        $header = $b64(json_encode(['alg' => 'ES256', 'x5c' => ['cert']]));
        $body = $b64(json_encode($payload));
        return "$header.$body.signature";
    }

    public function test_decodes_transaction_and_token(): void
    {
        $jws = $this->jws([
            'transactionId' => '2000000999',
            'originalTransactionId' => '2000000111',
            'productId' => 'test_marketplace_listing_fee',
            'appAccountToken' => '11111111-1111-4111-8111-111111111111',
            'environment' => 'Sandbox',
        ]);

        $out = Sk2ReceiptDecoder::decode($jws);

        $this->assertSame('2000000999', $out['transaction_id']);
        $this->assertSame('11111111-1111-4111-8111-111111111111', $out['app_account_token']);
        $this->assertSame('test_marketplace_listing_fee', $out['product_id']);
    }

    public function test_null_for_empty_or_malformed(): void
    {
        $this->assertNull(Sk2ReceiptDecoder::decode(null));
        $this->assertNull(Sk2ReceiptDecoder::decode(''));
        $this->assertNull(Sk2ReceiptDecoder::decode('only.two'));
        $this->assertNull(Sk2ReceiptDecoder::decode('a.!!!notbase64!!!.c'));
    }
}
