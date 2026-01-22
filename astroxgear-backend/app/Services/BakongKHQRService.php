<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class BakongKHQRService
{
    private $accountUsername;
    private $accountName;
    private $baseApiUrl;
    private $accessToken;

    public function __construct()
    {
        $this->accountUsername = env('BAKONG_ACCOUNT_USERNAME');
        $this->accountName     = env('BAKONG_ACCOUNT_NAME', 'Demo Merchant');
        $this->baseApiUrl      = env('BAKONG_PROD_BASE_API_URL');
        $this->accessToken    = env('BAKONG_ACCESS_TOKEN');
    }

    /**
     * Generate KHQR code (Individual Payment)
     */
    public function generateIndividualQR($amount, $currency = 'USD')
    {
        // Bakong expects milliseconds
        $expirationTimestamp = now()->addMinutes(5)->timestamp * 1000;

        $qrData = $this->buildKHQRString(
            (float) $amount,
            $currency,
            $expirationTimestamp
        );

        return [
            'qr'         => $qrData,
            'md5'        => md5($qrData),
            'expiration' => $expirationTimestamp,
        ];
    }

    /**
     * Build KHQR string (EMVCo + Bakong)
     */
    private function buildKHQRString($amount, $currency, $expirationTimestamp)
    {
        // Payload Format Indicator
        $qr = $this->buildTLV('00', '01');

        // Point of Initiation Method (Dynamic)
        $qr .= $this->buildTLV('01', '12');

        // Merchant Account Information (Bakong)
        $bakongData  = $this->buildTLV('00', 'com.bakong');
        $bakongData .= $this->buildTLV('01', $this->accountUsername);
        $bakongData .= $this->buildTLV('02', $this->accountName);

        $qr .= $this->buildTLV('29', $bakongData);

        // Currency
        $currencyCode = $currency === 'KHR' ? '116' : '840';
        $qr .= $this->buildTLV('53', $currencyCode);

        // Amount (DOT decimal, 2 digits – IMPORTANT)
        $qr .= $this->buildTLV(
            '54',
            number_format($amount, 2, '.', '')
        );

        // Country Code
        $qr .= $this->buildTLV('58', 'KH');

        // Merchant Name
        $qr .= $this->buildTLV('59', substr($this->accountName, 0, 25));

        // Merchant City
        $qr .= $this->buildTLV('60', 'Phnom Penh');

        // Additional Data
        $additionalData  = $this->buildTLV('05', '***');
        $additionalData .= $this->buildTLV(
            '99',
            substr((string) $expirationTimestamp, -10)
        );

        $qr .= $this->buildTLV('62', $additionalData);

        // CRC
        $qr .= '6304';
        $qr .= $this->calculateCRC16($qr);

        return $qr;
    }

    /**
     * Build TLV (Tag-Length-Value)
     */
    private function buildTLV($tag, $value)
    {
        $length = str_pad(strlen($value), 2, '0', STR_PAD_LEFT);
        return $tag . $length . $value;
    }

    /**
     * CRC16-CCITT
     */
    private function calculateCRC16($data)
    {
        $crc = 0xFFFF;
        $polynomial = 0x1021;

        for ($i = 0; $i < strlen($data); $i++) {
            $crc ^= ord($data[$i]) << 8;

            for ($j = 0; $j < 8; $j++) {
                if ($crc & 0x8000) {
                    $crc = ($crc << 1) ^ $polynomial;
                } else {
                    $crc <<= 1;
                }
            }
        }

        return strtoupper(
            str_pad(dechex($crc & 0xFFFF), 4, '0', STR_PAD_LEFT)
        );
    }

    /**
     * Check payment with Bakong API
     */
    public function checkPayment($md5)
    {
        if (!$this->baseApiUrl || !$this->accessToken) {
            throw new \Exception('Bakong API credentials not configured');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->accessToken,
        ])->post($this->baseApiUrl . '/check_transaction_by_md5', [
            'md5' => $md5,
        ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to check payment with Bakong API');
        }

        return $response->json();
    }
}