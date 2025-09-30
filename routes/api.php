<?php

use App\Http\Controllers\API\Admin\AdminEvaluatorController;
use App\Http\Controllers\API\Admin\AdminPlayerController;
use App\Http\Controllers\API\Admin\AdminScoutController;
use App\Http\Controllers\API\Admin\AdminTeamController;
use App\Http\Controllers\API\Admin\DashboardController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\Developer\DeveloperController;
use App\Http\Controllers\API\EvaluatorController;
use App\Http\Controllers\API\PlayerController;
use App\Http\Controllers\API\ScoutController;
use App\Http\Controllers\API\SharedController;
use App\Http\Controllers\API\TeamController;
use App\Http\Controllers\API\V2\AdvanceAssessmentController;
use App\Http\Controllers\API\V2\V2AuthController;
use App\Http\Controllers\API\V2\V2EvaluationController;
use App\Http\Controllers\API\V2\V2PlayerController;
use App\Http\Controllers\API\V2\V2TeamController;
use App\Http\Controllers\API\V3\V3AdvanceAssessmentController;
use App\Http\Controllers\API\Zapier\ZapierController;
use App\Http\Controllers\AuthController as ControllersAuthController;
use App\Http\Controllers\GuardianController;
use App\Http\Controllers\PlayableController;
use App\Http\Controllers\V4\ProfileController;
use App\Http\Controllers\V4\V4MediaController;
use App\Http\Controllers\V4\Chat\V4ChatMediaController;
use App\Http\Controllers\StripeController;
use App\Http\Controllers\V4\V4AuthController;
use App\Http\Controllers\V4\UserBlockController;
use App\Http\Controllers\WebSocketController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\EvaluationRejectionReasonController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get("test", function () {
    phpinfo();
    die;
    return "Test URL hit";
});

Route::get('privacy-policy', function () {
    return view('privacy-policy');
});

Route::get('terms-and-conditions', function () {
    return view('terms-and-conditions');
});

// WebSocket client event webhook
// Route::post('websocket/client-events', [WebSocketController::class, 'handleClientEvent']);

// Broadcasting authentication route (dev-only bypass if WS_AUTH_BYPASS=true)
if (env('WS_AUTH_BYPASS', false)) {
    Route::post('broadcasting/auth', function (Request $request) {
        $channel = $request->input('channel_name');
        $socketId = $request->input('socket_id');
        $sig = hash_hmac('sha256', $socketId . ':' . $channel, env('PUSHER_APP_SECRET'));
        return response()->json(['auth' => env('PUSHER_APP_KEY') . ':' . $sig]);
    });
}


Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('reset-password', [AuthController::class, 'resetPassword']);
Route::get("verify-token", [AuthController::class, 'verifyToken']);
Route::get("verify-otp", [AuthController::class, 'verifyOTP']);

Route::get('get-positions', [PlayerController::class, 'getPositions']);

Route::prefix('v2')->group(function () {
    Route::post('register', [V2AuthController::class, 'register']);
});

Route::prefix('zapier')->group(function () {
    Route::post('register', [ZapierController::class, 'register']);
    Route::put("edit", [ZapierController::class, 'editUser']);
});

