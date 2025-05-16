<?php

namespace App\Http\Controllers\API\V3;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\SubmitScoutingReportV3Request;
use App\Repositories\V2\AdvanceAssessmentRepository;
use Exception;
use Illuminate\Support\Facades\DB;

/**
 *
 */
class V3AdvanceAssessmentController extends Controller
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
     * @OA\Post(
     * path="/v3/submit-scouting-report",
     * summary="Submit player's scouting report",
     * description="submit report",
     * operationId="submitScoutingReportV3",
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
     *       @OA\Property(property="skills", type="string", example=""),
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
    public function submitScoutingReport(SubmitScoutingReportV3Request $request)
    {
        DB::beginTransaction();
        try {
            $report = $this->advance_assessment_repository->submitScoutingReport($request->all(), $request->header('Authorization'));

            DB::commit();
            return prepare_response(200, true, __('messages.assessment_report_saved'), $report);
        } catch (Exception $e) {
            DB::rollBack();
            return exceptionMessage($e);
        }
    }
}
