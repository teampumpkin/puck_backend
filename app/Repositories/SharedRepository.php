<?php

namespace App\Repositories;

use App\Mail\AutoReplyForMentorshipEnrollmentMail;
use App\Mail\AutoReplyForOneOnOneConsultationSignUpMail;
use App\Mail\NotifyEvaluator;
use App\Mail\PlayerNotifyAboutNewEvaluationRequestMail;
use App\Models\Chat;
use App\Models\City;
use App\Models\Country;
use App\Models\PrcAdvanceAssessmentValue;
use App\Models\PrcAssessmentStatementLog;
use App\Models\PrcLeague;
use App\Models\PrcOneTimeSubscription;
use App\Models\PrcPlan;
use App\Models\PrcPosition;
use App\Models\PrcReport;
use App\Models\PrcSave;
use App\Models\PrcScoutRequest;
use App\Models\PrcSubscription;
use App\Models\State;
use App\Models\User;
use App\Models\PrcAdvanceAssessmentValueStatement;
use App\Models\PrcSkill;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Stripe\Stripe;
use Stripe\Charge;
use Stripe\PaymentIntent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Class SharedRepository
 * @package App\Repositories
 */
class SharedRepository
{
    /**
     * @var PrcSave
     */
    private $prc_save;
    /**
     * @var PrcReport
     */
    private $prc_report;
    /**
     * @var User
     */
    private $user;
    /**
     * @var PrcPosition
     */
    private $prc_position;
    /**
     * @var PrcLeague
     */
    private $prc_league;
    /**
     * @var PrcSubscription
     */
    private $prc_subscription;
    /**
     * @var PrcPlan
     */
    private $prc_plan;
    /**
     * @var Chat
     */
    private $chat;
    /**
     * @var PrcAssessmentStatementLog
     */
    private $prc_assessment_statement_log;
    /**
     * @var Country
     */
    private $country;
    /**
     * @var State
     */
    private $state;
    /**
     * @var City
     */
    private $city;
    /**
     * @var PrcOneTimeSubscription
     */
    private $prc_one_time_subscription;
    /**
     * @var PrcScoutRequest
     */
    private $prc_scout_request;
    /**
     * @var PrcAdvanceAssessmentValueStatement
     */
    private $prc_advance_assessment_value_statement;
    /**
     * @var PrcAdvanceAssessmentValue
     */
    private $prc_advance_assessment_value;

    private $default_image = "data:image/png;base64,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";

    /**
     *
     */
    public function __construct()
    {
        $this->prc_save                     = new PrcSave();
        $this->prc_report                   = new PrcReport();
        $this->user                         = new User();
        $this->prc_position                 = new PrcPosition();
        $this->prc_league                   = new PrcLeague();
        $this->prc_subscription             = new PrcSubscription();
        $this->prc_plan                     = new PrcPlan();
        $this->chat                         = new Chat();
        $this->country                      = new Country();
        $this->state                        = new State();
        $this->city                         = new City();
        $this->prc_assessment_statement_log = new PrcAssessmentStatementLog();
        $this->prc_one_time_subscription    = new PrcOneTimeSubscription();
        $this->prc_scout_request            = new PrcScoutRequest();
        $this->prc_advance_assessment_value_statement = new PrcAdvanceAssessmentValueStatement();
        $this->prc_advance_assessment_value = new PrcAdvanceAssessmentValue();
    }

    /**
     * @param $token
     * @param $report_id
     *
     * @return string
     */
    public function saveUnSaveReport($token, $report_id)
    {
        $current_user = getUserInfo($token);

        $prc_save = $this->prc_save->where('user_id', $current_user->id)->first();

        $return_message = __('messages.report_added_in_favourite');

        if (empty($prc_save)) {
            $this->prc_save->create([
                'user_id' => $current_user->id,
                'players' => "",
                'teams'   => "",
                'reports' => json_encode([$report_id])
            ]);
        } else {
            $prc_saved_reports = (!empty($prc_save->reports)) ? json_decode($prc_save->reports) : [];

            if (!empty($prc_saved_reports) && in_array($report_id, $prc_saved_reports)) {
                $prc_saved_reports = array_flip($prc_saved_reports);
                unset($prc_saved_reports[ $report_id ]);
                $prc_saved_reports = array_values(array_map('strval', array_flip($prc_saved_reports)));

                $return_message = __('messages.report_removed_in_favourite');
            } else {
                $prc_saved_reports = array_merge($prc_saved_reports, [$report_id]);
            }

            $prc_save->reports = (empty($prc_saved_reports)) ? "" : json_encode($prc_saved_reports);
            $prc_save->save();

        }

        return $return_message;
    }

