<?php

namespace App\Repositories;

use App\Mail\ParentApprovalMail;
use App\Mail\ParentDeclineMail;
use App\Mail\PlayerNotifyAboutGuardianAcceptedMail;
use Exception;
use Illuminate\Support\Facades\Mail;

/**
 * Class GuardianRepository
 * @package App\Repositories
 */
class GuardianRepository
{
    /**
     * GuardianRepository constructor.
     */
    public function __construct()
    {
    }

    /**
     * @param $token
     * @param $is_accepted
     * @throws Exception
     */
    public function takeAction($token, $is_accepted)
    {
        $child = getUserInfo($token, 'guardian_token', false);

        if (empty($child)) {
            throw new Exception(__('messages.request_token_already_used'), 200);
        }

        $child->status = 'Blocked';

        if ($is_accepted) {
            $child->status = 'Active';

            $email = new ParentApprovalMail();

            $email_data = [
                'username' => $child->first_name . " " . $child->last_name
            ];

            Mail::to($child->email)->send(new PlayerNotifyAboutGuardianAcceptedMail($email_data));
        } else {
            $email_data['name'] = $child->guardian_first_name . " " . $child->guardian_last_name;

            $email = new ParentDeclineMail($email_data);
        }

        Mail::to($child->guardian_email)->send($email);

        $child->guardian_token = "";
        $child->save();
    }
}
