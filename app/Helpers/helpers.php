<?php

use App\Helpers\ZohoHelper;
use App\Models\City;
use App\Models\Country;
use App\Models\PrcAdvanceAssessmentValueStatement;
use App\Models\PrcAssessmentStatementLog;
use App\Models\PrcBlockUser;
use App\Models\PrcFollow;
use App\Models\PrcModule;
use App\Models\PrcSave;
use App\Models\PrcScoutRequest;
use App\Models\State;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

// if (!function_exists('prepare_response')) {
//     /**
//      * @param $code
//      * @param $status
//      * @param $message
//      * @param array $data
//      * @param array $extra_data
//      *
//      * @return JsonResponse
//      */
//     function prepare_response($code, $status, $message, $data = [], $extra_data = [], $version = "1.0")
//     {
//         if (!empty($extra_data)) {
//             $data = array_merge($data, $extra_data);
//         }

//         return response()->json([
//             'code'    => $code,
//             'status'  => $status,
//             'message' => $message,
//             'data'    => $data,
//             'version' => $version
//         ]);
//     }
// }

if (!function_exists('checkEmpty')) {
    /**
     * Check if given field is empty or not
     *
     * @param $data
     * @param string $field_name
     * @param null $default_response
     *
     * @return null
     */
    function checkEmpty($data, $field_name = '', $default_response = null)
    {
        if (gettype($data) == 'object') {
            $data = $data->toArray();
        }
        return !empty($data) && !empty($data[$field_name]) ? $data[$field_name] : $default_response;
    }
}

if (!function_exists('validate_admin_and_session_token')) {
    /**
     * @param $user_id
     * @param $token
     *
     * @return false
     */
    function validate_admin_and_session_token($user_id, $token)
    {
        $admin          = new User();
        $admin_response = $admin->where('id', '=', $user_id)
            ->where('branch_id', 0)
            ->first();

        if (!isset($admin_response)) {
            return false;
        }

        $session_token_expired_at = $admin_response->token_expiry;

        if (empty($session_token_expired_at) || !is_token_active($session_token_expired_at)) {
            return false;
        }

        return $admin_response;
    }
}

if (!function_exists('generateToken')) {
    /**
     * @return array|string|string[]|null
     */
    function generateToken()
    {
        $encrypted_string = Hash::make(rand() . time() . rand());
        $encrypted_string = str_replace(' ', '-', $encrypted_string); // Replaces all spaces with hyphens.
        // Removes special chars.
        return preg_replace('/[^A-Za-z0-9\-]/', '', $encrypted_string);
    }
}

if (!function_exists('checkUserAccess')) {
    /**
     * @param $token
     * @param $route
     * @return bool
     * @throws Exception
     */
    function checkUserAccess($token, $route)
    {
        $user = User::where('token', $token)->first();

        if (empty($user)) {
            return false;
        }

        if ($user->status != 'Active' && $user->status != 'Hidden') {
            throw new Exception(__('messages.account_block_or_in_active'), 200);
        }

        $module = PrcModule::with(['allowToTypes'])->where('api_route', $route)->first();

        if (empty($module) || array_search($user->type, array_column($module->allowToTypes->toArray(), 'id')) === FALSE) {
            throw new Exception(__('messages.not_rights_to_access'), 200);
        }

        return true;
    }
}

if (!function_exists('getUserInfo')) {
    /**
     * @param $where_value
     * @param string $where_key
     * @param bool $is_active_required
     * @param array $with
     * @return mixed
     */
    function getUserInfo($where_value, $where_key = "token", $is_active_required = true, $with = [])
    {
        $user = User::where($where_key, $where_value);
        array_push($with, 'player_league');
        array_push($with, 'player_position');
        array_push($with, 'team_managers');
        array_push($with, 'coaches');
        array_push($with, 'team_players');
        array_push($with, 'team_players.current_subscription');
        if (!empty($with)) {
            $user = $user->with($with);
        }

        if ($is_active_required) {
            $user = $user->whereIn('status', ['Active', 'Hidden']);
        }
        $user = $user->first();

        return $user;
    }
}

if (!function_exists('getUserIdAndType')) {
    /**
     * @param $where_value
     * @param string $where_key
     * @return mixed
     */
    function getUserIdAndType($where_value, $where_key = "token")
    {
        return User::where($where_key, $where_value)
            ->whereIn('status', ['Active', 'Hidden'])
            ->first(['id', 'type']);
    }
}


