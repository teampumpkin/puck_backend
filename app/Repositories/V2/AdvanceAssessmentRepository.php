<?php

namespace App\Repositories\V2;

use App\Helpers\ZohoHelper;
use App\Mail\NotifyEvaluator;
use App\Mail\PlayerNotifyAboutEvaluationRequestCompletedMail;
use App\Models\PrcAdvanceAssessmentCategory;
use App\Models\PrcAdvanceAssessmentSkill;
use App\Models\PrcAdvanceAssessmentValue;
use App\Models\PrcAdvanceAssessmentValueStatement;
use App\Models\PrcPosition;
use App\Models\PrcReport;
use App\Models\PrcScoutRequest;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 *
 */
class AdvanceAssessmentRepository
{
    /**
     * @var PrcAdvanceAssessmentCategory
     */
    private $prc_assessment_category;
    /**
     * @var PrcAdvanceAssessmentSkill
     */
    private $prc_assessment_skill;
    /**
     * @var PrcAdvanceAssessmentValue
     */
    private $prc_assessment_skill_value;
    /**
     * @var PrcAdvanceAssessmentValueStatement
     */
    private $prc_assessment_skill_value_statements;
    /**
     * @var PrcScoutRequest
     */
    private $prc_scout_request;
    /**
     * @var User
     */
    private $player;
    /**
     * @var PrcReport
     */
    private $prc_report;
    /**
     * @var ZohoHelper
     */
    private $prc_subscription;
    /**
    * @var PrcPosition
    */
    private $prc_position;

    /**
     *
     */
    public function __construct()
    {
        $this->player                                = new User();
        $this->prc_assessment_category               = new PrcAdvanceAssessmentCategory();
        $this->prc_assessment_skill                  = new PrcAdvanceAssessmentSkill();
        $this->prc_assessment_skill_value            = new PrcAdvanceAssessmentValue();
        $this->prc_scout_request                     = new PrcScoutRequest();
        $this->prc_assessment_skill_value_statements = new PrcAdvanceAssessmentValueStatement();
        $this->prc_report                            = new PrcReport();
        $this->prc_position                          = new PrcPosition();
    }


    /**
     * @param $player_position
     * @return Builder[]|Collection
     */
    public function getSkills($player_position, $position_info = false)
    {
        if (empty($player_position)) {
            $player_position = 6;
        }
        if ($position_info) {
            return [
                'position' => $this->prc_position->where('id', $player_position)->first(['id', 'position_name', 'short_name']),
                'skills' => $this->prc_assessment_category->with(['data', 'data.skill_values'])
                    ->has('data')
                    ->where('player_position_id', $player_position)
                    ->where('status', 1)
                    ->get([
                        "id",
                        "category_name as title"
                    ])
            ];
        }
        return $this->prc_assessment_category->with(['data', 'data.skill_values'])
            ->has('data')
            ->where('player_position_id', $player_position)
            ->where('status', 1)
            ->get([
                "id",
                "category_name as title"
        ]);
    }


    /**
     * @param $page
     * @return array
     */
    public function getAssessmentCategories($page)
    {
        $limit  = 15;
        $offset = $page * $limit;

        $assessment_categories = $this->prc_assessment_category->with(['player_position']);

        $total_rows = $assessment_categories->get()->count();

        $assessment_categories = $assessment_categories
            ->skip($offset)
            ->take($limit)
            ->orderBy('id')
            ->get();

        $assessment_data['total_rows']      = $total_rows;
        $assessment_data['assessment_data'] = $assessment_categories;

        return $assessment_data;
    }

    /**
     * @param $category_id
     * @param $status
     * @throws Exception
     */
    public function assessmentCategoriesStatusChange($category_id, $status)
    {
        $assessment_category = $this->prc_assessment_category->where('id', $category_id)
            ->first();

        if (empty($assessment_category)) {
            throw new Exception(__('messages.invalid_category'), 200);
        }

        $assessment_category->status = $status;
        $assessment_category->save();
    }