Route::group(['middleware' => 'login.check'], function () {
    Route::get("get-profile", [AuthController::class, 'getProfile']);
    Route::put("edit-profile", [AuthController::class, 'editProfile']);
    Route::post("change-password", [AuthController::class, 'changePassword']);

    Route::get("get-top-players", [PlayerController::class, 'getTopPlayers']);
    Route::get("get-all-players", [PlayerController::class, 'getAllPlayers']);
    Route::post("save-unsave-user", [PlayerController::class, 'saveUnSaveUser']);
    Route::post("follow-unfollow-user", [PlayerController::class, 'followUnFollowUser']);
    Route::post("block-unblock-user", [PlayerController::class, 'blockUnBlockUser']);

    Route::get("get-followers", [PlayerController::class, 'getFollowers']);
    Route::get("get-followings", [PlayerController::class, 'getFollowings']);

    Route::get("filter", [SharedController::class, 'filterUsers']);

    Route::get("get-user-profile", [SharedController::class, 'getUserProfile']);
    Route::get("get-profile-picture", [SharedController::class, 'getProfileImage']);

    Route::get('team-list', [TeamController::class, 'getTeams']);
    Route::get('team', [TeamController::class, 'getTeam']);

    Route::get("get-skills", [ScoutController::class, 'getSkills']);
    Route::get("get-scout-requests", [ScoutController::class, 'getScoutRequest']);
    Route::put("update-scout-request", [ScoutController::class, 'updateScoutRequest']);
    Route::post("request-status-update", [ScoutController::class, 'requestStatusUpdate']);
    Route::post("submit-scouting-report", [ScoutController::class, 'submitScoutingReport']);
    Route::post("publish-scouting-report", [ScoutController::class, 'publishScoutingReport']);
    Route::post("send-scout-request", [PlayerController::class, 'sendScoutRequest']);
    Route::post("cancel-scout-request", [PlayerController::class, 'cancelScoutRequest']);
    Route::get("medias", [PlayerController::class, 'playerMedias']);
    Route::put('media-edit', [PlayerController::class, 'playerMediaEdit']);
    Route::post("media-upload", [PlayerController::class, 'playerMediaUpload']);
    Route::get("media-download", [PlayerController::class, 'playerMediaDownload']);
    Route::post("scout-status-change", [AdminScoutController::class, 'scoutStatusChange']);
    Route::get("dashboard", [DashboardController::class, 'dashboard']);
    Route::get("get-all-scouts", [AdminScoutController::class, 'getAllScouts']);
    Route::get("get-all-evaluators", [EvaluatorController::class, 'getAllEvaluators']);
    Route::get("get-all-teams", [AdminTeamController::class, 'getTeams']);
    Route::post("evaluator-status-change", [AdminEvaluatorController::class, 'evaluatorStatusChange']);
    Route::post("team-status-change", [AdminTeamController::class, 'teamStatusChange']);
    Route::get('modules', [DeveloperController::class, 'getAllModules']);
    Route::get('user-types', [DeveloperController::class, 'getAllUserTypes']);
    Route::put('update-permission', [DeveloperController::class, 'updatePermission']);
    Route::get("get-reports", [SharedController::class, 'getReports']);
    Route::get("get-report", [SharedController::class, 'getReport']);
    Route::post("save-unsave-report", [SharedController::class, 'saveUnSaveReport']);
    Route::get("get-saved-players", [SharedController::class, 'getSavedPlayers']);
    Route::get("get-saved-teams", [SharedController::class, 'getSavedTeams']);
    Route::get("get-saved-reports", [SharedController::class, 'getSavedReports']);
    Route::post('add-manager', [TeamController::class, 'addManager']);
    Route::post('add-coach', [TeamController::class, 'addCoach']);
    Route::post('add-player', [TeamController::class, 'addPlayer']);

    Route::get('get-all-academies', [AdminTeamController::class, 'getAcademies']);

    Route::post('add-module', [DeveloperController::class, 'addNewModule']);

    Route::delete('media-delete', [PlayerController::class, 'deleteMedia']);
    Route::get('api-logs', [DeveloperController::class, 'getAPILogs']);
    Route::delete('delete-api-logs', [DeveloperController::class, 'removeLogs']);

    Route::put('edit-team-member', [TeamController::class, 'editTeamMember']);
    Route::delete('remove-team-member', [TeamController::class, 'removeTeamMember']);

    Route::get('positions', [AdminPlayerController::class, 'getPlayerPositions']);
    Route::post('add-position', [AdminPlayerController::class, 'addPosition']);

    Route::post('convert-to-evaluator', [AdminScoutController::class, 'convertToEvaluator']);

    Route::get('get-filters', [SharedController::class, 'getFiltersData']);

    Route::get('zoho-plans', [SharedController::class, 'getPlans']);
    Route::post('generate-payment-page', [SharedController::class, 'generatePaymentPage']);

    Route::post('add-new-plan', [SharedController::class, 'addNewPlan']);

    Route::get('get-chat-id', [SharedController::class, 'getChatId']);
    Route::get('recent-chats', [SharedController::class, 'getRecentChats']);

    Route::post('cancel-subscription', [SharedController::class, 'cancelSubscription']);

    Route::prefix('v2')->group(function () {
        // Pagination
        Route::get("search", [V2PlayerController::class, 'search']);
        Route::get("get-players", [V2PlayerController::class, 'getPlayers']);
        Route::get('team-list', [V2TeamController::class, 'getTeams']);
        Route::get("get-saved-teams", [V2TeamController::class, 'getSavedTeams']);

        Route::get("get-scout-requests", [V2EvaluationController::class, 'getScoutRequest']);
        Route::get("get-reports", [V2EvaluationController::class, 'getReports']);
        Route::get("get-report", [V2EvaluationController::class, 'getReport']);

        Route::get("get-skills", [AdvanceAssessmentController::class, 'getSkills']);
        Route::post("send-scout-request", [AdvanceAssessmentController::class, 'sendScoutRequest']);
        Route::post("submit-scouting-report", [AdvanceAssessmentController::class, 'submitScoutingReport']);
    });

    Route::prefix('v3')->group(function () {
        Route::post("submit-scouting-report", [V3AdvanceAssessmentController::class, 'submitScoutingReport']);
    });
    Route::get('assessment-categories', [AdvanceAssessmentController::class, 'getAssessmentCategories']);
    Route::post('add-assessment-category', [AdvanceAssessmentController::class, 'addAssessmentCategory']);
    Route::post('assessment-categories-status-change', [AdvanceAssessmentController::class, 'assessmentCategoriesStatusChange']);

    Route::get('assessment-skills', [AdvanceAssessmentController::class, 'getAssessmentSkills']);
    Route::post('add-assessment-skill', [AdvanceAssessmentController::class, 'addAssessmentSkill']);
    Route::get('assessment-skill-values', [AdvanceAssessmentController::class, 'getAssessmentSkillValues']);
    Route::get('assessment-skill-values-by-rating', [AdvanceAssessmentController::class, 'getAssessmentStatementsBySkill']);

    Route::post('manage-assessment-statement', [AdvanceAssessmentController::class, 'manageAssessmentStatement']);

    Route::get('assessment-category', [AdvanceAssessmentController::class, 'getAssessmentCategory']);

    Route::get('leagues', [SharedController::class, 'getLeagues']);

    Route::get('filtered-assessment-skill', [AdvanceAssessmentController::class, 'assessmentSkillOfCategory']);

    Route::post("one-time-evaluation-payment-page", [AdvanceAssessmentController::class, 'generateOneTimeEvaluationPaymentPage']);
    Route::post("one-time-payment-page", [AdvanceAssessmentController::class, 'generateOneTimePaymentPage']);

    Route::get('mentorship-plan-price', [PlayerController::class, 'mentorshipPlanPrice']);
    Route::get('popup-description', [SharedController::class, 'popupDescription']);

    Route::get('get-playables', [PlayableController::class, 'getPlayableList']);

    Route::get('notification-preferences', [PlayerController::class, 'getNotificationPreferences']);
    Route::put('update-notification-preferences', [PlayerController::class, 'setNotificationPreferences']);

    Route::put('change-status-account', [SharedController::class, 'changeStatusAccount']);

    Route::post('register-evaluator', [V2AuthController::class, 'registerEvaluator']);

    Route::post('pay-playable', [SharedController::class, 'generatePaymentStripe']);

    // Stripe
    Route::prefix('stripe')->group(function () {
        Route::post('create-customer', [StripeController::class, 'createCustomer']);
        Route::get('get-customer', [StripeController::class, 'getCustomer']);
        Route::post('create-payment-method', [StripeController::class, 'createPaymentMethod']);
        Route::post('attach-payment-method', [StripeController::class, 'attachPaymentMethod']);
        Route::post('detach-payment-method', [StripeController::class, 'detachPaymentMethod']);
        Route::get('get-payment-methods', [StripeController::class, 'getPaymentMethods']);
        Route::post('create-payment-intent', [StripeController::class, 'createPaymentIntent']);
        Route::post('confirm-payment-intent', [StripeController::class, 'confirmPaymentIntent']);
        Route::get('get-payments', [StripeController::class, 'getPayments']);
    });
});

