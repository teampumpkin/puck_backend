<?php

namespace App\Repositories;

use App\Mail\NotifyEvaluator;
use App\Mail\PlayerNotifyAboutEvaluationRequestAcceptedMail;
use App\Mail\PlayerNotifyAboutEvaluationRequestCompletedMail;
use App\Models\PrcAssessmentStatementLog;
use App\Models\PrcReport;
use App\Models\PrcScoutRequest;
use App\Models\PrcSkill;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

/**
 * Class ScoutRepository
 * @package App\Repositories
 */
class ScoutRepository
{
    /**
     * @var User
     */
    private $scout;
    /**
     * @var PrcScoutRequest
     */
    private $prc_scout_request;

    /**
     * @var PrcSkill
     */
    private $prc_skill;

    /**
     * @var PrcReport
     */
    private $prc_report;
    /**
     * @var PrcAssessmentStatementLog
     */
    private $prc_assessment_statement_log;

    /**
     * PlayerRepository constructor.
     */
    public function __construct()
    {
        $this->scout                        = new User();
        $this->prc_scout_request            = new PrcScoutRequest();
        $this->prc_skill                    = new PrcSkill();
        $this->prc_report                   = new PrcReport();
        $this->prc_assessment_statement_log = new PrcAssessmentStatementLog();
    }

    /**
     * @param $token
     *
     * @return array
     */
    public function getScoutRequest($token, $request = [])
    {
        $user = getUserIdAndType($token);

        if (($user->type === 1 || $user->type === 8) && !empty($request['admin'])) {
            $scouting_requests = $this->prc_scout_request->with(['player', 'evaluator', 'playable', 'report', 'player.player_league', 'player.player_position', 'media'])->orderBy('id', 'DESC')->get();
        }else{
            $scouting_requests = $this->prc_scout_request->with(['player', 'report', 'player.player_league', 'player.player_position', 'media'])->where('scout_user_id',
            $user->id)->orderBy('id', 'DESC')->get();
        }

        $request_data = [];

        if (!empty($scouting_requests)) {
            foreach ($scouting_requests as $key => $scouting_request) {
                if ($user->type === 1 || $user->type === 8) {
                    $request_data[ $key ]['player'] = $scouting_request->player;
                    $request_data[ $key ]['evaluator'] = $scouting_request->evaluator;
                    $request_data[ $key ]['playable'] = $scouting_request->playable;
                    $request_data[ $key ]['evaluator_id'] = $scouting_request->scout_user_id;
                }else{
                    $request_data[ $key ] = createUserObject($scouting_request->player, $user->id);
                }
                $request_data[ $key ]['scout_request_id'] = $scouting_request->id;
                $report_id                                = 0;
                if (empty($scouting_request->report)) {
                    $request_status = $scouting_request->status;
                } else {
                    if ($scouting_request->report->published) {
                        $request_status = 6;
                    } else {
                        $request_status = 5;
                    }
                    $report_id = $scouting_request->report->id;
                }
                $request_data[ $key ]['scout_request_status'] = $request_status;
                $request_data[ $key ]['created_at']           = $scouting_request->created_at;
                $request_data[ $key ]['updated_at']           = $scouting_request->updated_at;
                $request_data[ $key ]['scout_report_id']      = $report_id;
                $request_data[ $key ]['media_id']             = (!empty($scouting_request->media)) ? $scouting_request->media->id : 0;
            }
        }
        return $request_data;
    }

    /**
     * @param $data
     *
     * @return array
     */
    public function updateScoutRequest($token, $data)
    {
        $user = getUserIdAndType($token);

        $request_data = $this->prc_scout_request->with('player')->where('id', $data['id'])->first();

        $request_data->scout_user_id = $data['evaluator_id'];

        $request_data->save();

        $evaluator = $this->scout->where('id', $data['evaluator_id'])->first();

        $mail_data = [
            'evaluator_first_name' => $evaluator->first_name,
            'evaluator_last_name'  => $evaluator->last_name
        ];
        
        try{
            Mail::to($evaluator->email)->send(new NotifyEvaluator($mail_data));
        } catch (Exception $e) {
            Log::info("Something went wrong in sending NotifyEvaluator email to email -> " . $user->email);
        }

        return $request_data;
    }

