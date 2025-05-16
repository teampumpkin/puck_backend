<?php

namespace App\Http\Controllers\API\V2;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\AddAssessmentCategoryRequest;
use App\Http\Requests\API\AddAssessmentSkillRequest;
use App\Http\Requests\API\AssessmentCategoryStatusChangeRequest;
use App\Http\Requests\API\AssessmentStatementsByRatingRequest;
use App\Http\Requests\API\FilterAssessmentSkillRequest;
use App\Http\Requests\API\GenerateOneTimePaymentPageRequest;
use App\Http\Requests\API\GetAssessmentCategoryRequest;
use App\Http\Requests\API\ManageAssessmentRequest;
use App\Http\Requests\API\SendScoutRequest;
use App\Http\Requests\API\SubmitScoutingReportV2Request;
use App\Repositories\V2\AdvanceAssessmentRepository;
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
class AdvanceAssessmentController extends Controller
{
    /**
     * @var AdvanceAssessmentRepository
     */
    private $advance_assessment_repository;

    /**
     *
     */
    public function __construct()
    {
        $this->advance_assessment_repository = new AdvanceAssessmentRepository();
    }


    /**
     * @OA\Get (
     * path="/v2/get-skills",
     * summary="Get skills for scouting",
     * description="Retrieve Scouting skills",
     * operationId="getSkillsList",
     * tags={"Scouts"},
     * security={{"apiAuth":{}}},
     * @OA\Parameter(
     *    description="ID of player position",
     *    in="query",
     *    name="player_position",
     *    required=true,
     *    example="1",
     *    @OA\Schema(
     *       type="integer",
     *       format="int64"
     *    )
     * ),
     * @OA\Parameter(
     *    description="return player position info",
     *    in="query",
     *    name="player_position",
     *    required=false,
     *    example="true",
     *    @OA\Schema(
     *       type="string",
     *       format="text"
     *    )
     * ),
     * @OA\Response(
     *    response=200,
     *    description="",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Here is the skills for the assessment")
     *        )
     *     )
     * )
     */
    public function getSkills(Request $request)
    {
        try {
            $position_info = filter_var($request->position_info, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            $skills = $this->advance_assessment_repository->getSkills($request->player_position, $position_info);

            return prepare_response(200, true, __('messages.assessment_skill_list'), $skills);
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }

    /**
     * @OA\Get (
     * path="/assessment-categories",
     * summary="Get assessment categories for scouting",
     * description="Retrieve assessment categories",
     * operationId="getAssessmentCategories",
     * tags={"Admin"},
     * security={{"apiAuth":{}}},
     * @OA\Response(
     *    response=200,
     *    description="",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Here are the categories for the advanced assessment")
     *        )
     *     )
     * )
     */
    public function getAssessmentCategories(Request $request)
    {
        try {
            $assessment_categories = $this->advance_assessment_repository->getAssessmentCategories($request->get('page', 0));

            return prepare_response(200, true, __('messages.assessment_category_list'), $assessment_categories);
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }

    /**
     * @OA\Get (
     * path="/assessment-category",
     * summary="Get assessment category based on player position",
     * description="Get assessment category based on player position",
     * operationId="getAssessmentCategory",
     * tags={"Admin"},
     * security={{"apiAuth":{}}},
     * @OA\Parameter(
     *    description="ID of player position",
     *    in="query",
     *    name="player_position_id",
     *    required=true,
     *    example="1",
     *    @OA\Schema(
     *       type="integer",
     *       format="int64"
     *    )
     * ),
     * @OA\Response(
     *    response=200,
     *    description="",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Assessment categories have been retrieve")
     *        )
     *     )
     * )
     */
    public function getAssessmentCategory(GetAssessmentCategoryRequest $request)
    {
        try {
            $assessment_categories = $this->advance_assessment_repository->getAssessmentCategory($request->player_position_id);

            return prepare_response(200, true, __('messages.assessment_category_list'), $assessment_categories);
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }

    /**
     * @OA\Post(
     * path="/assessment-categories-status-change",
     * summary="Assessment categories Status Change",
     * description="Assessment categories Status Change",
     * operationId="assessmentCategoriesStatusChange",
     * tags={"Admin"},
     * security={{"apiAuth":{}}},
     * @OA\RequestBody(
     *    required=true,
     *    description="Status and Evaluator Id",
     *    @OA\JsonContent(
     *       required={"evaluator_id", "status"},
     *       @OA\Property(property="evaluator_id", type="integer", example="1"),
     *       @OA\Property(property="status", type="string", example="Active")
     *    ),
     * ),
     * @OA\Response(
     *    response=200,
     *    description="Wrong credentials response",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Status has been changed.")
     *        )
     *     )
     * )
     */
    public function assessmentCategoriesStatusChange(AssessmentCategoryStatusChangeRequest $request)
    {
        try {
            $this->advance_assessment_repository->assessmentCategoriesStatusChange($request->category_id, $request->status);

            return prepare_response(200, true, __('messages.assessment_category_status_changed'));
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }

    /**
     * @OA\Post(
     * path="/add-assessment-category",
     * summary="Add new assessment category",
     * description="Add new assessment category",
     * operationId="addAssessmentCategory",
     * tags={"Admin"},
     * security={{"apiAuth":{}}},
     * @OA\RequestBody(
     *    required=true,
     *    description="Player Position Id and Category Name",
     *    @OA\JsonContent(
     *       required={"player_position_id", "category_name"},
     *       @OA\Property(property="player_position_id", type="integer", example="1"),
     *       @OA\Property(property="category_name", type="string", example="ABC")
     *    ),
     * ),
     * @OA\Response(
     *    response=200,
     *    description="Wrong credentials response",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="New assessment category has been added.")
     *        )
     *     )
     * )
     */
    public function addAssessmentCategory(AddAssessmentCategoryRequest $request)
    {
        try {
            $this->advance_assessment_repository->addAssessmentCategory($request->all());

            return prepare_response(200, true, __('messages.new_assessment_category_add'));
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }

    /**
     * @OA\Get (
     * path="/assessment-skills",
     * summary="Get assessment skills for scouting",
     * description="Retrieve assessment skills",
     * operationId="getAssessmentSkills",
     * tags={"Admin"},
     * security={{"apiAuth":{}}},
     * @OA\Parameter(
     *    description="Player Position Id",
     *    in="query",
     *    name="player_position_id",
     *    example="0",
     *    @OA\Schema(
     *       type="int",
     *       format="int64"
     *    )
     * ),
     * @OA\Parameter(
     *    description="Assessment Category Id",
     *    in="query",
     *    name="category_id",
     *    example="0",
     *    @OA\Schema(
     *       type="int",
     *       format="int64"
     *    )
     * ),
     * @OA\Response(
     *    response=200,
     *    description="",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Here are the skills for the advanced assessment")
     *        )
     *     )
     * )
     */
    public function getAssessmentSkills(Request $request)
    {
        try {
            $assessment_skills = $this->advance_assessment_repository->getAssessmentSkills($request->all());

            return prepare_response(200, true, __('messages.assessment_skill_list'), $assessment_skills);
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }

    /**
     * @OA\Post(
     * path="/add-assessment-skill",
     * summary="Add new assessment skill",
     * description="Add new assessment skill",
     * operationId="addAssessmentSkill",
     * tags={"Admin"},
     * security={{"apiAuth":{}}},
     * @OA\RequestBody(
     *    required=true,
     *    description="Pass skill name, category id and skill info",
     *    @OA\JsonContent(
     *       required={"skill_name", "category_id"},
     *       @OA\Property(property="skill_name", type="string", example="Skating"),
     *       @OA\Property(property="skill_info", type="string", example="Skating"),
     *       @OA\Property(property="category_id", type="integer", example="1")
     *    ),
     * ),
     * @OA\Response(
     *    response=200,
     *    description="Wrong credentials response",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="New skill has been created.")
     *        )
     *     )
     * )
     */
    public function addAssessmentSkill(AddAssessmentSkillRequest $request)
    {
        try {
            $this->advance_assessment_repository->addAssessmentSkill($request->all());

            return prepare_response(200, true, __('messages.new_assessment_skill_add'));
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }

    /**
     * @OA\Post(
     * path="/v2/send-scout-request",
     * summary="send scouting request",
     * description="send scouting request",
     * operationId="sendScoutRequestV2",
     * tags={"Players"},
     * security={{"apiAuth":{}}},
     * @OA\RequestBody(
     *    required=true,
     *    description="Send scout request",
     *    @OA\JsonContent(
     *       required={"league", "media_id"},
     *       @OA\Property(property="league", type="integer", example=1),
     *       @OA\Property(property="media_id", type="integer", example=1)
     *    ),
     * ),
     * @OA\Response(
     *    response=200,
     *    description="Wrong credentials response",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Assessment request sent.")
     *        )
     *     )
     * )
     */
    public function sendScoutRequest(SendScoutRequest $request)
    {
        DB::beginTransaction();
        try {
            $this->advance_assessment_repository->sendScoutRequest($request->header('Authorization'), $request->all());
            DB::commit();
            return prepare_response(200, true, __('messages.assessment_request_sent'));
        } catch (Exception $e) {
            DB::rollBack();
            return exceptionMessage($e);
        }
    }

    /**
     * @OA\Get (
     * path="/assessment-skill-values",
     * summary="Retrieve advance assessment values",
     * description="Retrieve advance assessment values",
     * operationId="getAdvanceAssessmentSkillValues",
     * tags={"Admin"},
     * security={{"apiAuth":{}}},
     * @OA\Parameter(
     *    description="Filter with player position",
     *    in="query",
     *    name="player_position_id",
     *    required=false,
     *    example="1",
     *    @OA\Schema(
     *       type="int",
     *       format="int64"
     *    )
     * ),
     * @OA\Parameter(
     *    description="Filter with assessment category",
     *    in="query",
     *    name="category_id",
     *    required=false,
     *    example="1",
     *    @OA\Schema(
     *       type="int",
     *       format="int64"
     *    )
     * ),
     * @OA\Parameter(
     *    description="Filter with skill",
     *    in="query",
     *    name="skill_id",
     *    required=false,
     *    example="1",
     *    @OA\Schema(
     *       type="int",
     *       format="int64"
     *    )
     * ),
     * @OA\Response(
     *    response=200,
     *    description="",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Here is the list of assessment skill values")
     *        )
     *     )
     * )
     */
    public function getAssessmentSkillValues(Request $request)
    {
        try {
            $assessment_skill_values = $this->advance_assessment_repository->getAssessmentSkillValues($request->all());

            return prepare_response(200, true, __('messages.assessment_skill_value_list'), $assessment_skill_values);
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }

    /**
     * @OA\Get (
     * path="/filtered-assessment-skill",
     * summary="Retrieve assessment skill based on assessment category",
     * description="Retrieve assessment skill based on assessment category",
     * operationId="assessmentSkillOfCategory",
     * tags={"Admin"},
     * security={{"apiAuth":{}}},
     * @OA\Parameter(
     *    description="pass category id",
     *    in="query",
     *    name="category_id",
     *    required=true,
     *    example="1",
     *    @OA\Schema(
     *       type="int",
     *       format="int64"
     *    )
     * ),
     * @OA\Response(
     *    response=200,
     *    description="",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Here is the list of assessment skill.")
     *        )
     *     )
     * )
     */
    public function assessmentSkillOfCategory(FilterAssessmentSkillRequest $request)
    {
        try {
            $assessment_skills = $this->advance_assessment_repository->assessmentSkillOfCategory($request->category_id);

            return prepare_response(200, true, __('messages.assessment_skill_list'), $assessment_skills);
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }

    /**
     * @OA\Get (
     * path="/assessment-skill-values-by-rating",
     * summary="Get assessment values's statement of rating",
     * description="Get assessment values's statement of rating",
     * operationId="assessment-values-by-rating",
     * tags={"Admin"},
     * security={{"apiAuth":{}}},
     * @OA\Parameter(
     *    description="Pass value id",
     *    in="query",
     *    name="value_id",
     *    required=true,
     *    example=1,
     *    @OA\Schema(
     *       type="integer",
     *       format="number"
     *    )
     * ),
     * @OA\Response(
     *    response=200,
     *    description="",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Here is the list of assessment statements.")
     *        )
     *     )
     * )
     */
    public function getAssessmentStatementsBySkill(AssessmentStatementsByRatingRequest $request)
    {
        try {
            $assessment_skills_statements = $this->advance_assessment_repository->getAssessmentStatementsBySkill($request->value_id);

            return prepare_response(200, true, __('messages.assessment_skill_statement_list'), $assessment_skills_statements);
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }

    /**
     * @OA\Post(
     * path="/manage-assessment-statement",
     * summary="Add or Update assessment statement",
     * description="Add or Update assessment statement",
     * operationId="manageAssessmentStatement",
     * tags={"Admin"},
     * security={{"apiAuth":{}}},
     * @OA\RequestBody(
     *    required=true,
     *    description="pass statement_id and statement if update then pass actual statement id and for new statement pass 0",
     *    @OA\JsonContent(
     *       required={"statement"},
     *       @OA\Property(property="statement", type="string", example="statement"),
     *       @OA\Property(property="statement_id", type="integer", example="0")
     *    ),
     * ),
     * @OA\Response(
     *    response=200,
     *    description="Wrong credentials response",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Statement has been saved.")
     *        )
     *     )
     * )
     */
    public function manageAssessmentStatement(ManageAssessmentRequest $request)
    {
        DB::beginTransaction();
        try {
            $this->advance_assessment_repository->manageAssessmentStatement($request->all());
            DB::commit();
            return prepare_response(200, true, __('messages.assessment_skill_statement_saved'));
        } catch (Exception $e) {
            DB::rollBack();
            return exceptionMessage($e);
        }
    }

    /**
     * @OA\Post(
     * path="/v2/submit-scouting-report",
     * summary="Submit player's scouting report",
     * description="submit report",
     * operationId="submitScoutingReportV2",
     * tags={"Scouts"},
     * security={{"apiAuth":{}}},
     * @OA\RequestBody(
     *    required=true,
     *    description="pass player_id, game, skills, longRangePotential, scoutComment, recommendation, published, scoutRequestId, modified_skills",
     *    @OA\JsonContent(
     *       required={"player_id", "game", "longRangePotential", "scoutComment", "recommendation", "scoutRequestId", "modified_skills"},
     *       @OA\Property(property="player_id", type="integer", example="1"),
     *       @OA\Property(property="scout_request_id", type="integer", example="1"),
     *       @OA\Property(property="game", type="string", example="hockey"),
     *       @OA\Property(property="skills", type="string", example="[]"),
     *       @OA\Property(property="modified_skills", type="string", example="[]"),
     *       @OA\Property(property="long_range_potential", type="string", example=""),
     *       @OA\Property(property="scout_comment", type="string", example=""),
     *       @OA\Property(property="recommendation", type="string", example=""),
     *       @OA\Property(property="published", type="bool", example="false")
     *    ),
     * ),
     * @OA\Response(
     *    response=200,
     *    description="Wrong credentials response",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Assessment report saved.")
     *        )
     *     )
     * )
     */
    public function submitScoutingReport(SubmitScoutingReportV2Request $request)
    {
        try {
            $report = $this->advance_assessment_repository->submitScoutingReport($request->all(), $request->header('Authorization'));

            return prepare_response(200, true, __('messages.assessment_report_saved'), $report);
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }

    /**
     * @OA\Post(
     * path="/one-time-evaluation-payment-page",
     * summary="One time payment page url",
     * description="One time payment page url",
     * operationId="generateOneTimeEvaluationPaymentPage",
     * tags={"Players"},
     * security={{"apiAuth":{}}},
     * @OA\RequestBody(
     *    required=true,
     *    description="Generate one payment page url",
     *    @OA\JsonContent(
     *       required={"league", "media_id"},
     *       @OA\Property(property="league", type="integer", example=1),
     *       @OA\Property(property="media_id", type="integer", example=1)
     *    ),
     * ),
     * @OA\Response(
     *    response=200,
     *    description="Wrong credentials response",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Here is one time payment page URL!")
     *        )
     *     )
     * )
     */
    public function generateOneTimeEvaluationPaymentPage(GenerateOneTimePaymentPageRequest $request)
    {
        DB::beginTransaction();
        try {
            $page_url = $this->advance_assessment_repository->generatePaymentPage($request->all(), $request->header('Authorization'));

            DB::commit();
            return prepare_response(200, true, __('messages.one_time_payment_page_url'), [
                'payment_page' => $page_url
            ]);
        } catch (Exception $e) {
            DB::rollBack();
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
            $plan = $this->advance_assessment_repository->saveSubscription($request->all());
            DB::commit();
            return view('thank-you', compact('plan'));
        } catch (Exception $e) {
            DB::rollBack();
            return exceptionMessage($e);
        }
    }

    /**
     * @OA\Post(
     * path="/one-time-payment-page",
     * summary="One time payment page url",
     * description="One time payment page url",
     * operationId="generateOneTimePaymentPage",
     * tags={"Players"},
     * security={{"apiAuth":{}}},
     * @OA\RequestBody(
     *    required=true,
     *    description="Generate one payment page url",
     *    @OA\JsonContent(
     *       required={"type"},
     *       @OA\Property(property="type", type="integer", example=1)
     *    ),
     * ),
     * @OA\Response(
     *    response=200,
     *    description="Wrong credentials response",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Here is one time payment page URL!")
     *        )
     *     )
     * )
     */
    public function generateOneTimePaymentPage(Request $request)
    {
        DB::beginTransaction();
        try {
            $page_url = $this->advance_assessment_repository->generatePaymentPage($request->all(), $request->header('Authorization'));

            DB::commit();
            return prepare_response(200, true, __('messages.one_time_payment_page_url'), [
                'payment_page' => $page_url
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return exceptionMessage($e);
        }
    }
}
