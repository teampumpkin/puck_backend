<?php

namespace App\Repositories;

use App\Mail\NotifyEvaluator;
use App\Mail\NotifyEvaluatorAboutCancelRequest;
use App\Mail\PlayerNotifyAboutNewEvaluationRequestMail;
use App\Mail\PuckNotifyNewEvaluationRequest;
use App\Models\PrcBlockUser;
use App\Models\PrcFollow;
use App\Models\PrcLeague;
use App\Models\PrcMedia;
use App\Models\PrcPosition;
use App\Models\PrcSave;
use App\Models\PrcScoutRequest;
use App\Models\PrcTeamMember;
use App\Models\User;
use Aws\S3\S3Client;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

/**
 * Class PlayerRepository
 * @package App\Repositories
 */
class PlayerRepository
{
    /**
     * @var User
     */
    private $player;

    /**
     * @var PrcMedia
     */
    private $prc_media;

    /**
     * @var PrcScoutRequest
     */
    private $prc_scout_request;

    /**
     * PlayerRepository constructor.
     */
    public function __construct()
    {
        $this->player            = new User();
        $this->prc_media         = new PrcMedia();
        $this->prc_scout_request = new PrcScoutRequest();
    }

    /**
     * @return mixed
     */
    public function getTopPlayer($token)
    {
        $user = getUserIdAndType($token);

        $top_players = $this->player->with(['player_league'])->where('type', 2)
            ->where('status', 'Active')
            ->orderBy('rating_count', 'DESC')
            ->limit(50)
            ->get();

        $top_players->makeHidden(['token', 'password_reset_pin', 'status', 'setting']);
        $top_players_data = [];

        if (!empty($top_players)) {
            foreach ($top_players as $top_player) {
                $top_players_data[] = createUserObject($top_player, $user->id);
            }
        }

        return $top_players_data;
    }

    /**
     * @return mixed
     */
    public function getAllPlayers($token, $data = [])
    {
        $user    = getUserIdAndType($token);
        $players = $this->player->with(['player_league', 'player_position'])->withCount(['nPurchases'])->whereNotIn('type', [1, 3, 8])->where('status', 'Active');
        $order = 'ASC';

        if ($user->type !== 8) {
            $players = $players->whereIn('type', [2, 4, 5, 6, 7, 9]);
        }

        if (!empty($data['order_by'])) {
            $order = $data['order_by'];
        }

        if (!empty($data)) {
            if (!empty($data['year'])) {
                $players = $players->where('dob', 'like', $data['year'] . '%');
            }

            if (!empty($data['league'])) {
                $players = $players->where('league', $data['league']);
            }

            if (!empty($data['team'])) {
                $players = $players->where('team_id', $data['team']);
            }

            if (!empty($data['sort_by'])) {
                switch (strtolower($data['sort_by'])) {
                    case "id":
                        $players = $players->orderBy('id', $order);
                        break;
                    case "created":
                        $players = $players->orderBy('created_at', $order);
                        break;
                    case "player":
                        $players = $players->orderBy('first_name', $order)->orderBy('last_name', $order);
                        break;
                    case "dob":
                        $players = $players->orderBy('dob', $order);
                        break;
                    case "position":
                        $players = $players->orderBy(PrcPosition::select('position_name')->whereRaw('prc_positions.id = CAST(prc_users.position AS Bigint)'), $order);
                        break;
                    case "team":
                        $players = $players->orderByRaw('type = ?', [4])->orderBy('first_name', $order)->orderBy('last_name', $order);
                        break;
                    case "league":
                        $players = $players->orderBy(PrcLeague::select('league_name')->whereColumn('prc_leagues.id', 'prc_users.league'), $order);
                        break;
                    case "scouts":
                        $players = $players->orderByRaw('type = ?', [7])->orderBy('first_name', $order)->orderBy('last_name', $order);
                        break;
                    case "coaches":
                        $players = $players->orderBy(PrcTeamMember::select('first_name')->whereColumn('prc_team_members.user_id', 'prc_users.id'), $order);
                        break;
                    default:
                        break;
                }
            } else {
                $players = $players->orderBy('first_name', $order)->orderBy('last_name', $order);
            }
        }

        $players = $players->limit(50)->get();

        if ($user->type == 1 || $user->type == 8) {
            $players->makeVisible(['status']);

            return $players;
        }

        $all_players = [];

        if (!empty($players)) {
            foreach ($players as $player) {
                $all_players[] = createUserObject($player, $user->id);
            }
        }

        return $all_players;
    }

