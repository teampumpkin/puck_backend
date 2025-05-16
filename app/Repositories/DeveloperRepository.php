<?php

namespace App\Repositories;

use App\Models\APILog;
use App\Models\PrcModule;
use App\Models\PrcUserType;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 *
 */
class DeveloperRepository
{
    /**
     * @var PrcModule
     */
    private $prc_module;

    /**
     * @var PrcUserType
     */
    private $prc_user_type;
    /**
     * @var APILog
     */
    private $api_log;

    /**
     *
     */
    public function __construct()
    {
        $this->prc_module    = new PrcModule();
        $this->prc_user_type = new PrcUserType();
        $this->api_log       = new APILog();
    }

    /**
     * @param false $is_active_required
     * @return mixed
     * @throws Exception
     */
    public function getModules($data = [], $is_active_required = false)
    {
        $modules = $this->prc_module->with(['allowToTypes']);

        if ($is_active_required) {
            $modules = $modules->where('status', 1);
        }

        $modules = $modules->orderBy('id')->get();

        if (empty($modules->toArray())) {
            throw new Exception(__('messages.no_module_available'), 200);
        }

        if (!empty($data)) {
            $selected_ids = [];
            foreach ($modules as $allowToType) {
                $allowToType->is_selected = 0;
                if ($allowToType->allowToTypes->contains($data['type_id'])) {
                    $allowToType->is_selected = 1;
                    $selected_ids[]           = $allowToType->id;
                }
            }

            return [
                'modules'      => $modules,
                'selected_ids' => $selected_ids
            ];
        }

        return $modules;
    }

    /**
     * @param false $is_active_required
     * @return Builder[]|Collection
     * @throws Exception
     */
    public function getAllUserTypes($is_active_required = false)
    {
        $user_types = $this->prc_user_type->with(['allowModules', 'parentType']);

        if ($is_active_required) {
            $user_types = $user_types->where('status', 1);
        }

        $user_types = $user_types->orderBy('id')->get();

        if (empty($user_types->toArray())) {
            throw new Exception(__('messages.no_user_type_available'), 200);
        }

        return $user_types;
    }

    /**
     * @param $data
     */
    public function updatePermission($data)
    {
        $module = $this->prc_user_type->where('id', $data['type_id'])->first();

        $module->allowModules()->sync($data['permissions']);
    }

    /**
     * @param $data
     */
    public function addNewModule($data)
    {
        $this->prc_module->create([
            'module_name' => $data['module_name'],
            'api_route'   => $data['api_route'],
        ]);
    }

    /**
     * @param $page
     * @return array
     */
    public function getAPILogs($page)
    {
        $limit  = 15;
        $offset = $page * $limit;

        $api_logs = $this->api_log->orderBy('id', 'desc');

        $total_rows = 100;

        $api_logs = $api_logs
            ->skip($offset)
            ->take($limit)
            ->get();

        foreach ($api_logs as $key => $api_log) {
            $api_logs[ $key ]['request']  = json_decode($api_log->request);
            $api_logs[ $key ]['response'] = json_decode($api_log->response);
        }

        $api_log_data['total_rows']   = $total_rows;
        $api_log_data['api_log_data'] = $api_logs;

        return $api_log_data;
    }

    /**
     * @param $days
     * @return void
     */
    public function removeLogs($days = 0)
    {
        $this->api_log->truncate();
    }
}
