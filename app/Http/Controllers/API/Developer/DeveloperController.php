<?php

namespace App\Http\Controllers\API\Developer;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\AddModuleRequest;
use App\Repositories\DeveloperRepository;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 *
 */
class DeveloperController extends Controller
{
    /**
     * @var DeveloperRepository
     */
    private $developer_repository;

    /**
     *
     */
    public function __construct()
    {
        $this->developer_repository = new DeveloperRepository();
    }

    /**
     * @return JsonResponse
     */
    public function getAllModules(Request $request)
    {
        try {
            $modules = $this->developer_repository->getModules($request->all());

            return prepare_response(200, true, __('messages.module_list'), $modules);
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }

    /**
     * @return JsonResponse
     */
    public function getAllUserTypes()
    {
        try {
            $user_types = $this->developer_repository->getAllUserTypes();

            return prepare_response(200, true, __('messages.user_type_list'), $user_types);
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function updatePermission(Request $request)
    {
        DB::beginTransaction();
        try {
            $this->developer_repository->updatePermission($request->all());
            DB::commit();
            return prepare_response(200, true, __('messages.user_permission_update'));
        } catch (Exception $e) {
            DB::rollBack();
            return exceptionMessage($e);
        }
    }

    /**
     * @param AddModuleRequest $request
     * @return JsonResponse
     */
    public function addNewModule(AddModuleRequest $request)
    {
        DB::beginTransaction();
        try {
            $this->developer_repository->addNewModule($request->all());
            DB::commit();
            return prepare_response(200, true, __('messages.new_module_added'));
        } catch (Exception $e) {
            DB::rollBack();
            return exceptionMessage($e);
        }
    }

    public function getAPILogs(Request $request)
    {
        try {
            $api_logs = $this->developer_repository->getAPILogs($request->get('page', 0));

            return prepare_response(200, true, __('messages.api_logs'), $api_logs);
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }

    public function removeLogs(Request $request)
    {
        try {
            $days = $request->get('days', 0);

            $this->developer_repository->removeLogs($days);
            $message = 'API logs have been removed successfully';

            if ($days > 0) {
                $message = __('messages.remove_api_logs');
            }
            return prepare_response(200, true, $message);
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }
}