    /**
     * @param $token
     *
     * @return array
     * @throws Exception
     */
    public function getReports($token)
    {
        $user = getUserIdAndType($token);

        $report_data = [];

        $reports = $this->prc_report
            ->with(['player', 'scout', 'scout_request'])
            ->where('published', true)
            ->where(($user->type == 2 ? 'player_user_id' : 'scout_user_id'), $user->id)
            ->orderBy('created_at', 'DESC')
            ->get();

        if (empty($reports->toArray())) {
            throw new Exception(__('messages.no_report_available'), 200);
        }

        foreach ($reports as $index => $report) {
            $report_data[ $index ]          = $report;
            $report_data[ $index ]['saved'] = checkReportSaved($report->id, $user->id);
            $report_data[ $index ]["skills"] = json_decode($report["skills"]);

            // Get ratings and skill ids
            $skillRatings = [];
            foreach ($report_data[$index]["skills"] as $skill) {
                foreach ($skill->data as $data) {
                    $skillRatings[] = ['skill_id' => $data->id, 'rating' => $data->setRating];
                }
            }

            // Get all evaluations result using data from new skillRating array
            $evaluations = $this->prc_advance_assessment_value
                ->with('assessment_statements')
                ->whereIn('skill_id', array_column($skillRatings, 'skill_id'))
                ->whereIn('rating', array_column($skillRatings, 'rating'))
                ->get();

            // Organize elements in associative array for quicker search
            $evaluationData = [];
            foreach ($evaluations as $evaluation) {
                $key = $evaluation->skill_id . '_' . $evaluation->rating;
                $evaluationData[$key] = $evaluation->assessment_statements[0]->statement;
            }

            // Assign evaluation result to report
            foreach ($report_data[$index]["skills"] as $skill) {
                foreach ($skill->data as $data) {
                    $key = $data->id . '_' . $data->setRating;
                    $data->evaluation = $evaluationData[$key] ?? '';
                }
            }

        }

        return $report_data;
    }

    /**
     * @return int[]
     */
    public function dashboardData()
    {
        $dashboard_data = [
            'player'             => 0,
            'inactive_player'    => 0,
            'pending_scout'      => 0,
            'fans'               => 0,
            'inactive_fans'      => 0,
            'team'               => 0,
            'inactive_team'      => 0,
            'academy'            => 0,
            'inactive_academy'   => 0,
            'evaluator'          => 0,
            'inactive_evaluator' => 0
        ];

        $users = $this->user->get();

        if (!empty($users)) {
            foreach ($users as $user) {
                switch ($user->type) {
                    case 2:
                        $dashboard_data['player']++;
                        if ($user->status == 'Inactive') {
                            $dashboard_data['inactive_player']++;
                        }
                        break;
                    case 3:
                        $dashboard_data['evaluator']++;
                        if ($user->status == 'Inactive') {
                            $dashboard_data['inactive_evaluator']++;
                        }
                        break;
                    case 6:
                        $dashboard_data['fans']++;
                        if ($user->status == 'Inactive') {
                            $dashboard_data['inactive_fans']++;
                        }
                        break;
                    case 4:
                        $dashboard_data['team']++;
                        if ($user->status == 'Inactive') {
                            $dashboard_data['inactive_team']++;
                        }
                        break;
                    case 5:
                        $dashboard_data['academy']++;
                        if ($user->status == 'Inactive') {
                            $dashboard_data['inactive_academy']++;
                        }
                        break;

                }
            }
        }

        return $dashboard_data;
    }

