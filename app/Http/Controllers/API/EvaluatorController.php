<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Repositories\EvaluatorRepository;
use Exception;

/**
 * Class EvaluatorController
 * @package App\Http\Controllers\API
 */
class EvaluatorController extends Controller
{
    /**
     * @var EvaluatorRepository
     */
    private $evaluatorRepository;

    /**
     *
     */
    public function __construct()
    {
        $this->evaluatorRepository = new EvaluatorRepository();
    }

    /**
     * @OA\Get (
     * path="/get-all-evaluators",
     * summary="Get All evaluators",
     * description="Retrieve All evaluators",
     * operationId="getAllEvaluators",
     * tags={"Admin"},
     * security={{"apiAuth":{}}},
     * @OA\Response(
     *    response=200,
     *    description="",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Evaluator list retrieved.")
     *        )
     *     )
     * )
     */
    public function getAllEvaluators()
    {
        try {
            $evaluators = $this->evaluatorRepository->getAllEvaluators();

            return prepare_response(200, true, __('messages.evaluator_list_success'), $evaluators);
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }
}