    /**
     * @param $data
     *
     * @return array
     */
    public function scoutStatusChange($data)
    {
        $scout = $this->scout->where('type', 7)
            ->where('id', $data['scout_id'])
            ->first();

        if (empty($scout)) {
            return [
                "status"  => false,
                "message" => __('messages.invalid_scout_id')
            ];
        }

        $scout->status = $data['status'];
        $scout->save();

        return [
            "status"  => true,
            "message" => __('messages.scout_status_changed')
        ];
    }

    /**
     * @param $data
     * @param $token
     * @throws Exception
     */
    public function requestStatusUpdate($data, $token)
    {
        $user = getUserInfo($token);

        $prc_current_request = $this->prc_scout_request
            ->where('id', $data['request_id'])
            ->where('scout_user_id', $user->id)
            ->first();

        if (empty($prc_current_request)) {
            throw new Exception(__('messages.invalid_request_id'), 200);
        }

        $prc_current_request->status = $data['status'];

        if ($data['status'] == 3) {
            $exclude_evaluators = [$user->id];
            if (!empty($prc_current_request->rejected_by)) {
                $rejected_by        = json_decode($prc_current_request->rejected_by);
                $exclude_evaluators = array_merge($exclude_evaluators, $rejected_by);
            }
            $prc_request = $this->scout->leftJoin('prc_scout_requests', 'prc_scout_requests.scout_user_id', '=', 'prc_users.id')
                ->where('prc_users.type', 3)
                ->where('prc_users.league', 1)
                ->where('prc_users.status', 'Active')
                ->whereNotIn('prc_users.id', $exclude_evaluators)
                ->groupBy('prc_users.id')
                ->orderBy('total', 'asc')
                ->first([
                    'prc_users.id',
                    'prc_users.email',
                    DB::raw('count(prc_scout_requests.scout_user_id) AS total')
                ]);

            if (empty($prc_request)) {
                throw new Exception(__('messages.no_evaluator_available'), 200);
            }

            $rejected_by = [];

            if (!empty($prc_current_request->rejected_by)) {
                $rejected_by = json_decode($prc_current_request->rejected_by);
            }

            array_push($rejected_by, $user->id);

            $prc_current_request->scout_user_id = $prc_request->id;
            $prc_current_request->status        = 1;
            $prc_current_request->rejected_by   = json_encode($rejected_by);

            $evaluator = getUserInfo($prc_request->id, 'id');

            $mail_data = [
                'evaluator_first_name' => $evaluator->first_name,
                'evaluator_last_name'  => $evaluator->last_name,
                'player_first_name'    => $user->first_name,
                'player_last_name'     => $user->last_name,
            ];

            // Mail::to($evaluator->email)->send(new NotifyEvaluator($mail_data));
        } else {
            $mail_data = [
                'player_name' => $user->first_name . " " . $user->last_name
            ];

            Mail::to($user->email)->send(new PlayerNotifyAboutEvaluationRequestAcceptedMail($mail_data));
        }

        $prc_current_request->save();
    }

    /**
     * @param $player_id
     *
     * @return array
     */
    public function getSkills($player_id)
    {
        $player_info = getUserInfo($player_id, 'id');
        $skills      = $this->prc_skill
            ->where('player_type', getUserType($player_info->type));
//
//        if (!empty($player_info->sub_type)) {
//            $skills = $skills->where('player_sub_type', $player_info->sub_type);
//        }

        $skills = $skills->orderBy('id')->get();

        $scouting_skill = [];

        if (!empty($skills)) {
            foreach ($skills as $skill) {
                $scouting_skill[] = [
                    'skill' => $skill->skill,
                    'value' => 0
                ];
            }
        }

        return $scouting_skill;
    }