    /**
     * @param $token
     * @param false $saved_team
     * @return array
     * @throws Exception
     */
    public function getSavedPlayers($token, $saved_team = false)
    {
        $user = getUserIdAndType($token);

        $saved_players = $this->prc_save->where('user_id', $user->id)->first();

        $type = ($saved_team) ? "team" : "player";

        if (empty($saved_players) || empty($saved_players->players)) {
            throw new Exception(str_replace('%type%', $type, __('messages.not_favourite_item')), 200);
        }

        $player_data = json_decode($saved_players->players);

        $players = [];

        foreach ($player_data as $saved_player) {
            $player = getUserInfo($saved_player, 'id', true);

            if (empty($player)) {
                continue;
            }

            if ($saved_team && !in_array($player->type, [4, 5])) {
                continue;
            }

            if (!$saved_team && in_array($player->type, [4, 5])) {
                continue;
            }

            $players[] = createUserObject($player, $user->id);
        }
        if (empty($players)) {
            throw new Exception(str_replace('%type%', $type, __('messages.not_favourite_item')), 200);
        }
        return $players;
    }

    /**
     * @param $token
     * @return array
     * @throws Exception
     */
    public function getSavedReports($token)
    {
        $user = getUserIdAndType($token);

        $saved_reports = $this->prc_save->where('user_id', $user->id)->first();

        if (empty($saved_reports) || empty($saved_reports->reports)) {
            throw new Exception(__('messages.not_favourite_report'), 200);
        }

        $report_data = json_decode($saved_reports->reports);

        $reports = [];

        foreach ($report_data as $saved_report) {
            $reports[] = $this->getReport($token, $saved_report);
        }

        return $reports;
    }

    /**
     * @param $token
     * @param $report_id
     *
     * @return Builder|Model|object
     * @throws Exception
     */
    public function getReport($token, $report_id)
    {
        $user = getUserIdAndType($token);

        $report = $this->prc_report->with(['player', 'player.player_position', 'scout', 'scout_request'])
            ->where(($user->type == 2 ? 'player_user_id' : 'scout_user_id'), $user->id)
            ->where('id', $report_id)
            ->first();

        if (empty($report)) {
            throw new Exception(__('messages.invalid_report_id'), 200);
        }

        $skills = json_decode($report->skills);

        $statement_logs = $this->prc_assessment_statement_log
            ->with(['statement'])
            ->where('report_id', $report_id)->get();

        foreach ($skills as $skill) {
            if (!empty($skill->data)) {
                foreach ($skill->data as $key => $skill_value) {
                    if (isset($skill_value->CAllChildIndex)) {
                        if (!empty($statement_logs[ $skill_value->CAllChildIndex ]) && !empty($statement_logs[ $skill_value->CAllChildIndex ]->statement->statement)) {
                            $skill->data[ $key ]->player_message = $statement_logs[ $skill_value->CAllChildIndex ]->statement->statement;
                        }
                    }
                }
            }
        }

        $report->skills = $skills;

        $report_data          = $report;
        $report_data['saved'] = checkReportSaved($report->id, $user->id);

        return $report_data;
    }

    /**
     * @param $filter
     * @param $token
     * @return array
     * @throws Exception
     */
    public function filterUsers($filter, $token)
    {
        $user = getUserIdAndType($token);

        $users = $this->user->where('status', 'Active')
            ->whereNotIn('type', [1, 3, 8])
            ->where(function ($query) use ($filter) {
                return $query->where('first_name', 'ilike', '%' . $filter . '%')
                    ->orWhere('last_name', 'ilike', '%' . $filter . '%');
            })
            ->orderBy('first_name', 'ASC')
            ->limit(5)
            ->get();

        if (empty($users->toArray())) {
            throw new Exception(__('messages.no_player_available_for_filter'), 200);
        }

        $filtered_users = [];

        foreach ($users as $filtered_user) {
            $filtered_users[] = createUserObject($filtered_user, $user->id);
        }

        return $filtered_users;
    }

