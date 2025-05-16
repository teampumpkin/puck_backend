<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\AddNewPlanRequest;
use App\Http\Requests\API\ChangeStatusAccountRequest;
use App\Http\Requests\API\CityRequest;
use App\Http\Requests\API\FilterRequest;
use App\Http\Requests\API\GeneratePaymentPageRequest;
use App\Http\Requests\API\SaveUnSaveReportRequest;
use App\Http\Requests\API\ScoutReportRequest;
use App\Http\Requests\API\StateRequest;
use App\Http\Requests\API\UserProfileRequest;
use App\Http\Requests\API\ZohoRequest;
use App\Repositories\SharedRepository;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 *
 */
class SharedController extends Controller
{
    /**
     * @var SharedRepository
     */
    private $shared_repository;

    /**
     *
     */
    public function __construct()
    {
        $this->shared_repository = new SharedRepository();
    }

    /**
     * @OA\Post(
     * path="/save-unsave-report",
     * summary="save unsave report",
     * description="save unsave report using report id",
     * operationId="saveUnsaveReport",
     * tags={"Common"},
     * security={{"apiAuth":{}}},
     * @OA\RequestBody(
     *    required=true,
     *    description="Save or Unsave report",
     *    @OA\JsonContent(
     *       required={"report_id"},
     *       @OA\Property(property="report_id", type="integer", example=1)
     *    ),
     * ),
     * @OA\Response(
     *    response=200,
     *    description="Success response",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Report has been saved successfully!")
     *        )
     *     )
     * )
     */
    public function saveUnSaveReport(SaveUnSaveReportRequest $request)
    {
        DB::beginTransaction();
        try {
            $return_message = $this->shared_repository->saveUnSaveReport($request->header('Authorization'),
                $request->report_id);

            DB::commit();
            return prepare_response(200, true, $return_message);
        } catch (Exception $e) {
            DB::rollBack();
            return exceptionMessage($e);
        }
    }

