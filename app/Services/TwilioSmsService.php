<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;

class TwilioSmsService
{
    protected $client;
    protected $fromNumber;

    public function __construct()
    {
        $sid = config('services.twilio.sid');
        $token = config('services.twilio.token');
        $this->fromNumber = config('services.twilio.phone_number');

        $this->client = new Client($sid, $token);
    }

    public function sendSms(string $to, string $message): bool
    {
        try {
            $this->client->messages->create(
                $to,
                [
                    'from' => $this->fromNumber,
                    'body' => $message,
                ]
            );
            return true;
        } catch (Exception $e) {
            Log::error('Twilio SMS Error: ' . $e->getMessage());
            return false;
        }
    }
}