    /**
     * @param $user_id
     * @param $token
     * @return mixed
     * @throws Exception
     */
    public function getUserProfile($user_id, $token)
    {
        $user = getUserInfo($user_id, 'id');

        if (empty($user)) {
            throw new Exception('User not found!', 200);
        }

        $user->country = $user->country_id ? $user->countryR->country_name : null;
        $user->state = $user->state_id ? $user->stateR->state_name : null;
        $user->city = $user->city_id ? $user->cityR->city_name : null;
        $user->country_flag = $user->country_id ? $user->countryR->country_flag : null;

        unset($user->cityR);
        unset($user->stateR);
        unset($user->countryR);

        $current_user = getUserInfo($token);

        return createUserObject($user, $current_user->id);
    }


    /**
     * @param false $is_for_admin
     * @return mixed
     */
    public function getPositions($is_for_admin = false)
    {
        if ($is_for_admin) {
            return $this->prc_position->get();
        }
        return $this->prc_position->where('status', 1)->get();
    }

    /**
     * @param $data
     */
    public function addPosition($data)
    {
        $position = $this->prc_position->create([
            'position_name' => $data['position_name'],
            'short_name'    => $data['short_name']
        ]);

        Artisan::call('sync:skill', ['--position_id' => $position->id]);
    }

    /**
     * @return array|array[]
     */
    public function getFilterData()
    {
        $filter_data = [
            'years'   => [],
            'leagues' => [],
            'teams'   => [],
            'sort_by' => []
        ];

        for ($i = 1990; $i < Carbon::now()->year - 5; $i++) {
            $filter_data['years'][] = $i;
        }

        $teams = $this->user->where('status', 'Active')->where('type', 4)->get([
            'id',
            'first_name',
            'last_name'
        ]);

        foreach ($teams as $team) {
            $filter_data['teams'][] = $team;
        }

        $leagues = $this->getLeagues();

        foreach ($leagues as $league) {
            $filter_data['leagues'][] = $league;
        }

        $filter_data['sort_by'] = [
            (object) ['label' => 'Player', 'value' => 'player'],
            (object) ['label' => 'Birth year', 'value' => 'dob'],
            (object) ['label' => 'Position', 'value' => 'position'],
            (object) ['label' => 'Team', 'value' => 'team'],
            (object) ['label' => 'League', 'value' => 'league'],
            (object) ['label' => 'Scouts', 'value' => 'scouts'],
            (object) ['label' => 'Coaches', 'value' => 'coaches'],
        ];


        return $filter_data;
    }

    /**
     * @return mixed
     */
    public function getLeagues()
    {
        return $this->prc_league->where('status', 1)->get([
            'id',
            'league_name'
        ]);
    }

    /**
     *
     * @throws Exception
     */
    public function getPlans($player_id = 0)
    {
        $plans = $this->prc_plan->get();

        if (empty($plans)) {
            throw new Exception(__('messages.no_plan_available'), 200);
        }

        $is_subscribed = false;
        foreach ($plans as $plan) {
            $plan->is_subscribed     = false;
            $plan->is_cancelled      = false;
            $plan->cancelled_message = "";

            if (!$is_subscribed) {
                $plan_detail = $this->prc_subscription->where('plan_code', $plan->plan_code)
                    ->where('user_id', $player_id)
                    ->where('renew_on', '>=', Carbon::now()->format('Y-m-d'))
                    ->first();

                if (!empty($plan_detail)) {
                    $plan->is_cancelled      = $plan_detail->is_cancelled;
                    $plan_expire_date        = $plan_detail->renew_on;
                    $plan->cancelled_message = (!$plan_detail->is_cancelled) ? "" : "You've cancelled this subscription but still got benefit of this subscription till $plan_expire_date after that you can buy this subscription again";
                    $is_subscribed           = true;
                    $plan->is_subscribed     = !$plan_detail->is_cancelled;
                }
            }
        }

        return $plans;
    }

    /**
     * @param $data
     * @return mixed
     * @throws Exception
     */
    public function generatePaymentPage($data)
    {
        $user        = getUserInfo($data['player_id'], 'id');
        $zoho_helper = createZohoClassObject();
        $customer_id = $user->zoho_customer_id;
        if (empty($user->zoho_customer_id)) {
            $customer_id            = $zoho_helper->createCustomer($user);
            $user->zoho_customer_id = $customer_id;
            $user->save();
        }

        return $zoho_helper->createPaymentPage($customer_id, $data['plan_code']);
    }

