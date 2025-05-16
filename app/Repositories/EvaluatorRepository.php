<?php

namespace App\Repositories;

use App\Mail\AcceptanceOfBecomingAPuckRecruiterEvaluatorMail;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Mail;

/**
 * Class EvaluatorRepository
 * @package App\Repositories
 */
class EvaluatorRepository
{
    /**
     * @var User
     */
    private $evaluator;

    /**
     * PlayerRepository constructor.
     */
    public function __construct()
    {
        $this->evaluator = new User();
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function getAllEvaluators()
    {
        $evaluators = $this->evaluator->where('type', 3)
            ->orderBy('id', 'DESC')
            ->get();

        $evaluators->makeHidden(['token', 'password_reset_pin']);
        $evaluators->makeVisible(['status']);

        if (empty($evaluators->toArray())) {
            throw new Exception(__('messages.no_evaluator_available'), 200);
        }

        return $evaluators;
    }

    /**
     * @param $data
     *
     * @return array
     */
    public function evaluatorStatusChange($data)
    {
        $evaluator = $this->evaluator->where('type', 3)
            ->where('id', $data['evaluator_id'])
            ->first();

        if (empty($evaluator)) {
            return [
                "status"  => false,
                "message" => __('messages.invalid_evaluator_id')
            ];
        }

        $evaluator->status = $data['status'];
        $evaluator->save();

        if ($data['status'] === 'Active') {
            $email_data = [
                'evaluator_name' => $evaluator->first_name . " " . $evaluator->last_name
            ];

            Mail::to($evaluator->email)->send(new AcceptanceOfBecomingAPuckRecruiterEvaluatorMail($email_data));
        }

        return [
            "status"  => true,
            "message" => __('messages.evaluator_status_update')
        ];
    }
}