    /**
     * @param $data
     */
    public function addAssessmentCategory($data)
    {
        $this->prc_assessment_category->create([
            'category_name'      => $data['category_name'],
            'category_info'      => checkEmpty($data, 'category_info', ''),
            'player_position_id' => $data['player_position_id']
        ]);
    }


    /**
     * @param $data
     * @return array
     */
    public function getAssessmentSkills($data)
    {
        $page               = checkEmpty($data, 'page', 0);
        $category_id        = checkEmpty($data, 'category_id', 0);
        $player_position_id = checkEmpty($data, 'player_position_id', 0);

        $limit  = 15;
        $offset = $page * $limit;

        $assessment_skills = $this->prc_assessment_skill->with(['assessment_category', 'assessment_category.player_position']);

        if ($player_position_id > 0) {
            $assessment_skills = $assessment_skills->leftJoin('prc_advance_assessment_categories', 'prc_advance_assessment_categories.id', '=', 'prc_advance_assessment_skills.category_id')
                ->where('prc_advance_assessment_categories.id', $category_id)
                ->where('prc_advance_assessment_categories.player_position_id', $player_position_id);
        }

        $total_rows = $assessment_skills->get()->count();

        $assessment_skills = $assessment_skills
            ->skip($offset)
            ->take($limit)
            ->orderBy('prc_advance_assessment_skills.id')
            ->get([
                'prc_advance_assessment_skills.id',
                'prc_advance_assessment_skills.skill_name',
                'prc_advance_assessment_skills.skill_info',
                'prc_advance_assessment_skills.status',
                'prc_advance_assessment_skills.category_id',

            ]);

        $assessment_data['total_rows']            = $total_rows;
        $assessment_data['assessment_skill_data'] = $assessment_skills;

        return $assessment_data;
    }

    /**
     * @param $player_position_id
     * @return mixed
     */
    public function getAssessmentCategory($player_position_id)
    {
        return $this->prc_assessment_category->where('player_position_id', $player_position_id)
            ->where('status', 1)
            ->get();
    }

    /**
     * @param $data
     */
    public function addAssessmentSkill($data)
    {
        $assessment_skill = $this->prc_assessment_skill->create([
            'skill_name'  => $data['skill_name'],
            'skill_info'  => checkEmpty($data, 'skill_info', ''),
            'category_id' => $data['category_id']
        ]);

        if ($assessment_skill->id > 0) {
            $timestamp = Carbon::now();

            $skill_id = $assessment_skill->id;

            $this->prc_assessment_skill_value->insert([
                [
                    'skill_id'   => $assessment_skill->id,
                    'rating'     => 5,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp
                ],
                [
                    'skill_id'   => $assessment_skill->id,
                    'rating'     => 4.5,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp
                ],
                [
                    'skill_id'   => $assessment_skill->id,
                    'rating'     => 4,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp
                ],
                [
                    'skill_id'   => $assessment_skill->id,
                    'rating'     => 3.5,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp
                ],
                [
                    'skill_id'   => $assessment_skill->id,
                    'rating'     => 3,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp
                ],
                [
                    'skill_id'   => $assessment_skill->id,
                    'rating'     => 2.5,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp
                ],
                [
                    'skill_id'   => $assessment_skill->id,
                    'rating'     => 2,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp
                ],
                [
                    'skill_id'   => $assessment_skill->id,
                    'rating'     => 1.5,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp
                ],
                [
                    'skill_id'   => $assessment_skill->id,
                    'rating'     => 1,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp
                ],
            ]);
        }
    }