    /**
     * @param $data
     * @return array
     * @throws Exception
     */
    public function saveSubscription($data)
    {
        $hosted_page_id        = $data['hostedpage_id'];
        $assessment_request_id = checkEmpty($data, 'assessment_request_id', 0);
        $zoho_helper           = createZohoClassObject();
        $hosted_page_details   = $zoho_helper->getHostedPageDetail($hosted_page_id);

        $user = getUserIdAndType($hosted_page_details->customer_id, 'zoho_customer_id');

        if ($data['assessment_request_id'] == 0 && $data['for_call'] == false) {
            $this->prc_subscription->create([
                'user_id'         => $user->id,
                'subscription_id' => $hosted_page_details->subscription_id,
                'plan_code'       => $hosted_page_details->plan->plan_code,
                'card_id'         => $hosted_page_details->card->card_id,
                'start_from'      => $hosted_page_details->start_date,
                'renew_on'        => $hosted_page_details->next_billing_at,
                'extra_data'      => json_encode($hosted_page_details)
            ]);

            return [
                'name'      => $hosted_page_details->plan->name,
                'price'     => $hosted_page_details->plan->total,
                'next_date' => $hosted_page_details->next_billing_at
            ];
        }
        if (!$data['for_call'] && $data['assessment_request_id'] > 0) {
            $this->prc_one_time_subscription->create([
                'user_id'         => $user->id,
                'subscription_id' => $hosted_page_details->subscription_id,
                'plan_code'       => $hosted_page_details->plan->plan_code,
                'card_id'         => $hosted_page_details->card->card_id,
                'start_from'      => $hosted_page_details->start_date,
                'renew_on'        => $hosted_page_details->next_billing_at,
                'extra_data'      => json_encode($hosted_page_details)
            ]);

            $assessment_request = $this->prc_scout_request->where('id', $assessment_request_id)->first();

            $prc_request = $this->user->leftJoin('prc_scout_requests', 'prc_scout_requests.scout_user_id', '=', 'prc_users.id')
                ->where('prc_users.type', 3)
                ->where('prc_users.league', $assessment_request->league_id)
                ->where('prc_users.status', 'Active')
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
            $assessment_request->scout_user_id            = $prc_request->id;
            $assessment_request->one_time_subscription_id = $hosted_page_details->subscription_id;
            $assessment_request->save();

            $evaluator = getUserInfo($prc_request->id, 'id');

            $mail_data = [
                'evaluator_name' => $evaluator->first_name . " " . $evaluator->last_name
            ];

            // Mail::to($evaluator->email)->send(new NotifyEvaluator($mail_data));

            $email_data['name'] = $user->first_name . " " . $user->last_name;

            Mail::to($user->email)->send(new PlayerNotifyAboutNewEvaluationRequestMail($email_data));

            return [
                'evaluation_request' => true
            ];
        }
        if ($data['for_call']) {
            $this->prc_one_time_subscription->create([
                'user_id'         => $user->id,
                'type'            => 'one-to-one-call',
                'subscription_id' => $hosted_page_details->subscription_id,
                'plan_code'       => $hosted_page_details->plan->plan_code,
                'card_id'         => $hosted_page_details->card->card_id,
                'start_from'      => $hosted_page_details->start_date,
                'renew_on'        => $hosted_page_details->next_billing_at,
                'extra_data'      => json_encode($hosted_page_details)
            ]);

            $email_data = [
                'player_name' => $user->first_name . " " . $user->last_name
            ];

            Mail::to($user->email)->send(new AutoReplyForOneOnOneConsultationSignUpMail($email_data));

            return [
                'one_to_one_call' => true
            ];
        }
    }