//fucntion for getting the type of user -- legacy
if (!function_exists('getUserType')) {
    /**
     * @param $type
     * @return string|void
     */
    function getUserType($type)
    {
        switch ($type) {
            case 1:
                return 'developer';
            case 2:
                return 'player';
            case 3:
                return 'evaluator';
            case 4:
                return 'team';
            case 7:
                return 'scout';
            case 6:
                return 'fan';
            case 5:
                return 'academy';
            case 8:
                return 'admin';
            case 9:
                return 'parent';
        }
    }
}

if (!function_exists('sendExceptionToSlack')) {
    /**
     * @param $e
     */
    function sendExceptionToSlack($e)
    {
        $notification_message = "\n*" . $e->getMessage() . "*\n\nFile:-> *" . $e->getFile() . "*\n\nLine:-> *" . $e->getLine() . "*\n\nTrace:-> " . $e->getTraceAsString();
        Log::critical($notification_message);
    }
}

if (!function_exists('checkUserFollower')) {
    /**
     * @param $user_id
     * @param $follower_id
     *
     * @return bool
     */
    function checkUserFollower($user_id, $follower_id)
    {
        $is_follower = PrcFollow::where('user_id', $user_id)->where(
            'followers',
            'like',
            '%"' . $follower_id . '"%'
        )->count();

        return !!$is_follower;
    }
}

if (!function_exists('checkUserFollowing')) {
    /**
     * @param $user_id
     * @param $following_id
     *
     * @return bool
     */
    function checkUserFollowing($user_id, $following_id)
    {
        $following_user = PrcFollow::where('user_id', $user_id)->first(['following']);
        $following      = false;
        if (!empty($following_user) && !empty($following_user->following)) {
            $followings = json_decode($following_user->following);

            if (in_array($following_id, $followings)) {
                $following = true;
            }
        }
        return $following;
    }
}

if (!function_exists('checkUserBlocked')) {
    /**
     * @param $user_id
     * @param $blocked_id
     *
     * @return bool
     */
    function checkUserBlocked($user_id, $blocked_id)
    {
        $blocked_users = PrcBlockUser::where('user_id', $user_id)->first(['blocked_users']);

        $is_blocked = false;
        if (!empty($blocked_users) && !empty($blocked_users->blocked_users)) {
            $blocked_users = json_decode($blocked_users->blocked_users);

            if (!empty($blocked_users) && in_array($blocked_id, $blocked_users)) {
                $is_blocked = true;
            }
        }
        return $is_blocked;
    }
}

if (!function_exists('checkUserSave')) {
    /**
     * @param $user_id
     * @param $save_id
     *
     * @return bool
     */
    function checkUserSave($user_id, $save_id)
    {
        $saved_users = PrcSave::where('user_id', $user_id)->first();

        $is_saved = false;

        if (!empty($saved_users) && !empty($saved_users->players)) {
            $save_users = json_decode($saved_users->players);

            if (in_array($save_id, $save_users)) {
                $is_saved = true;
            }
        }

        return $is_saved;
    }
}

if (!function_exists('followersCount')) {
    /**
     * @param $player_id
     *
     * @return int
     */
    function followersCount($player_id)
    {
        $follower_users = PrcFollow::where('user_id', $player_id)->first();

        $total_followers = 0;

        if (!empty($follower_users) && !empty($follower_users->followers)) {
            $follower_users = json_decode($follower_users->followers);

            foreach ($follower_users as $follower_user) {
                $user_details = getUserIdAndType($follower_user, 'id');

                if (empty($user_details)) {
                    continue;
                }
                $total_followers++;
            }
        }

        return $total_followers;
    }
}

if (!function_exists('followingCount')) {
    /**
     * @param $player_id
     *
     * @return int
     */
    function followingCount($player_id)
    {
        $following_users = PrcFollow::where('user_id', $player_id)->first();

        $total_followings = 0;

        if (!empty($following_users) && !empty($following_users->following)) {
            $following_users = json_decode($following_users->following);

            foreach ($following_users as $following_user) {
                $user_details = getUserIdAndType($following_user, 'id');

                if (empty($user_details)) {
                    continue;
                }
                $total_followings++;
            }
        }

        return $total_followings;
    }
}