    /**
     * @param $token
     * @param $data
     * @throws Exception
     */
    public function sendScoutRequest($token, $data)
    {
        $user = getUserInfo($token);

        $league   = $data['league'];
        $media_id = $data['media_id'];

        $scout_request = $this->prc_scout_request->where('source_user_id', $user->id)
            ->orderBy('id', 'DESC')
            ->first();

        if (!empty($scout_request) && in_array($scout_request->status, [1, 2, 5])) {
            throw new Exception(__('messages.already_assessment_request_sent'), 200);
        }

        if (empty($scout_request) || in_array($scout_request->status, [3, 4, 6])) {
            $prc_request = $this->player->leftJoin('prc_scout_requests', 'prc_scout_requests.scout_user_id', '=', 'prc_users.id')
                ->where('prc_users.type', 3)
                ->where('prc_users.league', $league)
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

            $this->prc_scout_request->create([
                'source_user_id' => $user->id,
                'scout_user_id'  => $prc_request->id,
                'media_id'       => $media_id,
                'status'         => 1
            ]);

            $evaluator = getUserInfo($prc_request->id, 'id');

            $mail_data = [
                'evaluator_first_name' => $evaluator->first_name,
                'evaluator_last_name'  => $evaluator->last_name,
                'player_first_name'    => $user->first_name,
                'player_last_name'     => $user->last_name,
            ];

            // Mail::to($evaluator->email)->send(new NotifyEvaluator($mail_data));
        }
    }

    /**
     * @param $data
     * @return array
     */
    public function getAssessmentSkillValues($data)
    {
        $page               = checkEmpty($data, 'page', 0);
        $skill_id           = checkEmpty($data, 'skill_id', 0);
        $category_id        = checkEmpty($data, 'category_id', 0);
        $player_position_id = checkEmpty($data, 'player_position_id', 0);

        $limit  = 15;
        $offset = $page * $limit;

        $assessment_skill_values = $this->prc_assessment_skill_value->with([
            'assessment_statements',
            'skill',
            'skill.assessment_category',
            'skill.assessment_category.player_position',
        ]);

        if ($skill_id > 0) {
            $assessment_skill_values = $assessment_skill_values->where('skill_id', $skill_id);
        }

        if ($category_id > 0) {
            $assessment_skill_values = $assessment_skill_values->whereHas('skill', function ($query) use ($category_id) {
                $query->where('category_id', $category_id);
            });
        }

        if ($player_position_id > 0) {
            $assessment_skill_values = $assessment_skill_values->whereHas('skill.assessment_category', function ($query) use ($player_position_id) {
                $query->where('player_position_id', $player_position_id);
            });
        }

        $total_rows = $assessment_skill_values->get()->count();

        $assessment_skill_values = $assessment_skill_values
            ->skip($offset)
            ->take($limit)
            ->orderBy('prc_advance_assessment_values.id')
            ->get([
                'prc_advance_assessment_values.id',
                'prc_advance_assessment_values.rating',
                'prc_advance_assessment_values.key_word',
                'prc_advance_assessment_values.rubric_classification',
                'prc_advance_assessment_values.skill_id',

            ]);

        $assessment_data['total_rows']                  = $total_rows;
        $assessment_data['assessment_skill_value_data'] = $assessment_skill_values;

        return $assessment_data;
    }

    /**
     * @param $category_id
     * @return mixed
     */
    public function assessmentSkillOfCategory($category_id)
    {
        return $this->prc_assessment_skill->where('category_id', $category_id)
            ->get([
                'id',
                'skill_name'
            ]);
    }

    /**
     * @param $value_id
     * @return mixed
     */
    public function getAssessmentStatementsBySkill($value_id)
    {
        return $this->prc_assessment_skill_value_statements->where('assessment_value_id', $value_id)
            ->orderBy('id')
            ->get();
    }

