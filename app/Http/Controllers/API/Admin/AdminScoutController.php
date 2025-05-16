<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\CovertToEvaluatorRequest;
use App\Http\Requests\API\ScoutStatusChangeRequest;
use App\Repositories\ScoutRepository;
use Exception;
use Illuminate\Support\Facades\DB;

/**
 *
 */
class AdminScoutController extends Controller
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
     * @OA\Post(
     * path="/scout-status-change",
     * summary="scout Status Change",
     * description="scout Status Change",
     * operationId="scoutStatusChange",
     * tags={"Admin"},
     * security={{"apiAuth":{}}},
     * @OA\RequestBody(
     *    required=true,
     *    description="Status and Scout Id",
     *    @OA\JsonContent(
     *       required={"scout_id", "status"},
     *       @OA\Property(property="scout_id", type="integer", example="1"),
     *       @OA\Property(property="status", type="string", example="Active")
     *    ),
     * ),
     * @OA\Response(
     *    response=200,
     *    description="Wrong credentials response",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Password reset successfully.")
     *        )
     *     )
     * )
     */
    public function scoutStatusChange(ScoutStatusChangeRequest $request)
    {
        DB::beginTransaction();
        try {
            $response = $this->scoutRepository->scoutStatusChange($request->all());
            DB::commit();
            return prepare_response(200, $response['status'], $response['message']);
        } catch (Exception $e) {
            DB::rollBack();
            return exceptionMessage($e);
        }
    }

    /**
     * @OA\Get (
     * path="/get-all-scouts",
     * summary="Get All Scouts",
     * description="Retrieve All Scouts",
     * operationId="getAllScouts",
     * tags={"Admin"},
     * security={{"apiAuth":{}}},
     * @OA\Response(
     *    response=200,
     *    description="",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Scout list retrieved.")
     *        )
     *     )
     * )
     */
    public function getAllScouts()
    {
        try {
            $scouts = $this->scoutRepository->getAllScouts();

            return prepare_response(200, true, __('messages.scout_list'), $scouts);
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }

    /**
     * @OA\Post(
     * path="/convert-to-evaluator",
     * summary="Convert scout into evaluator",
     * description="Convert scout into evaluator",
     * operationId="convertToEvaluator",
     * tags={"Admin"},
     * security={{"apiAuth":{}}},
     * @OA\RequestBody(
     *    required=true,
     *    description="Pass scout id",
     *    @OA\JsonContent(
     *       required={"scout_id"},
     *       @OA\Property(property="scout_id", type="integer", example="1")
     *    ),
     * ),
     * @OA\Response(
     *    response=200,
     *    description="Wrong credentials response",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Scout has been converted to evaluator")
     *        )
     *     )
     * )
     */
    public function convertToEvaluator(CovertToEvaluatorRequest $request)
    {
        DB::beginTransaction();
        try {
            $this->scoutRepository->convertToEvaluator($request->all());
            DB::commit();
            return prepare_response(200, true, __('scout_convert_into_evaluator'));
        } catch (Exception $e) {
            DB::rollBack();
            return exceptionMessage($e);
        }
    }
}