    /**
     * @param $id
     * @param $token
     *
     * @return string
     * @throws Exception
     */
    public function saveUnSaveUser($id, $token)
    {
        $user = getUserInfo($id, 'id');

        if (empty($user)) {
            throw new Exception("User is not active.", 200);
        }

        $current_user = getUserInfo($token);

        $prc_save = PrcSave::where('user_id', $current_user->id)->first();

        $players = [(string)$id];

        $user_name = ($user->type === 3) ? 'Evaluator ' . $user->id : $user->first_name . " " . $user->last_name;

        $return_message = str_replace('%username%', $user_name, __('messages.added_in_favourite_list'));

        if (empty($prc_save)) {
            PrcSave::create([
                'user_id' => $current_user->id,
                'players' => (!empty($players)) ? json_encode($players) : "",
                'reports' => ""
            ]);
        } else {
            if (!empty($players)) {
                $prc_saved_players = (!empty($prc_save->players)) ? json_decode($prc_save->players) : [];

                if (!empty($prc_saved_players) && in_array($players[0], $prc_saved_players)) {
                    $prc_saved_players = array_flip($prc_saved_players);
                    unset($prc_saved_players[$players[0]]);
                    $prc_saved_players = array_values(array_map('strval', array_flip($prc_saved_players)));

                    $return_message = str_replace('%username%', $user_name, __('messages.remove_from_favourite_list'));
                } else {
                    $prc_saved_players = array_merge($prc_saved_players, $players);
                }

                $prc_save->players = (empty($prc_saved_players)) ? "" : json_encode($prc_saved_players);
                $prc_save->save();
            }
        }

        return $return_message;
    }

    /**
     * @param $id
     * @param $token
     *
     * @return array
     */
    public function followUnFollowUser($id, $token)
    {
        $current_user = getUserInfo($token);

        $prc_current_follow_obj = PrcFollow::where('user_id', $current_user->id)->first();
        $prc_user_follow_obj    = PrcFollow::where('user_id', $id)->first();

        $follow_user                = getUserInfo($id, 'id');
        $user_name                  = ($follow_user->type === 3) ? 'Evaluator ' . $follow_user->id : $follow_user->first_name . " " . $follow_user->last_name;
        $return_response['message'] = str_replace('%username%', $user_name, __('messages.start_following'));

        if (empty($prc_current_follow_obj)) {
            PrcFollow::create([
                'user_id'   => $current_user->id,
                'following' => json_encode([(string)$id]),
                'followers' => ""
            ]);
        } else {
            $prc_following = (!empty($prc_current_follow_obj->following)) ? json_decode(
                $prc_current_follow_obj->following,
                true
            ) : [];

            if (!empty($prc_following) && in_array($id, $prc_following)) {
                $prc_following = array_flip($prc_following);
                unset($prc_following[$id]);
                $prc_following = array_values(array_map('strval', array_flip($prc_following)));

                $return_response['message'] = str_replace('%username%', $user_name, __('messages.stop_following'));
            } else {
                $prc_following = array_merge($prc_following, [(string)$id]);
            }
            $prc_current_follow_obj->following = (empty($prc_following)) ? "" : json_encode($prc_following);
            $prc_current_follow_obj->save();
        }

        if (empty($prc_user_follow_obj)) {
            PrcFollow::create([
                'user_id'   => $id,
                'followers' => json_encode([(string)$current_user->id]),
                'following' => ""
            ]);
        } else {
            $prc_followers = (!empty($prc_user_follow_obj->followers)) ? json_decode(
                $prc_user_follow_obj->followers,
                true
            ) : [];

            if (!empty($prc_followers) && in_array($current_user->id, $prc_followers)) {
                $prc_followers = array_flip($prc_followers);
                unset($prc_followers[$current_user->id]);
                $prc_followers = array_values(array_map('strval', array_flip($prc_followers)));
            } else {
                $prc_followers = array_merge($prc_followers, [(string)$current_user->id]);
            }

            $prc_user_follow_obj->followers = (empty($prc_followers)) ? "" : json_encode($prc_followers);
            $prc_user_follow_obj->save();
        }
        $return_response['follower_count'] = followersCount($follow_user->id);
        return $return_response;
    }

