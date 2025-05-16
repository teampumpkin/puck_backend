<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\AddCoachRequest;
use App\Http\Requests\API\AddMemberRequest;
use App\Http\Requests\API\AddPlayerRequest;
use App\Http\Requests\API\EditTeamMemberRequest;
use App\Http\Requests\API\GetTeamRequest;
use App\Http\Requests\API\RemoveTeamMemberRequest;
use App\Repositories\TeamRepository;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TeamController extends Controller
{
    private $team_repository;

    public function __construct()
    {
        $this->team_repository = new TeamRepository();
    }

    /**
     * @OA\Get(
     * path="/team-list",
     * summary="Get All Teams",
     * description="Get team list",
     * operationId="getTeams",
     * tags={"Common"},
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
    public function getTeams(Request $request)
    {
        try {
            $teams = $this->team_repository->getTeams(false, $request->header('Authorization'));

            return prepare_response(200, true, __('messages.team_list'), $teams);
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }

    /**
     * @OA\Get(
     * path="/team",
     * summary="Team Detail",
     * description="Get Team details based on team_id",
     * operationId="getTeam",
     * tags={"Common"},
     * security={{"apiAuth":{}}},
     * @OA\Parameter(
     *    description="Pass team id",
     *    in="query",
     *    name="team_id",
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
     *       @OA\Property(property="message", type="string", example="Team detail has been retrieve successfully")
     *        )
     *     )
     * )
     */
    public function getTeam(GetTeamRequest $request)
    {
        try {
            $team = $this->team_repository->getTeam($request->team_id, $request->header('Authorization'));

            return prepare_response(200, true, __('messages.team_details'), $team);
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }

    /**
     * @OA\Post(
     * path="/add-manager",
     * summary="Add Manager in team",
     * description="Add manager in team",
     * operationId="addManager",
     * tags={"Teams"},
     * security={{"apiAuth":{}}},
     * @OA\RequestBody(
     *    required=true,
     *    description="Pass Manager details",
     *    @OA\JsonContent(
     *       required={"first_name","last_name","email"},
     *       @OA\Property(property="first_name", type="string", example="Test"),
     *       @OA\Property(property="last_name", type="string", example="User"),
     *       @OA\Property(property="email", type="string", format="email", example="user1@mail.com"),
     *       @OA\Property(property="profile_picture", type="string", example="base64 string")
     *    ),
     * ),
     * @OA\Response(
     *    response=422,
     *    description="",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Manager has been added successfully.")
     *        )
     *     )
     * )
     */
    public function addManager(AddMemberRequest $request)
    {
        DB::beginTransaction();
        try {
            $this->team_repository->addManager($request->all(), $request->header('Authorization'));
            DB::commit();
            return prepare_response(200, true, __('messages.team_manager_added'));
        } catch (Exception $e) {
            DB::rollBack();
            return exceptionMessage($e);
        }
    }

    /**
     * @OA\Put(
     * path="/edit-team-member",
     * summary="Edit member of team",
     * description="Edit member of team",
     * operationId="editTeamMember",
     * tags={"Teams"},
     * security={{"apiAuth":{}}},
     * @OA\RequestBody(
     *    required=true,
     *    description="Pass Member details",
     *    @OA\JsonContent(
     *       required={"member_id","first_name","last_name"},
     *       @OA\Property(property="member_id", type="integer", example=1),
     *       @OA\Property(property="first_name", type="string", example="User"),
     *       @OA\Property(property="last_name", type="string", example="PRC"),
     *       @OA\Property(property="profile_picture", type="string", example="Base64 string")
     *    ),
     * ),
     * @OA\Response(
     *    response=422,
     *    description="",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Team member update successfully.")
     *        )
     *     )
     * )
     */
    public function editTeamMember(EditTeamMemberRequest $request)
    {
        DB::beginTransaction();
        try {
            $this->team_repository->editTeamMember($request->all());
            DB::commit();
            return prepare_response(200, true, __('messages.team_member_update'));
        } catch (Exception $e) {
            DB::rollBack();
            return exceptionMessage($e);
        }
    }

    /**
     * @OA\Delete (
     * path="/remove-team-member",
     * summary="team member delete",
     * description="remove team member",
     * operationId="removeTeamMember",
     * tags={"Teams"},
     * security={{"apiAuth":{}}},
     * @OA\Parameter(
     *    description="member id",
     *    in="query",
     *    name="member_id",
     *    required=true,
     *    example="1",
     *    @OA\Schema(
     *       type="number",
     *       format="integer"
     *    )
     * ),
     * @OA\Response(
     *    response=200,
     *    description="Wrong credentials response",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="The member has been removed from the team.")
     *        )
     *     )
     * )
     */
    public function removeTeamMember(RemoveTeamMemberRequest $request)
    {
        try {
            $this->team_repository->removeTeamMember($request->member_id);

            return prepare_response(200, true, __('messages.team_member_remove'));
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }

    /**
     * @OA\Post(
     * path="/add-coach",
     * summary="Add Coach in team",
     * description="Add coach in team",
     * operationId="addCoach",
     * tags={"Teams"},
     * security={{"apiAuth":{}}},
     * @OA\RequestBody(
     *    required=true,
     *    description="Pass Coach details",
     *    @OA\JsonContent(
     *       required={"first_name","last_name","email"},
     *       @OA\Property(property="first_name", type="string", example="Test"),
     *       @OA\Property(property="last_name", type="string", example="User"),
     *       @OA\Property(property="email", type="string", format="email", example="user1@mail.com"),
     *       @OA\Property(property="profile_picture", type="string", example="base64 string")
     *    ),
     * ),
     * @OA\Response(
     *    response=422,
     *    description="",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Coach has been added successfully.")
     *        )
     *     )
     * )
     */
    public function addCoach(AddCoachRequest $request)
    {
        DB::beginTransaction();
        try {
            $this->team_repository->addCoach($request->all(), $request->header('Authorization'));
            DB::commit();
            return prepare_response(200, true, __('messages.team_coach_added'));
        } catch (Exception $e) {
            DB::rollBack();
            return exceptionMessage($e);
        }
    }

    /**
     * @OA\Post(
     * path="/add-player",
     * summary="Add Player in team",
     * description="Add player in team",
     * operationId="addPlayer",
     * tags={"Teams"},
     * security={{"apiAuth":{}}},
     * @OA\RequestBody(
     *    required=true,
     *    description="Pass Player details",
     *    @OA\JsonContent(
     *       required={"first_name","last_name","email"},
     *       @OA\Property(property="first_name", type="string", example="Test"),
     *       @OA\Property(property="last_name", type="string", example="User"),
     *       @OA\Property(property="email", type="string", format="email", example="user1@mail.com"),
     *       @OA\Property(property="profile_picture", type="string", example="base64 string")
     *    ),
     * ),
     * @OA\Response(
     *    response=422,
     *    description="",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Player has been added successfully.")
     *        )
     *     )
     * )
     */
    public function addPlayer(AddPlayerRequest $request)
    {
        DB::beginTransaction();
        try {
            $this->team_repository->addPlayer($request->all(), $request->header('Authorization'));
            DB::commit();
            return prepare_response(200, true, __('messages.team_player_added'));
        } catch (Exception $e) {
            DB::rollBack();
            return exceptionMessage($e);
        }
    }
}