    /**
     * @param $data
     * @return void
     * @throws Exception
     */
    public function saveMentorshipSubscription($data)
    {
        $hosted_page_id = $data['hostedpage_id'];

        $zoho_helper = createZohoClassObject();

        $hosted_page_details = $zoho_helper->getHostedPageDetail($hosted_page_id);

        $user = getUserIdAndType($hosted_page_details->customer_id, 'zoho_customer_id');

        $this->prc_one_time_subscription->create([
            'user_id'         => $user->id,
            'type'            => 'mentorship',
            'subscription_id' => $hosted_page_details->subscription_id,
            'plan_code'       => $hosted_page_details->plan->plan_code,
            'card_id'         => $hosted_page_details->card->card_id,
            'start_from'      => $hosted_page_details->start_date,
            'renew_on'        => $hosted_page_details->next_billing_at,
            'extra_data'      => json_encode($hosted_page_details)
        ]);

        $email_data = [
            'player_name' => $user->first_name . " " . $user->last_name
        ];

        Mail::to($user->email)->send(new AutoReplyForMentorshipEnrollmentMail($email_data));
    }

    /**
     * @param $data
     * @param $token
     * @throws Exception
     */
    public function addNewPlan($data, $token)
    {
        $zoho_helper = createZohoClassObject();
        $response    = $zoho_helper->createNewPlan($data);

        if ($response->code != 0) {
            throw new Exception($response->message);
        }
        $user = getUserIdAndType($token);
        $this->prc_plan->create([
            'plan_name'        => $response->plan->name,
            'product_id'       => $response->plan->product_id,
            'plan_code'        => $response->plan->plan_code,
            'plan_price'       => $response->plan->recurring_price,
            'interval'         => $response->plan->interval,
            'interval_unit'    => $response->plan->interval_unit,
            'plan_description' => $response->plan->description,
            'extra_data'       => json_encode($response->plan),
            'created_by'       => $user->id,
        ]);
    }

    /**
     * @param $token
     * @param $user_id
     * @return array|string|string[]|null
     */
    public function getChatId($token, $user_id)
    {
        $user = getUserIdAndType($token);

        $chat = $this->chat->where('is_blocked', 0)
            ->where(function ($query) use ($user, $user_id) {
                return $query->where('user1', $user->id)
                    ->where('user2', $user_id);
            })->orWhere(function ($query) use ($user, $user_id) {
                return $query->where('user2', $user->id)
                    ->where('user1', $user_id);
            })->first();

        if (!empty($chat)) {
            return $chat->uuid;
        }

        $uuid = generateToken();

        $this->chat->create([
            'uuid'  => $uuid,
            'user1' => $user->id,
            'user2' => $user_id
        ]);

        return $uuid;
    }

    /**
     * @param $token
     * @return array
     * @throws Exception
     */
    public function getRecentChats($token)
    {
        $user = getUserIdAndType($token);

        $chats = $this->chat->with(['user_1', 'user_2'])
            ->where(function ($query) use ($user) {
                return $query->where('user1', $user->id)
                    ->orWhere('user2', $user->id);
            })->get();

        if (empty($chats->toArray())) {
            throw new Exception(__('messages.no_chat_available'), 200);
        }

        $recent_chats = [];

        foreach ($chats as $chat) {
            $player_id = ($chat->user1 != $user->id) ? $chat->user1 : $chat->user2;

            $recent_chats[] = [
                'id'   => $chat->id,
                'uuid' => $chat->uuid,
                'user' => ($user->id != $chat->user1) ? [
                    'id'              => $chat->user_1->id,
                    'first_name'      => $chat->user_1->first_name,
                    'last_name'       => $chat->user_1->last_name,
                    'email'           => $chat->user_1->email,
                    'profile_picture' => $chat->user_1->s3_profile_picture
                ] : [
                    'id'              => $chat->user_2->id,
                    'first_name'      => $chat->user_2->first_name,
                    'last_name'       => $chat->user_2->last_name,
                    'email'           => $chat->user_2->email,
                    'profile_picture' => $chat->user_2->s3_profile_picture
                ],

                'can_chat' => !($chat->is_blocked) && validateCanSendMessage($user->id, $player_id)
            ];
        }

        return $recent_chats;
    }