    /**
     * @param $id
     * @param $token
     *
     * @return string
     */
    public function blockUnBlockUser($id, $token)
    {
        $current_user = getUserInfo($token);

        $prc_block_user = PrcBlockUser::where('user_id', $current_user->id)->first();
        $block_user     = getUserInfo($id, 'id');
        $user_name      = ($block_user->type === 3) ? 'Evaluator ' . $block_user->id : $block_user->first_name . " " . $block_user->last_name;
        $return_message = str_replace('%username%', $user_name, __('messages.block_user'));

        if (empty($prc_block_user)) {
            PrcBlockUser::create([
                'user_id'       => $current_user->id,
                'blocked_users' => json_encode([(string)$id])
            ]);
        } else {
            $prc_blocked_users = (!empty($prc_block_user->blocked_users)) ? json_decode(
                $prc_block_user->blocked_users,
                true
            ) : [];

            if (!empty($prc_blocked_users) && in_array($id, $prc_blocked_users)) {
                $prc_blocked_users = array_flip($prc_blocked_users);
                unset($prc_blocked_users[$id]);
                $prc_blocked_users = array_values(array_map('strval', array_flip($prc_blocked_users)));

                $return_message = str_replace('%username%', $user_name, __('messages.unblock_user'));;
            } else {
                $prc_blocked_users = array_merge($prc_blocked_users, [(string)$id]);
            }

            $prc_block_user->blocked_users = (empty($prc_blocked_users)) ? "" : json_encode($prc_blocked_users);
            $prc_block_user->save();
        }

        return $return_message;
    }

    /**
     * @param $token
     * @param int $league
     * @throws Exception
     */
    public function sendScoutRequest($token, $data = [])
    {
        $user = getUserInfo($token);

        // $evaluator = $this->player->inRandomOrder()->where('type', 3)->where('status', 'Active')->first();
        $evaluator = $this->player->where('email', 'hans-dev+eval@allcode.com')->first();

        $this->prc_scout_request->create([
            'source_user_id' => $user->id,
            'scout_user_id'  => $evaluator->id,
            'media_id' => $data['media_id'],
            'league_id' => $user->league,
            'one_time_subscription_id' => $data['payment_id'],
            'playable_id' => $data['playable_id'],
            'status'         => 1
        ]);

        $mail_data = [
            'evaluator_first_name' => $evaluator->first_name,
            'evaluator_last_name'  => $evaluator->last_name,
            'player_first_name'    => $user->first_name,
            'player_last_name'     => $user->last_name,
        ];

        $details = [
            'name' => $user->first_name . ' ' . $user->last_name,
        ];

        try {
            Mail::to($user->email)->send(new PlayerNotifyAboutNewEvaluationRequestMail($details));
        } catch (Exception $e) {
            Log::info("Something went wrong in sending PlayerNotifyAboutNewEvaluationRequestMail to email -> " . $user->email);
        }

        $puck_email = (env('SUPPORT_EMAIL') !== null && env('SUPPORT_EMAIL') !== '') ? env('SUPPORT_EMAIL') : 'support@puckrecruiter.com';

        try {
            Mail::to($puck_email)->send(new PuckNotifyNewEvaluationRequest());
        } catch (Exception $e) {
            Log::info("Something went wrong in sending PuckNotifyNewEvaluationRequest to email -> " . $user->email);
        }

        // Mail::to($evaluator->email)->send(new NotifyEvaluator($mail_data));
    }