if (!function_exists('checkScoutingRequestStatus')) {
    /**
     * @param $user_id
     *
     * @return int[]
     */
    function checkScoutingRequestStatus($user_id)
    {
        $scouting_request = PrcScoutRequest::where('source_user_id', $user_id)
            ->orderBy('id', 'desc')
            ->first();

        $request_status = [
            'status'     => 0,
            'request_id' => 0
        ];

        if (!empty($scouting_request)) {
            $request_status['status']     = $scouting_request->status;
            $request_status['request_id'] = $scouting_request->id;
        }

        return $request_status;
    }
}

if (!function_exists('exceptionMessage')) {
    /**
     * @param $e
     *
     * @return JsonResponse
     */
    function exceptionMessage($e)
    {
        if ($e->getCode() == 200) {
            return prepare_response(200, false, $e->getMessage());
        }
        sendExceptionToSlack($e);
        if (env('APP_DEBUG')) {
            return prepare_response(500, false, $e->getMessage() . " " . $e->getFile() . " " . $e->getLine());
        }

        return prepare_response(500, false, 'Something went wrong. Please try again');
    }
}

    if (!function_exists('checkReportSaved')) {
        /**
         * @param $report_id
         * @param $user_id
         *
         * @return bool
         */
        function checkReportSaved($report_id, $user_id)
        {
            $report = PrcSave::where('user_id', $user_id)->where('reports', 'like', '%' . $report_id . '%')->first();

            if (empty($report)) {
                return false;
            }
            return true;
        }
    }

if (!function_exists('checkEmpty')) {
    /**
     * @param $array
     * @param $key
     * @param $default_value
     * @return mixed
     */
    function checkEmpty($array, $key, $default_value)
    {
        if (empty($array[$key])) {
            return $default_value;
        }
        return $array[$key];
    }
}

if (!function_exists('validateCanSendMessage')) {
    /**
     * @param $player_id
     * @param $user_id
     * @return bool
     */
    function validateCanSendMessage($user_id, $player_id)
    {
        $can_send_message = true;

        if (checkUserBlocked($user_id, $player_id)) {
            $can_send_message = false;
        }

        if (checkUserBlocked($player_id, $user_id)) {
            $can_send_message = false;
        }

        return $can_send_message;
    }
}

if (!function_exists('createUserObject')) {
    /**
     * @param $user
     * @param int $user_id
     * @return mixed
     */
    function createUserObject($user, $user_id = 0)
    {
        $user->follower         = checkUserFollower($user_id, $user->id);
        $user->following        = checkUserFollowing($user_id, $user->id);
        $user->blocked          = checkUserBlocked($user_id, $user->id);
        $user->saved            = checkUserSave($user_id, $user->id);
        $user->type             = (gettype($user->type) == 'integer') ? getUserType($user->type) : $user->type;
        $user->followers_count  = followersCount($user->id);
        $user->following_count  = followingCount($user->id);
        $check_request          = checkScoutingRequestStatus($user_id);
        $user->request_status   = $check_request['status'];
        $user->request_id       = $check_request['request_id'];
        $user->league           = checkEmpty($user->player_league, 'league_name', '');
        $user->can_send_message = validateCanSendMessage($user_id, $user->id);

        return $user->toArray();
    }
}

if (!function_exists('createUserObjectPaginate')) {
    /**
     * @param $user
     * @param int $user_id
     * @return mixed
     */
    function createUserObjectPaginate($user, $user_id = 0)
    {
        $user->city = getCity($user->city_id);
        $user->state = getState($user->state_id);
        $user->country = getCountry($user->country_id);
        $user->saved = checkUserSave($user_id, $user->id);
        $user->type  = (gettype($user->type) == 'integer') ? getUserType($user->type) : $user->type;

        return $user->toArray();
    }
}

