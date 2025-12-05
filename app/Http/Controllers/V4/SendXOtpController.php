<?php

namespace App\Http\Controllers\V4;

use App\Http\Controllers\Controller;
use Log;
use sendx\Configuration;
use sendx\api\EmailSendingApi;
use sendx\model\XEmailMessage;
use sendx\model\XFrom;

class SendXOtpController extends Controller
{
    public static function sendOtp(string $email, string $otp)
    {
        // configure API key
        $config = Configuration::getDefaultConfiguration()
            ->setApiKey('X-Team-ApiKey', env('SENDX_API_KEY'));

        $apiInstance = new EmailSendingApi(null, $config);

        // prepare message
        $msg = new XEmailMessage();
        $msg->setSubject("Your OTP Code");
        $msg->setHtmlBody("<p>Your OTP is: <strong>$otp</strong></p>");
        $msg->setFrom(
            (new XFrom())
                ->setEmail(env("SENDX_FROM_EMAIL"))
                ->setName(env("SENDX_FROM_NAME"))
        );
        $msg->setTo([
            ["email" => $email]
        ]);

        try {
            return $apiInstance->sendEmail($msg);
        } catch (\Exception $e) {
            Log::error('SendX OTP error: ' . $e->getMessage());
            return "Error: " . $e->getMessage();
        }
    }
}
