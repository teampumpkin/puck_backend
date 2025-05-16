<?php

namespace App\Http\Controllers;

use App\Mail\EvaluatorRequestIsBeingVettedMail;
use App\Mail\NotifyGuardian;
use App\Mail\WelcomeMail;
use App\Models\PrcTeamMember;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Class AuthController
 * @package App\Http\Controllers\API
 */
class AuthController extends Controller
{
    public function verifyAccount($token)
    {
        DB::beginTransaction();
        try {
            if (empty($token)) {
                throw new Exception(__('messages.general_exception_message'), 200);
            }

            $user = getUserInfo($token, 'email_token', false);

            if (empty($user)) {
                throw new Exception(__('messages.user_info_not_found'), 200);
            }

            $user->is_email_verified = 1;
            $user->email_verified_at = Carbon::now();
            $user->email_token       = null;
            $status                  = "Active";
            $can_sent_welcome_email  = true;
            if ($user->type == 2 && !empty($user->guardian_email)) {
                $token              = md5(md5(generateToken()));
                $mail_data['name']  = rtrim($user->first_name . ' ' . $user->last_name);
                $mail_data['token'] = $token;

                Mail::to($user->guardian_email)->send(new NotifyGuardian($mail_data));
                $status = "Parent Approval Pending";

                $user->guardian_token   = $token;
                $can_sent_welcome_email = false;
            }

            if ($user->type == 3) {
                $email_data = [
                    'evaluator_name' => $user->first_name . " " . $user->last_name
                ];
                $status     = "Pending";
                Mail::to($user->email)->send(new EvaluatorRequestIsBeingVettedMail($email_data));
            }

            $team_member = PrcTeamMember::where('email', $user->email)->first();

            if (!empty($team_member)) {
                $team_member->user_id = $user->id;
                $team_member->save();
            }

            $user->status = $status;
            $user->save();
            DB::commit();

            if ($can_sent_welcome_email) {
                $welcome_email_data = [
                    'username' => $user->first_name . " " . $user->last_name
                ];

                Mail::to($user->email)->send(new WelcomeMail($welcome_email_data));
            }

            $details['name'] = trim($user->first_name . " " . $user->last_name);

            return view('verified', compact('details'));
        } catch (Exception $e) {
            DB::rollBack();
            $message = ($e->getCode() === 200) ? $e->getMessage() : __('messages.general_exception_message');
            return view('error', compact('message'));
        }
    }
}
