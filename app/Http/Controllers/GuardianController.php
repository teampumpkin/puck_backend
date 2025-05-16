<?php

namespace App\Http\Controllers;

use App\Repositories\GuardianRepository;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;

/**
 *
 */
class GuardianController extends Controller
{
    /**
     * @var GuardianRepository
     */
    private $guardian_repository;

    /**
     *
     */
    public function __construct()
    {
        $this->guardian_repository = new GuardianRepository();
    }

    /**
     * @param $token
     * @return Application|Factory|View
     */
    public function acceptRequest($token)
    {
        try {
            $this->guardian_repository->takeAction($token, true);
            $message = __('messages.guardian_accept_request');
            return view('status', compact('message'));
        } catch (Exception $e) {
            $message = __('messages.general_exception_message');

            if ($e->getCode() == 200) {
                $message = $e->getMessage();
            }
            return view('error', compact('message'));
        }

    }

    /**
     * @param $token
     * @return Application|Factory|View
     */
    public function rejectRequest($token)
    {
        try {
            $this->guardian_repository->takeAction($token, false);
            $message = __('messages.guardian_decline_request');
        } catch (Exception $e) {
            $message = "Something went wrong!";

            if ($e->getCode() == 200) {
                $message = $e->getMessage();
            }
        }
        return view('status', compact('message'));
    }
}