    /**
     * @param $request_id
     * @throws Exception
     */
    public function cancelScoutRequest($request_id)
    {
        $scout_request = PrcScoutRequest::where('id', $request_id)
            ->first();

        if (!empty($scout_request)) {
            if ($scout_request->status != 1) {
                throw new Exception(__('messages.can_not_cancel_assessment_request'), 200);
            }
            $scout_request->status = 4;
            $scout_request->save();
        }

        $user      = getUserInfo($scout_request->source_user_id, 'id');
        $evaluator = getUserInfo($scout_request->scout_user_id, 'id');

        $mail_data = [
            'evaluator_first_name' => $evaluator->first_name,
            'evaluator_last_name'  => $evaluator->last_name,
            'player_first_name'    => $user->first_name,
            'player_last_name'     => $user->last_name,
        ];

        // Mail::to($evaluator->email)->send(new NotifyEvaluatorAboutCancelRequest($mail_data));
    }

    /**
     * @param $user_id
     *
     * @return array
     */
    public function getFollowers($user_id)
    {
        $user_followers = PrcFollow::where('user_id', $user_id)->first();

        $follower_data = [];

        if (!empty($user_followers) && !empty($user_followers->followers)) {
            $followers = json_decode($user_followers->followers);

            foreach ($followers as $follower) {
                $follower_user = getUserInfo($follower, 'id');
                if (empty($follower_user)) {
                    continue;
                }
                $follower_data[] = createUserObject($follower_user, $user_id);
            }
        }

        return $follower_data;
    }

    /**
     * @param $user_id
     *
     * @return array
     */
    public function getFollowings($user_id)
    {
        $user_following = PrcFollow::where('user_id', $user_id)->first();

        $following_data = [];

        if (!empty($user_following) && !empty($user_following->following)) {
            $followings = json_decode($user_following->following);

            foreach ($followings as $following) {
                $following_user = getUserInfo($following, 'id');
                if (empty($following_user)) {
                    continue;
                }
                $following_data[] = createUserObject($following_user, $user_id);
            }
        }

        return $following_data;
    }

    /**
     * @param $token
     * @param int $user_id
     * @return mixed
     * @throws Exception
     */
    public function playerMedias($user_id)
    {
        $medias = PrcMedia::where('user_id', $user_id)->get(['id', 'name', 'media_path', 'created_at']);

        if ($medias->isEmpty()) {
            throw new Exception(__('messages.no_media_uploaded'), 200);
        }

        return $medias;
    }

    /**
     * @param $token
     * @param $data
     */
    public function playerMediaUpload($token, $data)
    {
        $player = getUserInfo($token);

        $name = isset($data['name']) ? $data['name'] : null;

        $filePath = env("PLAYER_MEDIA_FOLDER") . uniqid(time()) . $data['file_extension'];

        $s3 = new S3Client([
            'version' => 'latest',
            'region'  => env('AWS_DEFAULT_REGION')
        ]);

        $command = $s3->getCommand('PutObject', [
            'Bucket' => env('AWS_BUCKET'),
            'Key' => $filePath  // file path in s3 bucket where file will be upload
        ]);

        $time = 1440;

        if (env('LINK_TIME') !== null && env('LINK_TIME') !== '') {
            $time = env('LINK_TIME');
        }

        $request = $s3->createPresignedRequest($command, '+' . $time . ' minutes');

        // Get the actual presigned-url
        $presignedUrl = (string)$request->getUri();

        $this->prc_media->create([
            'user_id'     => $player->id,
            'name' => $name,
            'media_path'  => $filePath,
            'uploaded_at' => Carbon::now()
        ]);

        return $presignedUrl;
    }

    /**
     * @param $token
     * @param $data
     */
    public function playerMediaEdit($token, $data)
    {
        $player = getUserInfo($token);

        $media = $this->prc_media->where('id', $data['media_id'])->where('user_id', $player->id)->first();

        if (empty($media)) {
            return $media;
        }

        $media->name = $data['name'];

        $media->save();

        return $media;
    }