    /**
     * @param $player_id
     * @throws Exception
     */
    public function cancelSubscription($player_id)
    {
        $user = getUserInfo($player_id, "id", true, ['current_subscription']);

        if (empty($user->current_subscription)) {
            throw new Exception(__('messages.no_subscription_available'), 200);
        }
        $zoho_helper = createZohoClassObject();
        $zoho_helper->cancelSubscription($user->current_subscription->subscription_id);
        $user->current_subscription->is_cancelled = 1;
        $user->current_subscription->save();
    }

    /**
     * @return mixed
     */
    public function getCountries()
    {
        return $this->country->where('status', 1)
            ->orderBy('country_name')
            ->get();
    }

    /**
     * @param $country_id
     * @return mixed
     */
    public function getStates($country_id)
    {
        return $this->state->where('country_id', $country_id)
            ->where('status', 1)
            ->orderBy('state_name')
            ->get();
    }

    /**
     * @param $state_id
     * @return mixed
     */
    public function getCities($state_id)
    {
        return $this->city->where('state_id', $state_id)
            ->where('status', 1)
            ->orderBy('city_name')
            ->get();
    }

    /**
     * @param $alert_type
     * @return array
     * @throws Exception
     */
    public function popupDescription($alert_type)
    {
        switch ($alert_type) {
            case 1:
                return [
                    'id'            => 1,
                    'alert_title'   => "Personalized Assessment",
                    'alert_content' => "Choose a video with up to 10 minutes of your best performance content to submit for an evaluation by a professional Puck Recruiter evaluator and receive back a detailed report on various skill content. You will get a better idea of what a professional sees as your personal strengths and opportunities for growth. After our Beta launch, for an additional investment, you can share this assessment with coaches, scouts, and peers. Best of all, it is presented in a professional format that can also highlight your growth over time by including multiple assessment results."
                ];
            case 2:
                return [
                    'id'            => 2,
                    'alert_title'   => "Personalized Live Consultation",
                    'alert_content' => "After ordering a Puck Recruiter Assessment, you will have an opportunity to further your development by purchasing a live one-on-one consultation with a professional Puck Recruiter evaluator. In this highly curated 30-minute session, you will review your assessment results and discuss next steps for growth. After our Beta launch, you will have the opportunity to include this specialist reference within a unique personalized portfolio to send to future teams."
                ];
            case 3:
                return [
                    'id'            => 3,
                    'alert_title'   => "Mentorship Program",
                    'alert_content' => "Upgrade to our most extensive developmental skill-building plan. This program is for the player that wants to take their game to the next level while being guided by a professional who has seasoned experience within the top leagues of the game." .
                        "\n\nYour mentorship package will span 12 weeks and will include two assessments, two live one-on-one 30-minute consultations, along with weekly live check-ins with a professional Puck Recruiter mentor. Within this program, your mentor will work with you to develop a growth plan exclusive to you and your particular goals and areas for opportunity. You will have exclusive access to a professional that has been where you have been and knows the trade secrets and improvement measures needed to get to where you want to go." .
                        "\n\nIncluded in the Mentorship Program is a professional marketing portfolio specifically crafted to highlight your personal strengths and skills. To compliment your hard work, after our Beta launch, you will be featured on the Highlight Reel for a 12-week duration (more to come on this soon). Finally, after Beta, you will have the ability to share unlimited assessment reports with coaches, scouts, and others who are interested in seeing how far you have progressed." .
                        "\n\nUpon purchase, you will be billed for a one-time $389 (CDN) onboarding fee. Once your 12-week program starts, you will be billed $325 (CDN) each month until the end of your program. "
                ];
            case 4:
                return [
                    'id'            => 3,
                    'alert_title'   => "About section",
                    'alert_content' => "Hockey is our thing. But a lot of people can say that." .
                        "\n\nJust like you, we know what it is like to spend hours and hours on training, dollars and dollars on camps and programs, and even more on consultants and agents. Does it work to get you to where you want to be in the game?" .
                        "\n\nSometimes. But...have you looked at the stats of how many players attend " .
                        '"main"' .
                        "camps versus how many end up in the NHL? It isn't promising, is it?" .
                        "\n\nHere is the deal. In all of those events, can you say you received the feedback you needed WHEN you needed it? Was it specific enough to address YOUR individual opportunities for growth and did it help you to create a personalized comprehensive plan to get to the next level? I know it goes against what you typically think, but this isn't about your team, it is about YOU, so you can be better for your team and crush those goals." .
                        "\n\nPuck Recruiter is a company that was started by a group of professional hockey players ranging from the NHL, USHL, WHL and beyond. We have been where you have been and we want to help you get to where you want to be, with a more direct, personal way of doing it. In the end, we are fueled with the passion to make the journey to the top a little more supported and a little more clear, because we have been there. And we want you to join us!"
                ];

            default:
                throw new Exception(__('messages.popup_description_not_found'), 200);
        }

    }