    /**
     * @param $data
     * @param $token
     *
     * @return mixed
     */
    public function submitScoutingReport($data, $token)
    {
        $scout = getUserInfo($token);

        $scouting_report = $this->prc_report->where('scout_request_id', $data['scout_request_id'])->first();

        if (empty($scouting_report)) {
            $scouting_report = $this->prc_report->create([
                "player_user_id"       => $data['player_id'],
                "scout_user_id"        => $scout->id,
                "game"                 => $data['game'],
                "skills"               => $data['skills'],
                "long_range_potential" => $data['long_range_potential'],
                "scout_comment"        => $data['scout_comment'],
                "recommendation"       => $data['recommendation'],
                "published"            => $data['published'],
                "scout_request_id"     => $data['scout_request_id']
            ]);
        } else {
            if (!$data['published']) {
                $scouting_report->player_user_id       = $data['player_id'];
                $scouting_report->scout_user_id        = $scout->id;
                $scouting_report->game                 = $data['game'];
                $scouting_report->skills               = $data['skills'];
                $scouting_report->long_range_potential = $data['long_range_potential'];
                $scouting_report->scout_comment        = $data['scout_comment'];
                $scouting_report->recommendation       = $data['recommendation'];
            }
            $scouting_report->published = $data['published'];

            $scouting_report->save();
        }

        $report = $scouting_report->with(['scout', 'player', 'player.player_league'])->where('id', $scouting_report->id)->first();

        $report->player->league = checkEmpty($report->player->player_league, 'league_name', '');
        $report->scout->type    = getUserType($report->scout->type);
        $report->player->type   = getUserType($report->player->type);
        $report_data            = $report;
        $report_data['saved']   = checkReportSaved($report->id, $scout->id);

        $prc_scout_request         = $this->prc_scout_request->where('id', $data['scout_request_id'])->first();
        $prc_scout_request->status = 5;
        $prc_scout_request->save();

        return $report_data;
    }

    /**
     * @param $data
     * @return void
     */
    public function publishScoutingReport($data)
    {
        $scouting_report = $this->prc_report->with(['player'])->where('id', $data['report_id'])->first();

        $scouting_report->published = $data['published'];
        $scouting_report->rating    = checkEmpty($data, 'rating', 0);

        if ($data['published']) {
            $modified_skill_data = json_decode($scouting_report->modified_skills);
            foreach ($modified_skill_data as $modified_skill) {
                $statement_id = getAssessmentStatementId($scouting_report->player_user_id, $modified_skill);

                if ($statement_id > 0) {
                    $this->prc_assessment_statement_log->create([
                        'player_id'           => $scouting_report->player_user_id,
                        'report_id'           => $scouting_report->id,
                        'assessment_value_id' => $modified_skill,
                        'statement_id'        => $statement_id,
                    ]);
                }
            }
        }
        $scouting_report->save();

        $prc_scout_request         = $this->prc_scout_request->where('id', $scouting_report->scout_request_id)->first();
        $prc_scout_request->status = 6;
        $prc_scout_request->save();

        $email_data = [
            'player_name' => $scouting_report->player->first_name . " " . $scouting_report->player->last_name
        ];

        Mail::to($scouting_report->player->email)->send(new PlayerNotifyAboutEvaluationRequestCompletedMail($email_data));
    }


    /**
     * @return mixed
     * @throws Exception
     */
    public function getAllScouts()
    {
        $scouts = $this->scout->where('type', 7)
            ->orderBy('id', 'DESC')
            ->get();

        if (empty($scouts->toArray())) {
            throw new Exception(__('messages.no_scout_available'), 200);
        }

        $scouts->makeHidden(['token', 'password_reset_pin']);
        $scouts->makeVisible(['status']);

        if (empty($scouts->toArray())) {
            throw new Exception(__('messages.no_scout_available'), 200);
        }

        return $scouts;
    }

    public function convertToEvaluator($data)
    {
        $this->scout->where('id', $data['scout_id'])->update([
            'type'   => 3,
            'league' => 1
        ]);
    }
}
