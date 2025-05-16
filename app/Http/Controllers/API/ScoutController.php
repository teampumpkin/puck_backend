<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\PublishScoutingReportRequest;
use App\Http\Requests\API\ScoutReportRequest;
use App\Http\Requests\API\ScoutRequestUpdateRequest;
use App\Http\Requests\API\SkillRequest;
use App\Http\Requests\API\SubmitScoutingReportRequest;
use App\Repositories\ScoutRepository;
use Exception;
use Illuminate\Http\Request;

/**
 * Class ScoutController
 * @package App\Http\Controllers\API
 */
class ScoutController extends Controller
{
    /**
     * @var ScoutRepository
     */
    private $scoutRepository;

    /**
     *
     */
    public function __construct()
    {
        $this->scoutRepository = new ScoutRepository();
    }

    /**
     * @OA\Get (
     * path="/get-scout-requests",
     * summary="Get Scouting request",
     * description="Retrieve Scouting request",
     * operationId="getScoutRequest",
     * tags={"Scouts"},
     * security={{"apiAuth":{}}},
     * @OA\Response(
     *    response=200,
     *    description="",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Here is the assessment request list")
     *        )
     *     )
     * )
     */
    public function getScoutRequest(Request $request)
    {
        try {
            $scout_requests = $this->scoutRepository->getScoutRequest($request->header('Authorization'), $request->all());

            if (empty($scout_requests)) {
                return prepare_response(200, false, __('messages.no_assessment_request'));
            }

            return prepare_response(200, true, __('messages.assessment_request_list'), $scout_requests);
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }

    public function updateScoutRequest(Request $request)
    {
        try {
            $scout_request = $this->scoutRepository->updateScoutRequest($request->header('Authorization'), $request->all());

            if (empty($scout_request)) {
                return prepare_response(404, false, __('Evaluation not found'));
            }

            return prepare_response(200, true, __('Evaluation updated'), $scout_request);
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }

    /**
     * @OA\Post(
     * path="/request-status-update",
     * summary="Approve/reject scouting request",
     * description="status update",
     * operationId="requestStatusUpdate",
     * tags={"Scouts"},
     * security={{"apiAuth":{}}},
     * @OA\RequestBody(
     *    required=true,
     *    description="request_id and status",
     *    @OA\JsonContent(
     *       required={"request_id", "status"},
     *       @OA\Property(property="request_id", type="integer", example="1"),
     *       @OA\Property(property="status", type="string", example="1")
     *    ),
     * ),
     * @OA\Response(
     *    response=200,
     *    description="Wrong credentials response",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Assessment request status modified.")
     *        )
     *     )
     * )
     */
    public function requestStatusUpdate(ScoutRequestUpdateRequest $request)
    {
        try {
            $this->scoutRepository->requestStatusUpdate($request->all(), $request->header('Authorization'));

            return prepare_response(200, true, __('messages.assessment_request_status_changed'));
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }

    /**
     * @OA\Get (
     * path="/get-skills",
     * summary="Get skills for scouting",
     * description="Retrieve Scouting skills",
     * operationId="getSkills",
     * tags={"Scouts"},
     * security={{"apiAuth":{}}},
     * @OA\Parameter(
     *    description="Player Id",
     *    in="query",
     *    name="player_id",
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
     *       @OA\Property(property="message", type="string", example="Here is the skills for the assessment")
     *        )
     *     )
     * )
     */
    public function getSkills(SkillRequest $request)
    {
        try {
            $skills = $this->scoutRepository->getSkills($request->player_id);

            return prepare_response(200, true, __('messages.assessment_skill_list'), $skills);
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }

    /**
     * @OA\Post(
     * path="/submit-scouting-report",
     * summary="Submit player's scouting report",
     * description="submit report",
     * operationId="submitScoutingReport",
     * tags={"Scouts"},
     * security={{"apiAuth":{}}},
     * @OA\RequestBody(
     *    required=true,
     *    description="pass player_id, game, skills, longRangePotential, scoutComment, recommendation, published, scoutRequestId",
     *    @OA\JsonContent(
     *       required={"player_id", "game", "longRangePotential", "scoutComment", "recommendation", "scoutRequestId"},
     *       @OA\Property(property="player_id", type="integer", example="1"),
     *       @OA\Property(property="scout_request_id", type="integer", example="1"),
     *       @OA\Property(property="game", type="string", example="hockey"),
     *       @OA\Property(property="skills", type="json", example="{}"),
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
    public function submitScoutingReport(SubmitScoutingReportRequest $request)
    {
        try {
            $report = $this->scoutRepository->submitScoutingReport($request->all(), $request->header('Authorization'));

            return prepare_response(200, true, __('messages.assessment_report_saved'), $report);
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }

    /**
     * @OA\Post(
     * path="/publish-scouting-report",
     * summary="Publish player's scouting report",
     * description="publish report",
     * operationId="publishScoutingReport",
     * tags={"Scouts"},
     * security={{"apiAuth":{}}},
     * @OA\RequestBody(
     *    required=true,
     *    description="pass player_id, game, skills, longRangePotential, scoutComment, recommendation, published, scoutRequestId",
     *    @OA\JsonContent(
     *       required={"report_id", "published"},
     *       @OA\Property(property="report_id", type="integer", example="1"),
     *       @OA\Property(property="published", type="bool", example="false")
     *    ),
     * ),
     * @OA\Response(
     *    response=200,
     *    description="Wrong credentials response",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Assessment report published.")
     *        )
     *     )
     * )
     */
    public function publishScoutingReport(PublishScoutingReportRequest $request)
    {
        try {
            $this->scoutRepository->publishScoutingReport($request->all());

            return prepare_response(200, true, __('messages.assessment_report_published'));
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }

    /**
     * @OA\Get (
     * path="/get-scout-report",
     * summary="Get scouting report",
     * description="Retrieve Scouting report",
     * operationId="getScoutReport",
     * tags={"Scouts"},
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
     *       @OA\Property(property="message", type="string", example="Here is the assessment report generated by you")
     *        )
     *     )
     * )
     */
    public function getScoutReport(ScoutReportRequest $request)
    {
        try {
            $report = $this->scoutRepository->getScoutReport($request->header('Authorization'), $request->report_id);

            return prepare_response(200, true, __('messages.assessment_report_detail_for_evaluator'), $report);
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }
}