    /**
     * @param $page
     * @return array
     */
    public function oneOnOneCallRequest($page)
    {
        $limit  = 15;
        $offset = $page * $limit;

        $call_requests = $this->prc_one_time_subscription->with(['user'])
            ->where('type', 'one-to-one-call')
            ->orderBy('id', 'DESC');

        $total_rows = $call_requests->get()->count();

        $call_requests = $call_requests
            ->skip($offset)
            ->take($limit)
            ->get();

        $call_request_data['total_rows']    = $total_rows;
        $call_request_data['call_requests'] = $call_requests;

        return $call_request_data;
    }

    /**
     * @param $data
     * @return void
     * @throws Exception
     */
    public function updateOneOnOneRequestStatus($data)
    {
        $call_request = $this->prc_one_time_subscription->where('id', $data['call_request_id'])->first();

        if (empty($call_request)) {
            throw new Exception(__('messages.invalid_call_request_id'), 200);
        }

        $call_request->status = $data['status'];
        $call_request->save();
    }

    /**
     * @param $user_id
     * @throws Exception
     */
    public function getProfileImage($user_id)
    {
        $user = getUserInfo($user_id, "id");

        $image_64 = $this->default_image;

        if(!empty($user['profile_picture'])){
            $image_64 = $user['profile_picture']; //your base64 encoded data
        }

        switch (substr($image_64, 0, 1)) {
            case "/":
                $extension = "jpeg";
                break;
            default:
                $extension = "png";
                break;
        }

        $image = '';

        // Get header data:image/png;base64, in case have it
        $header = substr($image_64, 0, strpos($image_64, ',')+1);

        if (!empty($header)) {
            // if header return just one character as / or i, etc, set as empty string to avoid his replacement
            if (strlen($header) === 1) { $header = ''; }

            // if header exist remove it from base64 to use only content
            $image = str_replace($header, '', $image_64);

            $image = str_replace(' ', '+', $image);
        }

        return [base64_decode($image), $extension];

    }

    /**
     * @param $user_id
     * @throws Exception
     */
    public function updateStatusAccount($user_id, $token, $status = 'Active')
    {
        $user = User::where('id', $user_id)->first();
        $actual_user = User::where('token', $token)->first();
        if(($actual_user->id != $user->id) && ($actual_user->type != 1 && $actual_user->type != 8)){
            return prepare_response(400, false, __('You have not authorization to delete another user'));
        }
        if(!empty($user)){
            if(!empty($status)){
                $user->status = $status;
                return prepare_response(200, true, __('messages.change_status_account'));
            }

            if($user->status != 'Deleted'){
                $user->status = 'Deleted';
            }else{
                $user->status = 'Active';
            }

            $user->save();
            return prepare_response(200, true, __('messages.change_status_account'));
        }

        return prepare_response(404, false, __('messages.user_info_not_found'));

    }

    /**
     * @param $token
     * @throws Exception
     */
    public function createStripePay($token, $data)
    {
        $user = getUserIdAndType($token);
        Stripe::setApiKey(config('services.stripe.secret'));

        $paymentIntent = PaymentIntent::create([
            'amount' => $data['amount'],
            'currency' => 'usd',
            'customer' => 'cus_O0eB2X8NfCqkKb',
            'description' => 'Example charge',
            'payment_method' => $data['stripe_token'],
            'confirm' => true
        ]);

        return $paymentIntent;
    }
}
