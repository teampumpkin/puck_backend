<?php

namespace App\Http\Controllers\V4;

use App\Http\Controllers\Controller;
use App\Models\V4BannedUser;
use App\Models\V4User;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use App\Helpers\PushNotificationHelper;

class V4FcmTestController extends Controller
{
    /**
     * Send a test notification to a token
     */
    public function sendTestNotification(Request $request)
    {
        $token = $request->token ?? 'TEST_DEVICE_TOKEN_HERE';

        $fcm = new PushNotificationHelper();

        $response = $fcm->sendToToken(
            $token,
            "Test Notification",
            "This is a test message from Laravel!",
            [
                'badge' => 1,
                'type' => 'test_notification',
            ]
        );

        return response()->json($response);
    }

    /**
     * Test FCM Configuration
     */
    public function testConfig()
    {
        $fcm = new PushNotificationHelper();

        $response = $fcm->testConfiguration();

        return response()->json($response);
    }
}