if (!function_exists('getAssessmentStatementId')) {
    /**
     * @param $player_id
     * @param $assessment_value_id
     * @return int
     */
    function getAssessmentStatementId($player_id, $assessment_value_id)
    {
        $statement_id             = 0;
        $assessment_statement_log = PrcAssessmentStatementLog::where('player_id', $player_id)
            ->where('assessment_value_id', $assessment_value_id)
            ->first();

        if (!empty($assessment_statement_log)) {
            $statement_id = $assessment_statement_log->statement_id;
        }

        $assessment_value_statement = PrcAdvanceAssessmentValueStatement::where('assessment_value_id', $assessment_value_id);
        $count                      = $assessment_value_statement->get()->count();

        if ($count == 0) {
            return 0;
        }

        if ($count == 1) {
            STATEMENT:
            $assessment_value_statement = $assessment_value_statement->first();
            return $assessment_value_statement->id;
        }

        if ($statement_id > 0) {
            $assessment_value_statement = $assessment_value_statement->where('id', '>', $statement_id);
        }

        $assessment_value_statement = $assessment_value_statement->first();

        if (empty($assessment_value_statement)) {
            goto STATEMENT;
        }

        return $assessment_value_statement->id;
    }
}

if (!function_exists('createZohoClassObject')) {
    /**
     * @return ZohoHelper
     */
    function createZohoClassObject()
    {
        return new ZohoHelper();
    }
}

if (!function_exists('getCity')) {
    /**
     * @param $user_id
     * @param $save_id
     *
     * @return bool
     */
    function getCity($city_id)
    {
        $city = City::where('id', $city_id)->first();

        if (empty($city)) {
            return null;
        }

        $city = $city->city_name;

        return $city;
    }
}

if (!function_exists('getState')) {
    /**
     * @param $user_id
     * @param $save_id
     *
     * @return bool
     */
    function getState($state_id)
    {
        $state = State::where('id', $state_id)->first();

        if (empty($state)) {
            return null;
        }

        $state = $state->state_name;

        return $state;
    }
}

if (!function_exists('getCountry')) {
    /**
     * @param $user_id
     * @param $save_id
     *
     * @return bool
     */
    function getCountry($country_id)
    {
        $country = Country::where('id', $country_id)->first();

        if (empty($country)) {
            return null;
        }

        $country = $country->country_name;

        return $country;
    }
}

if (!function_exists('getCountryShortName')) {
    /**
     * @param $user_id
     * @param $save_id
     *
     * @return bool
     */
    function getCountryShortName($country_id)
    {
        $country = Country::where('id', $country_id)->first();

        if (empty($country)) {
            return '';
        }

        $country = $country->short_name_2_digit;

        return $country;
    }
}

if (!function_exists('createEvaluationObject')) {
    /**
     * @param $user
     * @param int $user_id
     * @return mixed
     */
    function createEvaluationObject($evaluation)
    {
        $evaluation->first_name = $evaluation->player ? $evaluation->player->first_name : "";
        $evaluation->last_name = $evaluation->player ? $evaluation->player->last_name : "";
        $evaluation->s3_profile_picture = $evaluation->player ? $evaluation->player->s3_profile_picture : null;
        $evaluation->city_id = $evaluation->player?->city_id;
        $evaluation->state_id = $evaluation->player?->state_id;
        $evaluation->country_id = $evaluation->player?->country_id;
        $evaluation->city = $evaluation->player ? getCity($evaluation->player->city_id) : null;
        $evaluation->state = $evaluation->player ? getState($evaluation->player->state_id) : null;
        $evaluation->country = $evaluation->player ? getCountry($evaluation->player->country_id) : null;
        $evaluation->position_id = $evaluation->player ? $evaluation->player->player_position->id : null;
        $evaluation->position = $evaluation->player ? $evaluation->player->player_position->position_name : null;
        $evaluation->saved = checkUserSave($evaluation->player->id, $evaluation->player->id);
        $evaluation->scout_request_status = $evaluation->status;
        $evaluation->media_id = $evaluation->media?->id;
        $evaluation->media_path = $evaluation->media?->media_path;
        $evaluation->playable_id = $evaluation?->playable?->id;
        $evaluation->playable_title = $evaluation?->playable?->title;
        $evaluation->type  = $evaluation->player ? getUserType($evaluation->player?->type) : 'player';
        $evaluation->evaluated = $evaluation->player?->evaluated;
        if (!empty($evaluation->report)) {
            $evaluation->scout_request_status = $evaluation->report->published ? 6 : 5;
        }

        unset($evaluation->one_time_subscription_id);
        unset($evaluation->scout_user_id);
        unset($evaluation->playable);
        unset($evaluation->player);
        unset($evaluation->media);
        unset($evaluation->rejected_by);
        unset($evaluation->league_id);
        unset($evaluation->status);
        unset($evaluation->report);

        return $evaluation->toArray();
    }
}