Route::get('countries', [SharedController::class, 'getCountries']);
Route::get('states', [SharedController::class, 'getStates']);
Route::get('cities', [SharedController::class, 'getCities']);

Route::get('save-subscription', [SharedController::class, 'saveSubscription']);
Route::get('save-mentorship-subscription', [SharedController::class, 'saveMentorshipSubscription']);

Route::get("no-cache/verify-account/{token}", [ControllersAuthController::class, 'verifyAccount']);
Route::get('no-cache/accept/{token}', [GuardianController::class, 'acceptRequest']);
Route::get('no-cache/{token}', [GuardianController::class, 'rejectRequest']);


//V4-Routes
Route::prefix('v4')->group(function () {
    Route::post('send-login-otp', [V4AuthController::class, 'sendLoginOtp']);
    Route::post('verify-login-otp', [V4AuthController::class, 'verifyLoginOtp']);
    Route::post('/child-login', [V4AuthController::class, 'childLogin']);

    Route::middleware('auth:v4api')->group(function () {
        Route::get('/profile', [ProfileController::class, 'getProfileData']);
        Route::post('/profile-batch', [ProfileController::class, 'getProfileBatchData']);
        ;
        Route::post('/update-profile', [ProfileController::class, 'updateProfile']);
        Route::post('/add-child', [ProfileController::class, 'addChild']);
        Route::post('/update-child-permissions/{childId}', [ProfileController::class, 'updateChildPermissions']);
        Route::post('/update-child-credentials/{childId}', [ProfileController::class, 'updateChildCredentials']);
        Route::get('/search-users', [ProfileController::class, 'searchUsers']);

        // Evaluation
        Route::get('evaluation-rejection-reasons', [EvaluationRejectionReasonController::class, 'getActiveReasons']);


        // Media routes
        Route::post('/upload-media', [V4MediaController::class, 'uploadMedia']);
        Route::get('/all-media', [V4MediaController::class, 'getAllMedia']);
        Route::put('/edit-media/{id}', [V4MediaController::class, 'editMedia']);
        Route::delete('/delete-media/{id}', [V4MediaController::class, 'deleteMedia']);

        // Chat routes
        Route::prefix('/chat')->group(function () {
            // Chat media routes
            Route::post('/upload-media', [V4ChatMediaController::class, 'uploadMedia']);
            Route::get('/get-media', [V4ChatMediaController::class, 'getMedia']);

            // Direct chat routes (keeping commented for now)
            // Route::get('/get-chat-id', [\App\Http\Controllers\V4\Chat\V4ChatController::class, 'getChatId']);
            // Route::get('/recent-chats', [\App\Http\Controllers\V4\Chat\V4ChatController::class, 'getRecentChats']);
            // Route::post('/send-message', [\App\Http\Controllers\V4\Chat\V4ChatController::class, 'sendMessage']);
            // Route::post('/send-media-message', [\App\Http\Controllers\V4\Chat\V4ChatController::class, 'sendMediaMessage']);
            // Route::get('/get-messages', [\App\Http\Controllers\V4\Chat\V4ChatController::class, 'getMessages']);
            // Route::put('/mark-as-read', [\App\Http\Controllers\V4\Chat\V4ChatController::class, 'markAsRead']);

            // Group chat routes (keeping commented for now)
            // Route::post('/create-group-chat', [\App\Http\Controllers\V4\Chat\V4ChatController::class, 'createGroupChat']);
            // Route::get('/group-chats', [\App\Http\Controllers\V4\Chat\V4ChatController::class, 'getGroupChats']);
            // Route::post('/add-participants', [\App\Http\Controllers\V4\Chat\V4ChatController::class, 'addParticipants']);
            // Route::post('/remove-participants', [\App\Http\Controllers\V4\Chat\V4ChatController::class, 'removeParticipants']);
        });

        // Block/Unblock routes
        Route::post('/block-user', [UserBlockController::class, 'blockUser']);
        Route::post('/unblock-user/{userId}', [UserBlockController::class, 'unblockUser']);

        // Get blocked users list
        Route::get('/blocked-users', [UserBlockController::class, 'getBlockedUsers']);

        // Get block history
        Route::get('/block-history', [UserBlockController::class, 'getBlockHistory']);

        // Check block status
        Route::get('/check-block-status/{userId}', [UserBlockController::class, 'checkBlockStatus']);
    });
});

// // Parent routes
// Route::get('/children', [ParentController::class, 'listChildren']);
