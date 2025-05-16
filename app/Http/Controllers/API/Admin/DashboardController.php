<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\SharedRepository;
use Exception;

/**
 *
 */
class DashboardController extends Controller
{

    /**
     * @var SharedRepository
     */
    private $sharedRepository;

    /**
     *
     */
    public function __construct()
    {
        $this->sharedRepository = new SharedRepository();
    }

    /**
     * @OA\Get (
     * path="/dashboard",
     * summary="dashboard",
     * description="dashboard",
     * operationId="dashboard",
     * tags={"Admin"},
     * security={{"apiAuth":{}}},
     * @OA\Response(
     *    response=200,
     *    description="",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Dashboard data has been retrieve")
     *        )
     *     )
     * )
     */
    public function dashboard()
    {
        try {
            $response = $this->sharedRepository->dashboardData();
            return prepare_response(200, true, __('messages.dashboard_data'), $response);
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }
}
