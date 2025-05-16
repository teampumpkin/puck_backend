<?php
namespace App\Repositories\V2;

use App\Models\PrcAdvanceAssessmentValue;
use App\Models\PrcReport;
use App\Models\PrcScoutRequest;
use Exception;

class V2EvaluationRepository {
	/**
     * @var PrcScoutRequest
     */
    private $prc_scout_request;
    /**
    * @var PrcReport
    */
    private $prc_report;
    /**
     * @var PrcAdvanceAssessmentValue
     */
    private $prc_advance_assessment_value;

	public function __construct() {
		$this->prc_scout_request = new PrcScoutRequest();
        $this->prc_report = new PrcReport();
        $this->prc_advance_assessment_value = new PrcAdvanceAssessmentValue();
	}

	public function getScoutRequests($token, $data = [])
    {
        $pageSize = !empty($data['page_size']) ? $data['page_size'] : 10;
        $user = getUserIdAndType($token);

        if (($user->type === 1 || $user->type === 8) && !empty($request['admin'])) {
            $scouting_requests = $this->prc_scout_request->with(['player', 'evaluator', 'playable', 'report', 'player.player_league', 'player.player_position', 'media'])->orderBy('id', 'DESC')->paginate($pageSize);
        }else{
            $scouting_requests = $this->prc_scout_request->with(['player', 'player.player_position', 'playable', 'report', 'media'])->where('scout_user_id',
            $user->id)->orderBy('id', 'DESC')->paginate($pageSize);
        }

        foreach ($scouting_requests as $evaluation) {
            createEvaluationObject($evaluation);
        }

        $result = $scouting_requests->toArray();

        unset($result['links']);

        return $result;
    }

    public function getReports($token, $data = [])
    {
        $pageSize = !empty($data['page_size']) ? $data['page_size'] : 10;
        $user = getUserIdAndType($token);

        $reports = $this->prc_report
            ->with(['player'])
            ->where('published', true)
            ->where(($user->type == 2 ? 'player_user_id' : 'scout_user_id'), $user->id)
            ->orderBy('created_at', 'DESC')
            ->paginate($pageSize);

        if (empty($reports->toArray())) {
            throw new Exception(__('messages.no_report_available'), 200);
        }

        foreach ($reports as $report) {
            createReportObject($report);
        }

        $result = $reports->toArray();

        unset($result['links']);

        return $result;
    }

    public function getReport($token, $report_id)
    {
        $user = getUserIdAndType($token);

        $report = $this->prc_report->with(['player', 'player.player_position', 'scout', 'scout_request', 'scout_request.playable', 'scout_request.media'])
            ->where(($user->type == 2 ? 'player_user_id' : 'scout_user_id'), $user->id)
            ->where('id', $report_id)
            ->first();

        if (empty($report)) {
            throw new Exception(__('messages.invalid_report_id'), 200);
        }

        $report->saved = checkReportSaved($report->id, $user->id);
        $report['skills'] = json_decode($report["skills"]);

        // Get ratings and skill ids
        $skillRatings = [];
        foreach ($report["skills"] as $skill) {
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
        foreach ($report["skills"] as $skill) {
            foreach ($skill->data as $data) {
                $key = $data->id . '_' . $data->setRating;
                $data->evaluation = $evaluationData[$key] ?? '';
            }
        }

        createReportDetailObject($report);

        return $report;
    }
}