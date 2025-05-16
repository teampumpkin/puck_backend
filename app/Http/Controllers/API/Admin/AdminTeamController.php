<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\TeamStatusChangeRequest;
use App\Repositories\TeamRepository;
use Exception;
use Illuminate\Support\Facades\DB;

/**
 *
 */
class AdminTeamController extends Controller
{
    /**
     * @var TeamRepository
     */
    private $team_repository;

    /**
     *
     */
    public function __construct()
    {
        $this->team_repository = new TeamRepository();
    }

    /**
     * @OA\Get(
     * path="/get-all-teams",
     * summary="Get All Teams",
     * description="Get team list",
     * operationId="getTeamsAdmin",
     * tags={"Admin"},
     * security={{"apiAuth":{}}},
     * @OA\Response(
     *    response=200,
     *    description="",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Team list have been retrieve successfully.")
     *        )
     *     )
     * )
     */
    public function getTeams()
    {
        try {
            $teams = $this->team_repository->getTeams(true);

            return prepare_response(200, true, __('messages.team_list'), $teams);
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }

    /**
     * @OA\Post(
     * path="/team-status-change",
     * summary="team Status Change",
     * description="team Status Change",
     * operationId="teamStatusChange",
     * tags={"Admin"},
     * security={{"apiAuth":{}}},
     * @OA\RequestBody(
     *    required=true,
     *    description="Status and Team Id",
     *    @OA\JsonContent(
     *       required={"team_id", "status"},
     *       @OA\Property(property="team_id", type="integer", example="1"),
     *       @OA\Property(property="status", type="string", example="Active")
     *    ),
     * ),
     * @OA\Response(
     *    response=200,
     *    description="success",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Team status updated.")
     *        )
     *     )
     * )
     */
    public function teamStatusChange(TeamStatusChangeRequest $request)
    {
        DB::beginTransaction();
        try {
            $response = $this->team_repository->teamStatusChange($request->all());
            DB::commit();
            return prepare_response(200, $response['status'], $response['message']);
        } catch (Exception $e) {
            DB::rollBack();
            return exceptionMessage($e);
        }
    }

    /**
     * @OA\Get(
     * path="/get-all-academies",
     * summary="Get All Academies",
     * description="Get team list",
     * operationId="getAcademies",
     * tags={"Admin"},
     * security={{"apiAuth":{}}},
     * @OA\Response(
     *    response=200,
     *    description="",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Academy list have been retrieve successfully.")
     *        )
     *     )
     * )
     */
    public function getAcademies()
    {
        try {
            $academies = $this->team_repository->getTeams(true, '', 5);

            return prepare_response(200, true, __('messages.academy_list'), $academies);
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }
}
