<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\EvaluatorStatusChangeRequest;
use App\Repositories\EvaluatorRepository;
use Exception;
use Illuminate\Support\Facades\DB;

/**
 *
 */
class AdminEvaluatorController extends Controller
{
    /**
     * @var EvaluatorRepository
     */
    private $evaluator_repository;

    /**
     *
     */
    public function __construct()
    {
        $this->evaluator_repository = new EvaluatorRepository();
    }

    /**
     * @OA\Post(
     * path="/evaluator-status-change",
     * summary="evaluator Status Change",
     * description="evaluator Status Change",
     * operationId="evaluatorStatusChange",
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
     *       @OA\Property(property="message", type="string", example="Password reset successfully.")
     *        )
     *     )
     * )
     */
    public function evaluatorStatusChange(EvaluatorStatusChangeRequest $request)
    {
        DB::beginTransaction();
        try {
            $response = $this->evaluator_repository->evaluatorStatusChange($request->all());
            DB::commit();
            return prepare_response(200, $response['status'], $response['message']);
        } catch (Exception $e) {
            DB::rollBack();
            return exceptionMessage($e);
        }
    }
}
