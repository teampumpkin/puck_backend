<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\AddPositionRequest;
use App\Http\Requests\API\UpdateOneToOneCallStatusRequest;
use App\Repositories\SharedRepository;
use Exception;
use Illuminate\Http\Request;

/**
 *
 */
class AdminPlayerController extends Controller
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
     * @OA\Get (
     * path="/positions",
     * summary="Get player positions for the admin",
     * description="Get player positions for the admin",
     * operationId="positions",
     * tags={"Admin"},
     * security={{"apiAuth":{}}},
     * @OA\Response(
     *    response=200,
     *    description="",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Position list has been retrieve")
     *        )
     *     )
     * )
     */
    public function getPlayerPositions()
    {
        try {
            $positions = $this->shared_repository->getPositions(true);

            return prepare_response(200, true, __('messages.position_list'), $positions);
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }

    /**
     * @OA\Post(
     * path="/add-position",
     * summary="Create new Player position",
     * description="Create new Player position",
     * operationId="addPosition",
     * tags={"Admin"},
     * security={{"apiAuth":{}}},
     * @OA\RequestBody(
     *    required=true,
     *    description="Pass position_name and short name",
     *    @OA\JsonContent(
     *       required={"position_name, short_name"},
     *       @OA\Property(property="position_name", type="text", example="Left Wing"),
     *       @OA\Property(property="short_name", type="text", example="LW")
     *    ),
     * ),
     * @OA\Response(
     *    response=200,
     *    description="Wrong credentials response",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="New position has been created.")
     *        )
     *     )
     * )
     */
    public function addPosition(AddPositionRequest $request)
    {
        try {
            $this->shared_repository->addPosition($request->all());

            return prepare_response(200, true, __('messages.new_position_created'));
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }

    /**
     * @OA\Get (
     * path="/one-on-one-call-request",
     * summary="Retrieve one on one call request list",
     * description="Retrieve one on one call request list",
     * operationId="oneOnOneCallRequest",
     * tags={"Admin"},
     * security={{"apiAuth":{}}},
     * @OA\Response(
     *    response=200,
     *    description="",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="One on on call reqeust has been retrieve successfully")
     *        )
     *     )
     * )
     */
    public function oneOnOneCallRequest(Request $request)
    {
        try {
            $oneOnOneCallRequests = $this->shared_repository->oneOnOneCallRequest($request->get('page', 0));
            return prepare_response(200, true, __('messages.one_on_on_call_request_list'), $oneOnOneCallRequests);
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }

    /**
     * @OA\Post(
     * path="/call-request-status-change",
     * summary="Status change of call request",
     * description="Status change of call request",
     * operationId="callRequestStatusChange",
     * tags={"Admin"},
     * security={{"apiAuth":{}}},
     * @OA\RequestBody(
     *    required=true,
     *    description="Pass call request id and status",
     *    @OA\JsonContent(
     *       required={"call_request_id", "status"},
     *       @OA\Property(property="call_request_id", type="integer", example="1"),
     *       @OA\Property(property="status", type="string", example="Link Send"),
     *    ),
     * ),
     * @OA\Response(
     *    response=200,
     *    description="Wrong credentials response",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Call request status has been updated")
     *        )
     *     )
     * )
     */
    public function updateOneOnOneRequestStatus(UpdateOneToOneCallStatusRequest $request)
    {
        try {
            $this->shared_repository->updateOneOnOneRequestStatus($request->all());
            return prepare_response(200, true, __('messages.call_request_update'));
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }
}