    /**
     * @OA\Get (
     * path="/get-reports",
     * summary="Get scouting reports",
     * description="Retrieve Scouting reports",
     * operationId="getReports",
     * tags={"Common"},
     * security={{"apiAuth":{}}},
     * @OA\Response(
     *    response=200,
     *    description="",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Here are the scouting reports")
     *        )
     *     )
     * )
     */
    public function getReports(Request $request)
    {
        try {
            $reports = $this->shared_repository->getReports($request->header('Authorization'));

            return prepare_response(200, true, __('messages.assessment_report_list'), $reports);
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }

    /**
     * @OA\Get (
     * path="/get-report",
     * summary="Get report",
     * description="Retrieve Scouting report",
     * operationId="getReport",
     * tags={"Common"},
     * security={{"apiAuth":{}}},
     * @OA\Parameter(
     *    description="Report id",
     *    in="query",
     *    name="report_id",
     *    required=true,
     *    example="1",
     *    @OA\Schema(
     *       type="integer",
     *       format="number"
     *    )
     * ),
     * @OA\Response(
     *    response=200,
     *    description="",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Here is the scouting report detail")
     *        )
     *     )
     * )
     */
    public function getReport(ScoutReportRequest $request)
    {
        try {
            $report = $this->shared_repository->getReport($request->header('Authorization'), $request->report_id);

            return prepare_response(200, true, __('messages.assessment_report_detail'), $report);
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }

    /**
     * @OA\Get (
     * path="/get-saved-players",
     * summary="Get all saved players",
     * description="Retrieve saved player list",
     * operationId="getSavedPlayers",
     * tags={"Common"},
     * security={{"apiAuth":{}}},
     * @OA\Response(
     *    response=200,
     *    description="",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Here is the list of your saved players")
     *        )
     *     )
     * )
     */
    public function getSavedPlayers(Request $request)
    {
        try {
            $saved_players = $this->shared_repository->getSavedPlayers($request->header('Authorization'));

            return prepare_response(200, true, __('messages.favourite_player_list'), $saved_players);
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }

    /**
     * @OA\Get (
     * path="/get-saved-teams",
     * summary="Get all saved teams",
     * description="Retrieve saved player list",
     * operationId="getSavedTeams",
     * tags={"Common"},
     * security={{"apiAuth":{}}},
     * @OA\Response(
     *    response=200,
     *    description="",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Here is the list of your saved teams")
     *        )
     *     )
     * )
     */
    public function getSavedTeams(Request $request)
    {
        try {
            $saved_teams = $this->shared_repository->getSavedPlayers($request->header('Authorization'), true);

            return prepare_response(200, true, __('messages.favourite_team_list'), $saved_teams);
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }

    /**
     * @OA\Get (
     * path="/get-saved-reports",
     * summary="Get all saved reports",
     * description="Retrieve saved player list",
     * operationId="getSavedReports",
     * tags={"Common"},
     * security={{"apiAuth":{}}},
     * @OA\Response(
     *    response=200,
     *    description="",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Here is the list of your saved reports")
     *        )
     *     )
     * )
     */
    public function getSavedReports(Request $request)
    {
        try {
            $saved_reports = $this->shared_repository->getSavedReports($request->header('Authorization'));

            return prepare_response(200, true, __('messages.favourite_report_list'), $saved_reports);
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }

    /**
     * @OA\Get (
     * path="/filter",
     * summary="Filter API",
     * description="Retrieve Common/Team based on filter",
     * operationId="filterUsers",
     * tags={"Filter"},
     * security={{"apiAuth":{}}},
     * @OA\Parameter(
     *    description="filter",
     *    in="query",
     *    name="filter",
     *    required=true,
     *    example="John Deo",
     *    @OA\Schema(
     *       type="string",
     *       format="text"
     *    )
     * ),
     * @OA\Response(
     *    response=200,
     *    description="",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Filtered user list found")
     *        )
     *     )
     * )
     */
    public function filterUsers(FilterRequest $request)
    {
        try {
            $filtered_users = $this->shared_repository->filterUsers($request->filter, $request->header('Authorization'));

            return prepare_response(200, true, __('messages.filtered_players'), $filtered_users);
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }

    /**
     * @OA\Get (
     * path="/get-user-profile",
     * summary="Get User profile API",
     * description="Retrieve user information",
     * operationId="getUserProfile",
     * tags={"Common"},
     * security={{"apiAuth":{}}},
     * @OA\Parameter(
     *    description="User id",
     *    in="query",
     *    name="user_id",
     *    required=true,
     *    example="1",
     *    @OA\Schema(
     *       type="integer",
     *       format="number"
     *    )
     * ),
     * @OA\Response(
     *    response=200,
     *    description="",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="User information found.")
     *        )
     *     )
     * )
     */
    public function getUserProfile(UserProfileRequest $request)
    {
        try {
            $user_profile = $this->shared_repository->getUserProfile($request->user_id, $request->header('Authorization'));

            return prepare_response(200, true, __('messages.user_info'), $user_profile);
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }

    /**
     * @OA\Get (
     * path="/get-filters",
     * summary="Get Data which are require for filters",
     * description="Retrieve filter data",
     * operationId="getFiltersData",
     * tags={"Common"},
     * security={{"apiAuth":{}}},
     * @OA\Response(
     *    response=200,
     *    description="",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Filter data has been retrieve")
     *        )
     *     )
     * )
     */
    public function getFiltersData()
    {
        try {
            $filter_data = $this->shared_repository->getFilterData();

            return prepare_response(200, true, __('messages.filter_data'), $filter_data);
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }

    /**
     * @OA\Get (
     * path="/zoho-plans",
     * summary="Get Plans from ZOHO subscription",
     * description="Retrieve Plans from the ZOHO subscription",
     * operationId="getPlans",
     * tags={"Zoho Subscriptions"},
     * security={{"apiAuth":{}}},
     * @OA\Parameter(
     *    description="Player id",
     *    in="query",
     *    name="player_id",
     *    required=true,
     *    example="0",
     *    @OA\Schema(
     *       type="integer",
     *       format="number"
     *    )
     * ),
     * @OA\Response(
     *    response=200,
     *    description="",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Zoho plans have been retieve successfully")
     *        )
     *     )
     * )
     */
    public function getPlans(ZohoRequest $request)
    {
        try {
            $plans = $this->shared_repository->getPlans($request->player_id);

            return prepare_response(200, true, __('messages.zoho_plan_list'), $plans);
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }

    /**
     * @OA\Post(
     * path="/generate-payment-page",
     * summary="Generate Payment page for the player",
     * description="Generate payment page for the player",
     * operationId="generatePaymentPage",
     * tags={"Zoho Subscriptions"},
     * security={{"apiAuth":{}}},
     * @OA\RequestBody(
     *    required=true,
     *    description="Generate Payment Page",
     *    @OA\JsonContent(
     *       required={"plan_code", "player_id"},
     *       @OA\Property(property="plan_code", type="string", example="plan_code"),
     *       @OA\Property(property="player_id", type="integer", example=1)
     *    ),
     * ),
     * @OA\Response(
     *    response=200,
     *    description="Success response",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Here is payment page URL!")
     *        )
     *     )
     * )
     */
    public function generatePaymentPage(GeneratePaymentPageRequest $request)
    {
        try {
            $page_url = $this->shared_repository->generatePaymentPage($request->all());

            return prepare_response(200, true, __('messages.payment_page_url'), [
                'payment_page' => $page_url
            ]);
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }

    /**
     * @param Request $request
     * @return Application|Factory|View|JsonResponse
     */
    public function saveSubscription(Request $request)
    {
        DB::beginTransaction();
        try {
            $plan = $this->shared_repository->saveSubscription($request->all());
            DB::commit();
            if (!empty($plan['evaluation_request'])) {
                return view('thank-you-evaluation-payment');
            }
            if (!empty($plan['one_to_one_call'])) {
                return view('thank-you-one-time-payment');
            }
            return view('thank-you', compact('plan'));
        } catch (Exception $e) {
            DB::rollBack();
            return exceptionMessage($e);
        }
    }

    /**
     * @param Request $request
     * @return Application|Factory|View|JsonResponse
     */
    public function saveMentorshipSubscription(Request $request)
    {
        DB::beginTransaction();
        try {
            $this->shared_repository->saveMentorshipSubscription($request->all());
            DB::commit();
            return view('thank-you-one-time-payment');
        } catch (Exception $e) {
            DB::rollBack();
            return exceptionMessage($e);
        }
    }

    public function addNewPlan(AddNewPlanRequest $request)
    {
        try {
            $this->shared_repository->addNewPlan($request->all(), $request->header('Authorization'));

            return prepare_response(200, true, __('messages.zoho_new_plan_created'));
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }

    /**
     * @OA\Get (
     * path="/get-chat-id",
     * summary="Get Unique Chat Id",
     * description="Retrieve Chat Id",
     * operationId="getChatId",
     * tags={"Chats"},
     * security={{"apiAuth":{}}},
     * @OA\Parameter(
     *    description="User Id",
     *    in="query",
     *    name="user_id",
     *    example="1",
     *    @OA\Schema(
     *       type="integer",
     *       format="number"
     *    )
     * ),
     * @OA\Response(
     *    response=200,
     *    description="",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="ChatId has been retrieve")
     *        )
     *     )
     * )
     */
    public function getChatId(Request $request)
    {
        try {
            $uuid = $this->shared_repository->getChatId($request->header('Authorization'), $request->user_id);

            return prepare_response(200, true, __('messages.get_chat_id'), $uuid);
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }

    /**
     * @OA\Get (
     * path="/recent-chats",
     * summary="Get recent chats of the user",
     * description="Recent chats of the user",
     * operationId="getRecentChats",
     * tags={"Chats"},
     * security={{"apiAuth":{}}},
     * @OA\Response(
     *    response=200,
     *    description="",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Recent chat list has been retrieve")
     *        )
     *     )
     * )
     */
    public function getRecentChats(Request $request)
    {
        try {
            $recent_chats = $this->shared_repository->getRecentChats($request->header('Authorization'));

            return prepare_response(200, true, __('messages.recent_chat_list'), $recent_chats);
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }

    /**
     * @OA\Post (
     * path="/cancel-subscription",
     * summary="Cancel the subscription",
     * description="Cancel the subscription",
     * operationId="cancelSubscription",
     * tags={"Zoho Subscriptions"},
     * security={{"apiAuth":{}}},
     * @OA\RequestBody(
     *    required=true,
     *    description="Pass player_id",
     *    @OA\JsonContent(
     *       required={"player_id"},
     *       @OA\Property(property="player_id", type="integer", example="1"),
     *    ),
     * ),
     * @OA\Response(
     *    response=200,
     *    description="",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Your subscription will be canceled at the end of this term.")
     *        )
     *     )
     * )
     */
    public function cancelSubscription(ZohoRequest $request)
    {
        try {
            $this->shared_repository->cancelSubscription($request->player_id);
            return prepare_response(200, true, __('messages.zoho_cancel_subscription'));
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }

    /**
     * @OA\Get (
     * path="/leagues",
     * summary="Retrieve league list",
     * description="Retrieve league list",
     * operationId="getLeagues",
     * tags={"Common"},
     * security={{"apiAuth":{}}},
     * @OA\Response(
     *    response=200,
     *    description="",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Here is the list of league")
     *        )
     *     )
     * )
     */
    public function getLeagues()
    {
        try {
            $leagues = $this->shared_repository->getLeagues();

            return prepare_response(200, true, __('messages.league_list'), $leagues);
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }

    /**
     * @OA\Get (
     * path="/countries",
     * summary="Get active countries",
     * description="Get active countries",
     * operationId="getCountries",
     * tags={"Common"},
     * security={{"apiAuth":{}}},
     * @OA\Response(
     *    response=200,
     *    description="",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Countries have been found")
     *        )
     *     )
     * )
     */
    public function getCountries()
    {
        try {
            $countries = $this->shared_repository->getCountries();

            return prepare_response(200, true, __('messages.country_list'), $countries);
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }

    /**
     * @OA\Get (
     * path="/states",
     * summary="Retrieve state list base on the selected country",
     * description="Retrieve state list base on the selected country",
     * operationId="getStates",
     * tags={"Common"},
     * security={{"apiAuth":{}}},
     * @OA\Parameter(
     *    description="Pass country id",
     *    in="query",
     *    name="country_id",
     *    required=true,
     *    example="1",
     *    @OA\Schema(
     *       type="integer",
     *       format="number"
     *    )
     * ),
     * @OA\Response(
     *    response=200,
     *    description="",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="State list has been found")
     *        )
     *     )
     * )
     */
    public function getStates(StateRequest $request)
    {
        try {
            $states = $this->shared_repository->getStates($request->country_id);

            return prepare_response(200, true, __('messages.state_list'), $states);
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }

    /**
     * @OA\Get (
     * path="/cities",
     * summary="Retrieve city list base on the selected country",
     * description="Retrieve city list base on the selected country",
     * operationId="getCities",
     * tags={"Common"},
     * security={{"apiAuth":{}}},
     * @OA\Parameter(
     *    description="Pass state id",
     *    in="query",
     *    name="state_id",
     *    required=true,
     *    example="1",
     *    @OA\Schema(
     *       type="integer",
     *       format="number"
     *    )
     * ),
     * @OA\Response(
     *    response=200,
     *    description="",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="City list has been found")
     *        )
     *     )
     * )
     */
    public function getCities(CityRequest $request)
    {
        try {
            $cities = $this->shared_repository->getCities($request->state_id);

            return prepare_response(200, true, __('messages.city_list'), $cities);
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }

    /**
     * @OA\Get (
     * path="/popup-description",
     * summary="Retrieve description of different type of alerts",
     * description="Retrieve description of different type of alerts",
     * operationId="popupDescription",
     * tags={"Common"},
     * security={{"apiAuth":{}}},
     * @OA\Parameter(
     *    description="Pass alert type",
     *    in="query",
     *    name="alert_type",
     *    required=true,
     *    example="1",
     *    @OA\Schema(
     *       type="integer",
     *       format="number"
     *    )
     * ),
     * @OA\Response(
     *    response=200,
     *    description="",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Description found")
     *        )
     *     )
     * )
     */
    public function popupDescription(Request $request)
    {
        try {
            $description = $this->shared_repository->popupDescription($request->get('alert_type', 'evaluation'));

            return prepare_response(200, true, __('messages.popup_description'), $description);
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }

    /**
     * @OA\Get (
     * path="/get-profile-image",
     * summary="Get profile image",
     * description="Get profile image file instead of base64",
     * operationId="getProfileImage",
     * tags={"Common"},
     * security={{"apiAuth":{}}},
     * @OA\Parameter(
     *    description="User ID",
     *    in="query",
     *    name="user_id",
     *    required=true,
     *    example="1",
     *    @OA\Schema(
     *       type="integer",
     *       format="number"
     *    )
     * ),
     * @OA\Response(
     *    response=200,
     *    description="",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Here is the image")
     *        )
     *     )
     * )
     */
    public function getProfileImage(Request $request)
    {
        try {
            list($img, $ext) = $this->shared_repository->getProfileImage($request->get('user_id'));
            return response($img)->header('Content-Type', 'image/'.$ext);
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }

    public function changeStatusAccount(ChangeStatusAccountRequest $request)
    {
        try {
            return $this->shared_repository->updateStatusAccount($request->user_id, $request->header('Authorization'), $request->status ?? '');
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }

    public function generatePaymentStripe(Request $request){
        try {
            $charge = $this->shared_repository->createStripePay($request->header('Authorization'), $request->all());
            return prepare_response(200, true, __('Charge created succesfully'), $charge);
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }
}