    /**
     * @param $data
     */
    public function manageAssessmentStatement($data)
    {
        if ($data['statement_id'] > 0) {
            $this->prc_assessment_skill_value_statements->where('id', $data['statement_id'])
                ->update([
                    'statement' => $data['statement']
                ]);
        } else {
            $this->prc_assessment_skill_value_statements->create([
                'assessment_value_id' => $data['assessment_value_id'],
                'statement'           => $data['statement']
            ]);
        }
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
                // "game"                 => $data['game'],
                "skills"               => $data['skills'],
                // "modified_skills"      => (gettype($data['modified_skills']) == 'array') ? json_encode($data['modified_skills']) : $data['modified_skills'],
                "long_range_potential" => $data['long_range_potential'],
                "scout_comment"        => $data['scout_comment'],
                "recommendation"       => $data['recommendation'],
                "published"            => $data['published'],
                "scout_request_id"     => $data['scout_request_id'],
                "rating"               => checkEmpty($data, 'rating', 0)
            ]);
        } else {
            if (!$data['published']) {
                $scouting_report->player_user_id       = $data['player_id'];
                $scouting_report->scout_user_id        = $scout->id;
                // $scouting_report->game                 = $data['game'];
                $scouting_report->skills               = $data['skills'];
                // $scouting_report->modified_skills      = json_encode($data['modified_skills']);
                $scouting_report->long_range_potential = $data['long_range_potential'];
                $scouting_report->scout_comment        = $data['scout_comment'];
                $scouting_report->recommendation       = $data['recommendation'];
            }
            $scouting_report->published = $data['published'];
            $scouting_report->rating    = checkEmpty($data, 'rating', 0);


            $scouting_report->save();
        }

        $report_data['id']    = $scouting_report->id;
        $report_data['saved'] = checkReportSaved($scouting_report->id, $scout->id);

        $prc_scout_request = $this->prc_scout_request->where('id', $data['scout_request_id'])->first();

        $prc_scout_request->status = 5;
        $prc_scout_request->save();

        $email_data = [
            'player_name' => $scouting_report->player->first_name . " " . $scouting_report->player->last_name
        ];

        try {
            Mail::to($scouting_report->player->email)->send(new PlayerNotifyAboutEvaluationRequestCompletedMail($email_data));
        } catch (Exception $e) {
            Log::info("Something went wrong in sending notification about report completed to email -> " . $scouting_report->player->email);
        }

        return $report_data;
    }

    /**
     * @param $data
     * @param $token
     * @return mixed
     * @throws Exception
     */
    public function generatePaymentPage($data, $token)
    {
        $user = getUserInfo($token, 'token', true);

        $zoho_helper = createZohoClassObject();

        if (empty($user->zoho_customer_id)) {
            $user->zoho_customer_id = $zoho_helper->createCustomer($user);
            $user->save();
        }

        if (empty($data['type'])) {
            $league   = $data['league'];
            $media_id = $data['media_id'];

            $scout_request = $this->prc_scout_request->where('source_user_id', $user->id)
                ->where('scout_user_id', '!=', 0)
                ->orderBy('id', 'DESC')
                ->first();

            if (!empty($scout_request) && in_array($scout_request->status, [1, 2, 5])) {
                throw new Exception(__('messages.already_assessment_request_sent'), 200);
            }

            $assessment_request = $this->prc_scout_request->create([
                'source_user_id' => $user->id,
                'scout_user_id'  => 0,
                'media_id'       => $media_id,
                'league_id'      => $league,
                'status'         => 1
            ]);

            return $zoho_helper->createPaymentPage($user->zoho_customer_id, ZOHO_EVALUATION_PLAN, $assessment_request->id);
        }
        if ($data['type'] == 1) {
            return $zoho_helper->createPaymentPage($user->zoho_customer_id, ZOHO_ONE_TO_ONE_CALL_PLAN, 0, true);
        }
        return $zoho_helper->createMentorshipPaymentPage($user->zoho_customer_id, ZOHO_MENTORSHIP_PLAN);
    }

    public function saveSubscription($data)
    {
        $zoho_helper         = createZohoClassObject();
        $hosted_page_details = $zoho_helper->getHostedPageDetail($data['hosted_page_id']);

        $user = getUserIdAndType($hosted_page_details->customer_id, 'zoho_customer_id');

        $this->prc_one_time_subscription->create([
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
}
