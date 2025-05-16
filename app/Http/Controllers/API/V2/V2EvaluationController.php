<?php

namespace App\Http\Controllers\API\V2;

use App\Http\Controllers\Controller;
use App\Repositories\V2\V2EvaluationRepository;
use Exception;
use Illuminate\Http\Request;

class V2EvaluationController extends Controller
{

    /**
     * @var V2EvaluationRepository
     */
    private $evaluationRepository;

    /**
     *
     */
    public function __construct()
    {
        $this->evaluationRepository = new V2EvaluationRepository();
    }

    public function getScoutRequest(Request $request)
    {
        try {
            $evaluations = $this->evaluationRepository->getScoutRequests($request->header('Authorization'), $request->all());

            if (empty($evaluations)) {
                return prepare_response(200, false, __('messages.no_assessment_request'), [], "2.0");
            }

            return prepare_response(200, true, __('messages.assessment_request_list'), $evaluations, [], "2.0");
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }

    public function getReports(Request $request)
    {
        try {
            $reports = $this->evaluationRepository->getReports($request->header('Authorization'), $request->all());

            return prepare_response(200, true, __('messages.assessment_report_list'), $reports, [], "2.0");
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }

    public function getReport(Request $request)
    {
        try {
            $report = $this->evaluationRepository->getReport($request->header('Authorization'), $request->report_id);

            return prepare_response(200, true, __('messages.assessment_report_detail'), $report, [], "2.0");
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }

}