if (!function_exists('createReportObject')) {
    /**
     * @param $user
     * @param int $user_id
     * @return mixed
     */
    function createReportObject($report)
    {

        $report->first_name = $report->player ? $report->player->first_name : "";
        $report->last_name = $report->player ? $report->player->last_name : "";
        $report->s3_profile_picture = $report->player ? $report->player->s3_profile_picture : null;
        $report->city_id = $report->player?->city_id;
        $report->state_id = $report->player?->state_id;
        $report->country_id = $report->player?->country_id;
        $report->city = $report->player ? getCity($report->player->city_id) : null;
        $report->state = $report->player ? getState($report->player->state_id) : null;
        $report->country = $report->player ? getCountry($report->player->country_id) : null;
        $report->position_id = $report->player ? $report->player->player_position->id : null;
        $report->position = $report->player ? $report->player->player_position->position_name : null;
        $report->saved = $report->player ? checkUserSave($report->player->id, $report->player->id) : null;
        $report->type  = $report->player ? getUserType($report->player?->type) : 'player';
        $report->evaluated = $report->player?->evaluated;


        unset($report->media_id);
        unset($report->modified_skills);
        unset($report->long_range_potential);
        unset($report->scout_user_id);
        unset($report->skills);
        unset($report->game);
        unset($report->playable);
        unset($report->player);
        unset($report->rating);
        unset($report->scout_comment);
        unset($report->recommendation);

        return $report->toArray();
    }
}

if (!function_exists('createReportDetailObject')) {
    /**
     * @param $user
     * @param int $user_id
     * @return mixed
     */
    function createReportDetailObject($report)
    {

        $report->first_name = $report->player ? $report->player->first_name : "";
        $report->last_name = $report->player ? $report->player->last_name : "";
        $report->s3_profile_picture = $report->player ? $report->player->s3_profile_picture : null;
        $report->city_id = $report->player?->city_id;
        $report->state_id = $report->player?->state_id;
        $report->country_id = $report->player?->country_id;
        $report->city = $report->player ? getCity($report->player->city_id) : null;
        $report->state = $report->player ? getState($report->player->state_id) : null;
        $report->country = $report->player ? getCountry($report->player->country_id) : null;
        $report->position_id = $report->player ? $report->player->player_position->id : null;
        $report->position = $report->player ? $report->player->player_position->position_name : null;
        $report->user_saved = $report->player ? checkUserSave($report->player->id, $report->player->id) : null;
        $report->scout_request_status = $report->scout_request?->status;
        $report->media_id = $report->scout_request?->media?->id;
        $report->media_path = $report->scout_request?->media?->media_path;
        $report->playable_id = $report->scout_request?->playable?->id;
        $report->playable_title = $report->scout_request?->playable?->title;
        $report->type  = $report->player ? getUserType($report->player?->type) : 'player';
        $report->evaluated = $report->player?->evaluated;

        unset($report->modified_skills);
        unset($report->long_range_potential);
        unset($report->scout_user_id);
        unset($report->game);
        unset($report->playable);
        unset($report->player);
        unset($report->scout);
        unset($report->scout_request);

        return $report->toArray();
    }
}

if (!function_exists('getEnvironment')) {
    /**
     * Get the current environment (development, staging, production)
     * Uses ENVIRONMENT variable with fallback to APP_ENV
     *
     * @param string $default Default environment if none is set
     * @return string
     */
    function getEnvironment($default = 'production')
    {
        return env('ENVIRONMENT', env('APP_ENV', $default));
    }
}

if (!function_exists('isDevelopment')) {
    /**
     * Check if current environment is development
     *
     * @return bool
     */
    function isDevelopment()
    {
        return getEnvironment() === 'development';
    }
}

if (!function_exists('isStaging')) {
    /**
     * Check if current environment is staging
     *
     * @return bool
     */
    function isStaging()
    {
        return getEnvironment() === 'staging';
    }
}

if (!function_exists('isProduction')) {
    /**
     * Check if current environment is production
     *
     * @return bool
     */
    function isProduction()
    {
        return getEnvironment() === 'production';
    }
}