    /**
     * @param $media_id
     * @param $token
     * @throws Exception
     */
    public function playerMediaDownload($token, $media_id)
    {
        // $user = getUserIdAndType($token);
        $prc_media = $this->prc_media->where('id', $media_id);

        if (empty($prc_media->first())) {
            throw new Exception(__('messages.invalid_media_id'), 200);
        }

        /* if ($prc_media->first()->user_id != $user->id) {
            throw new Exception(__('messages.can_not_download_another_player_media'), 200);
        } */

        $s3 = new S3Client([
            'version' => 'latest',
            'region'  => env('AWS_DEFAULT_REGION')
        ]);

        $command = $s3->getCommand('GetObject', [
            'Bucket' => env('AWS_BUCKET'),
            'Key' => $prc_media->first()->media_path  // file path in s3 bucket where file is located
        ]);

        $time = 1440;

        if (env('LINK_TIME') !== null && env('LINK_TIME') !== '') {
            $time = env('LINK_TIME');
        }

        $request = $s3->createPresignedRequest($command, '+' . $time . ' minutes');

        // Get the actual presigned-url
        $presignedUrl = (string)$request->getUri();

        return $presignedUrl;
    }

    /**
     * @param $media_id
     * @param $token
     * @throws Exception
     */
    public function removeMedia($media_id, $token)
    {
        $user = getUserIdAndType($token);

        $prc_media = $this->prc_media->where('id', $media_id)->where('user_id', $user->id);

        if (empty($prc_media->first())) {
            throw new Exception(__('messages.invalid_media_id'), 200);
        }

        if ($prc_media->first()->user_id != $user->id) {
            throw new Exception(__('messages.can_not_remove_another_player_media'), 200);
        }

        $media = $prc_media->first();

        Storage::disk('s3')->delete($media->media_path);

        $prc_media->delete();
    }

    public function mentorshipPlanPrice()
    {
        return [
            'on_board_price'     => MENTORSHIP_ON_BOARD_PRICE,
            'subscription_price' => MENTORSHIP_SUBSCRIPTION_PRICE,
        ];
    }

    public function notification_preferences($token)
    {
        $user = getUserInfo($token);

        if (empty($user->notification_preferences)) {
            $user->notification_preferences = ['push_notifications' => ['followers', 'messages']];
            $user->save();
        }

        $preferences = [
            'pause' => in_array('pause', $user->notification_preferences['push_notifications']) ? true : false,
            'followers' => in_array('followers', $user->notification_preferences['push_notifications']) ? true : false,
            'messages' => in_array('messages', $user->notification_preferences['push_notifications']) ? true : false,
        ];

        return $preferences;
    }

    public function update_notification_preferences($data = [], $token)
    {
        $user = getUserInfo($token);

        if (empty($user->notification_preferences)) {
            $user->notification_preferences = ['push_notifications' => ['followers', 'messages']];
            $user->save();
        }

        $preferences = [];

        if (!empty($data['notification'])) {
            switch ($data['notification']) {
                case 'pause':
                    if (!in_array($data['notification'], $user->notification_preferences['push_notifications'])) {
                        $user->notification_preferences = ['push_notifications' => ['pause']];
                    } else {
                        $user->notification_preferences = ['push_notifications' => ['']];
                    }
                    break;
                default:
                    $newArray = $user->notification_preferences['push_notifications'];
                    if (in_array('pause', $user->notification_preferences['push_notifications'])) {
                        $key = array_search('pause', $newArray);
                        unset($newArray[$key]);
                    }
                    if (in_array($data['notification'], $user->notification_preferences['push_notifications'])) {
                        $key = array_search($data['notification'], $newArray);
                        unset($newArray[$key]);
                        $user->notification_preferences = ['push_notifications' => $newArray];
                    } else {
                        array_push($newArray, $data['notification']);
                        $user->notification_preferences = ['push_notifications' => $newArray];
                    }
                    break;
            }
        }


        $user->save();

        $preferences = [
            'pause' => in_array('pause', $user->notification_preferences['push_notifications']) ? true : false,
            'followers' => in_array('followers', $user->notification_preferences['push_notifications']) ? true : false,
            'messages' => in_array('messages', $user->notification_preferences['push_notifications']) ? true : false,
        ];

        return $preferences;
    }
}
