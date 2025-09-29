<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\EvaluationRejectionReason;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Class EvaluationRejectionReasonController
 * @package App\Http\Controllers\API
 */
class EvaluationRejectionReasonController extends Controller
{
    /**
     * @OA\Get(
     * path="/evaluation-rejection-reasons/active",
     * summary="Get Active Evaluation Rejection Reasons",
     * description="Retrieve all active rejection reasons that evaluators can select",
     * operationId="getActiveEvaluationRejectionReasons",
     * tags={"Evaluation"},
     * security={{"apiAuth":{}}},
     * @OA\Response(
     *    response=200,
     *    description="Active rejection reasons retrieved successfully",
     *    @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="Active rejection reasons retrieved successfully"),
     *       @OA\Property(property="data", type="array",
     *          @OA\Items(
     *              @OA\Property(property="id", type="integer", example=1),
     *              @OA\Property(property="title", type="string", example="Insufficient Skill Level"),
     *              @OA\Property(property="description", type="string", example="Player does not demonstrate the required skill level"),
     *              @OA\Property(property="active", type="boolean", example=true),
     *              @OA\Property(property="sort_order", type="integer", example=1),
     *              @OA\Property(property="meta", type="object", example={"severity": "high", "category": "technical"})
     *          )
     *       )
     *    )
     * ),
     * @OA\Response(
     *    response=500,
     *    description="Internal server error",
     *    @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example=false),
     *       @OA\Property(property="message", type="string", example="Something went wrong")
     *    )
     * )
     * )
     */
    public function getActiveReasons(Request $request): JsonResponse
    {
        try {
            $activeReasons = EvaluationRejectionReason::active()->get();

            return prepare_response(
                true,
                'Active rejection reasons retrieved successfully',
                $activeReasons
            );
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }

    /**
     * @OA\Get(
     * path="/evaluation-rejection-reasons",
     * summary="Get All Evaluation Rejection Reasons",
     * description="Retrieve all rejection reasons (admin only)",
     * operationId="getAllEvaluationRejectionReasons",
     * tags={"Evaluation"},
     * security={{"apiAuth":{}}},
     * @OA\Response(
     *    response=200,
     *    description="All rejection reasons retrieved successfully",
     *    @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="All rejection reasons retrieved successfully"),
     *       @OA\Property(property="data", type="array",
     *          @OA\Items(
     *              @OA\Property(property="id", type="integer", example=1),
     *              @OA\Property(property="title", type="string", example="Insufficient Skill Level"),
     *              @OA\Property(property="description", type="string", example="Player does not demonstrate the required skill level"),
     *              @OA\Property(property="active", type="boolean", example=true),
     *              @OA\Property(property="sort_order", type="integer", example=1),
     *              @OA\Property(property="meta", type="object", example={"severity": "high", "category": "technical"})
     *          )
     *       )
     *    )
     * )
     * )
     */
    public function getAllReasons(Request $request): JsonResponse
    {
        try {
            $allReasons = EvaluationRejectionReason::orderBy('sort_order')->get();

            return prepare_response(
                200,
                true,
                'All rejection reasons retrieved successfully',
                $allReasons
            );
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }
}
