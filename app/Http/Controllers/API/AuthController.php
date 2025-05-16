<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\ChangePasswordRequest;
use App\Http\Requests\API\EditProfileRequest;
use App\Http\Requests\API\ForgotPasswordRequest;
use App\Http\Requests\API\LoginRequest;
use App\Http\Requests\API\RegisterRequest;
use App\Http\Requests\API\ResetPasswordRequest;
use App\Http\Requests\API\VerifyOTPRequest;
use App\Http\Requests\API\VerifyTokenRequest;
use App\Mail\ForgotMail;
use App\Mail\NotifyGuardian;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Class AuthController
 * @package App\Http\Controllers\API
 */
class AuthController extends Controller
{
    /**
     * @OA\Post(
     * path="/login",
     * summary="Sign in",
     * description="Login by email, password",
     * operationId="authLogin",
     * tags={"Authentication"},
     * @OA\RequestBody(
     *    required=true,
     *    description="Pass user credentials",
     *    @OA\JsonContent(
     *       required={"email","password"},
     *       @OA\Property(property="email", type="string", format="email", example="user1@mail.com"),
     *       @OA\Property(property="password", type="string", format="password", example="PassWord12345"),
     *       @OA\Property(property="request_from", type="string", format="text", example="admin")
     *    ),
     * ),
     * @OA\Response(
     *    response=422,
     *    description="",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Wrong email or password.")
     *        )
     *     )
     * )
     */
    public function login(LoginRequest $request)
    {
        try {
            $credentials = [
                'email'    => Str::lower($request->email),
                'password' => $request->password
            ];

            $checkUser = User::whereRaw('lower(email) = ? ', [$credentials['email']])->first();

            if ($checkUser && Hash::check($credentials['password'], $checkUser->password)) {
                $user = User::with(['current_subscription'])->find($checkUser->id);

                if ($user->status != 'Active' && $user->status != 'Hidden') {
                    if ($user->status === 'Parent Approval Pending') {
                        return prepare_response(200, false, __('messages.waiting_for_guardian_approval'));
                    }
                    if ($user->status === 'Deleted') {
                        return prepare_response(200, false, __('messages.user_deleted'));
                    }
                    return prepare_response(200, false, __('messages.account_not_active'));
                }

                /* if (!$user->is_email_verified) {
                    return prepare_response(200, false, __('messages.email_not_verified'));
                } */

                if (empty($request->request_from) && ($user->type == 1 || $user->type == 8)) {
                    throw new Exception(__('messages.unauthorized_access'), 200);
                }

                $user->makeVisible(['token']);
                $data = $user->toArray();

                if (empty($user->token)) {
                    $token       = generateToken();
                    $user->token = $token;
                    $user->save();

                    $data = array_merge($data, ["token" => $token]);
                }
                $data['followers_count'] = followersCount($data['id']);
                $data['following_count'] = followingCount($data['id']);
                $data['type']            = getUserType($data['type']);

                $data['country'] = $user->country_id ? $user->countryR->country_name : null;
                $data['state'] = $user->state_id ? $user->stateR->state_name : null;
                $data['city'] = $user->city_id ? $user->cityR->city_name : null;
                $data['country_flag'] = $user->country_id ? $user->countryR->country_flag : null;

                return prepare_response(200, true, __('messages.login_success'), $data);
            } else {
                return prepare_response(401, false, __('messages.invalid_credentials'));
            }
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }

    /**
     * @OA\Post(
     * path="/register",
     * summary="Sign up",
     * description="Register with PRC",
     * operationId="register",
     * tags={"Authentication"},
     * @OA\RequestBody(
     *    required=true,
     *    description="Pass user details",
     *    @OA\JsonContent(
     *       required={"first_name","last_name","email","password","type", "dob"},
     *       @OA\Property(property="first_name", type="string", example="Test"),
     *       @OA\Property(property="last_name", type="string", example="User"),
     *       @OA\Property(property="email", type="string", format="email", example="user1@mail.com"),
     *       @OA\Property(property="password", type="string", format="password", example="PassWord12345"),
     *       @OA\Property(property="dob", type="string", example="1990-04-04"),
     *       @OA\Property(property="guardian_email", type="string", format="email", example="parent@mail.com"),
     *       @OA\Property(property="type", type="integer", example="1")
     *    ),
     * ),
     * @OA\Response(
     *    response=422,
     *    description="",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Wrong email or password.")
     *        )
     *     )
     * )
     */
    public function register(RegisterRequest $request)
    {
        DB::beginTransaction();
        try {
            $request_data = $request->all();

            $request_data['password'] = Hash::make($request_data['password']);
            $request_data['position'] = 1;
            $request_data['token']    = generateToken();
            $request_data['type']     = strtolower($request_data['type']);
            $request_data['sub_type'] = (!empty($request_data['sub_type'])) ? strtolower($request_data['sub_type']) : '';
            $request_data['status']   = "Pending";

            switch ($request_data['type']) {
                case 'player':
                    $request_data['type'] = 2;
                    break;
                case 'evaluator':
                    $request_data['type']   = 3;
                    $request_data['league'] = 1;
                    break;
                case 'team':
                    $request_data['type'] = 4;
                    break;
                case 'scout':
                    $request_data['type'] = 7;
                    break;
                case 'fan':
                    $request_data['type'] = 6;
                    break;
                case 'academy':
                    $request_data['type'] = 5;
                    break;
                case 'parent':
                    $request_data['type'] = 9;
                    break;
            }

            if (in_array($request_data['type'], [2, 6, 7, 9])) {
                if (empty($request_data['guardian_email'])) {
                    $request_data['status'] = "Active";
                } else {
                    $token              = md5(md5(generateToken()));
                    $mail_data['name']  = rtrim($request_data['first_name'] . ' ' . $request_data['last_name']);
                    $mail_data['token'] = $token;

                    Mail::to($request_data['guardian_email'])->send(new NotifyGuardian($mail_data));

                    $request_data['guardian_token'] = $token;
                }
            }

            $user = User::create($request_data);

            $welcome_email_data = [
                'username' => $user->first_name . " " . $user->last_name
            ];

            Mail::to($user->email)->send(new WelcomeMail($welcome_email_data));

            if ($request_data['type'] == 3) {
                $email_data = [
                    'evaluator_name' => $user->first_name . " " . $user->last_name
                ];

                Mail::to($user->email)->send(new EvaluatorRequestIsBeingVettedMail($email_data));
            }

            $team_member = PrcTeamMember::where('email', $request_data['email'])->first();

            if (!empty($team_member)) {
                $team_member->user_id = $user->id;
                $team_member->save();
            }

            DB::commit();
            if ((in_array($user->type, [2, 6, 7, 9]) && empty($user->guardian_email))) {
                $user = getUserInfo($user->token);
                $user->makeVisible(['token']);
                $user->followers_count      = 0;
                $user->following_count      = 0;
                $user->current_subscription = null;
                $user->type                 = getUserType($user->type);
                return prepare_response(200, true, __('messages.register_success'), $user->toArray());
            }
            return prepare_response(201, true, __('messages.waiting_for_approval'));
        } catch (Exception $e) {
            DB::rollBack();
            return exceptionMessage($e);
        }
    }

    /**
     * @OA\Post(
     * path="/forgot-password",
     * summary="Forgot Password",
     * description="Get OTP on registed mail",
     * operationId="forgotPassword",
     * tags={"Authentication"},
     * @OA\RequestBody(
     *    required=true,
     *    description="Pass register email",
     *    @OA\JsonContent(
     *       required={"email"},
     *       @OA\Property(property="email", type="string", format="email", example="user1@mail.com")
     *    ),
     * ),
     * @OA\Response(
     *    response=200,
     *    description="",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="OTP sent to your email, Please check.")
     *        )
     *     )
     * )
     */
    public function forgotPassword(ForgotPasswordRequest $request)
    {
        DB::beginTransaction();
        try {
            $user = User::where('email', $request->email)->first();

            if (!empty($user)) {
                REGENERATE:
                $otp = rand(pow(10, 6 - 1), pow(10, 6) - 1);

                $is_otp_available = User::where('password_reset_pin', $otp)->first();

                if (!empty($is_otp_available)) {
                    goto REGENERATE;
                }

                $mail_data['username'] = $user->first_name . " " . $user->last_name;
                $mail_data['otp']      = $otp;

                Mail::to($request->email)->send(new ForgotMail($mail_data));

                $user->password_reset_pin = $otp;
                $user->save();
                DB::commit();
                return prepare_response(200, true, __('messages.otp_send'));
            }
            DB::rollBack();
            return prepare_response(400, false, __('User does not exist'));
        } catch (Exception $e) {
            DB::rollBack();
            return exceptionMessage($e);
        }
    }

    /**
     * @OA\Post(
     * path="/reset-password",
     * summary="Reset Password",
     * description="Reset Password using OTP",
     * operationId="resetPassword",
     * tags={"Authentication"},
     * @OA\RequestBody(
     *    required=true,
     *    description="Pass OTP and New Password",
     *    @OA\JsonContent(
     *       required={"otp", "password"},
     *       @OA\Property(property="otp", type="integer", example="123456"),
     *       @OA\Property(property="password", type="string", format="password", example="123456")
     *    ),
     * ),
     * @OA\Response(
     *    response=200,
     *    description="",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Password reset successfully.")
     *        )
     *     )
     * )
     */
    public function resetPassword(ResetPasswordRequest $request)
    {
        DB::beginTransaction();
        try {
            $user = User::where('password_reset_pin', $request->otp)->first();

            if (empty($user)) {
                return prepare_response(200, false, "Invalid OTP.");
            }

            $user->password_reset_pin = null;
            $user->password           = Hash::make($request->password);
            $user->token              = null;
            $user->save();
            DB::commit();
            return prepare_response(200, true, __('messages.password_reset'));
        } catch (Exception $e) {
            DB::rollBack();
            return exceptionMessage($e);
        }
    }

    /**
     * @OA\Get (
     * path="/get-profile",
     * summary="Get Profile",
     * description="Retrieve user's profile",
     * operationId="getProfile",
     * tags={"Authentication"},
     * security={{"apiAuth":{}}},
     * @OA\Response(
     *    response=200,
     *    description="",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Profile retrieved.")
     *        )
     *     )
     * )
     */
    public function getProfile(Request $request)
    {
        try {
            $user = getUserInfo($request->header('Authorization'), 'token', true, ['current_subscription', 'medias']);

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

            $user->uploaded_media_count = 0;

            if (!empty($user->medias)) {
                $user->uploaded_media_count = count($user->medias);
            }

            unset($user->medias);

            $user->league          = checkEmpty($user->player_league, 'league_name', '');
            $user->type            = getUserType($user->type);
            $user->followers_count = followersCount($user->id);
            $user->following_count = followingCount($user->id);

            if ($user->type == 'academy') {
                $team_players = [
                    'premium' => [],
                    'basic'   => []
                ];

                foreach ($user->team_players as $team_player) {

                    if (empty($team_player->current_subscription)) {
                        $team_players['basic'][] = $team_player;
                    } else {
                        $current_date = Carbon::now();
                        $renew_date   = $team_player->current_subscription->renew_on;

                        if ($current_date->lte($renew_date) || !$team_player->current_subscription->is_cancelled) {
                            $team_players['premium'][] = $team_player;
                        }
                    }
                }

                unset($user->team_players);
                $user->team_players = $team_players;
            }

            $check_request = checkScoutingRequestStatus($user->id);
            $user->premium = [
                'last_evaluation_status'     => $check_request['status'],
                'last_evaluation_request_id' => $check_request['request_id']
            ];
            $user->makeVisible(['token']);
            return prepare_response(200, true, __('messages.profile_retrieve'), $user);
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }

    /**
     * @OA\Put (
     * path="/edit-profile",
     * summary="Edit Profile",
     * description="Update profile",
     * operationId="editProfile",
     * tags={"Authentication"},
     * security={{"apiAuth":{}}},
     * @OA\RequestBody(
     *    required=true,
     *    description="Pass user details",
     *    @OA\JsonContent(
     *       required={"first_name","last_name"},
     *       @OA\Property(property="first_name", type="string", example="Test"),
     *       @OA\Property(property="last_name", type="string", example="User"),
     *       @OA\Property(property="phone", type="string", example="+917894561230"),
     *       @OA\Property(property="dob", type="string", example="1990-04-04"),
     *       @OA\Property(property="handedness", type="string", example="LH"),
     *       @OA\Property(property="weight", type="string", example="30"),
     *       @OA\Property(property="height", type="string", example="6'5''"),
     *       @OA\Property(property="profile_picture", type="string", example="base64 string of image"),
     *       @OA\Property(property="league", type="string", example="Json string of league"),
     *       @OA\Property(property="position", type="integer", example="1"),
     *       @OA\Property(property="city", type="string", example="Ahmedabad"),
     *       @OA\Property(property="region", type="string", example="Gujarat"),
     *       @OA\Property(property="country", type="string", example="India"),
     *       @OA\Property(property="country_short_name", type="string", example="IN"),
     *       @OA\Property(property="marketplace_email_allowed", type="boolean", example=true)
     *    ),
     * ),
     * @OA\Response(
     *    response=200,
     *    description="",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Profile updated.")
     *        )
     *     )
     * )
     */
    public function editProfile(EditProfileRequest $request)
    {
        DB::beginTransaction();
        try {
            $token        = $request->header('Authorization');
            $profile_data = $request->all();

            $user = User::where('token', $token)->first();

            $user->first_name                = checkEmpty($profile_data, 'first_name', '');
            $user->last_name                 = checkEmpty($profile_data, 'last_name', '');
            $user->phone                     = checkEmpty($profile_data, 'phone', $user->phone);
            $user->dob                       = checkEmpty($profile_data, 'dob', $user->dob);
            $user->position                  = checkEmpty($profile_data, 'position', $user->position);
            $user->handedness                = checkEmpty($profile_data, 'handedness', $user->handedness);
            $user->weight                    = checkEmpty($profile_data, 'weight', $user->weight);
            $user->height                    = checkEmpty($profile_data, 'height', $user->height);
            if (!empty($profile_data['profile_picture'])) {
                try {

                    Storage::disk('s3')->delete(parse_url($user->s3_profile_picture, PHP_URL_PATH));

                    $image_data = base64_decode($profile_data['profile_picture']);

                    $imageName = env("PROFILE_PICTURE_FOLDER") . 'image_' . time() . '.' . 'png';

                    Storage::disk('s3')->put($imageName, $image_data);

                    $s3 = Storage::disk('s3')->getAdapter()->getClient();
                    $url = $s3->getObjectUrl(env('AWS_BUCKET'), $imageName);

                    $user->s3_profile_picture = $url;
                } catch (Exception $e) {
                    Log::info("Something went wrong uploading image to user -> " . $user['email']);
                }
            }
            // $user->profile_picture           = checkEmpty($profile_data, 'profile_picture', $user->profile_picture);
            $user->league                    = checkEmpty($profile_data, 'league', $user->league);
            $user->city_id                   = checkEmpty($profile_data, 'city_id', $user->city_id);
            $user->state_id                  = checkEmpty($profile_data, 'state_id', $user->state_id);
            $user->country_id                = checkEmpty($profile_data, 'country_id', $user->country_id);
            $user->city                      = checkEmpty($profile_data, 'city', $user->city_id ? $user->cityR->city_name : null);
            $user->region                    = checkEmpty($profile_data, 'region', $user->state_id ? $user->stateR->state_name : null);
            $user->country                   = checkEmpty($profile_data, 'country', $user->country_id ? $user->countryR->country_name : null);
            $user->country_short_name        = checkEmpty($profile_data, 'country_short_name', $user->country_id ? $user->countryR->short_name_2_digit : '');
            $user->marketplace_email_allowed = checkEmpty($profile_data, 'marketplace_email_allowed', $user->marketplace_email_allowed);
            $user->save();
            DB::commit();
            return prepare_response(200, true, __('messages.profile_update_success'), $user, [], "1.0");
        } catch (Exception $e) {
            DB::rollBack();
            return exceptionMessage($e);
        }
    }

    /**
     * @OA\Post(
     * path="/change-password",
     * summary="change Password",
     * description="change Password using OTP",
     * operationId="changePassword",
     * tags={"Authentication"},
     * security={{"apiAuth":{}}},
     * @OA\RequestBody(
     *    required=true,
     *    description="Pass Old and New Password",
     *    @OA\JsonContent(
     *       required={"old_password", "new_password"},
     *       @OA\Property(property="old_password", type="string", format="password", example="123456"),
     *       @OA\Property(property="new_password", type="string", format="password", example="123456")
     *    ),
     * ),
     * @OA\Response(
     *    response=200,
     *    description="Wrong credentials response",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Password reset successfully.")
     *        )
     *     )
     * )
     */
    public function changePassword(ChangePasswordRequest $request)
    {
        DB::beginTransaction();
        try {
            $user = getUserInfo($request->header('Authorization'));

            if (!Hash::check($request->old_password, $user->password)) {
                return prepare_response(200, false, __('messages.current_password_wrong'));
            }

            $user->password = Hash::make($request->new_password);
            $user->token    = null;
            $user->save();
            DB::commit();
            return prepare_response(200, true, __('messages.password_reset'));
        } catch (Exception $e) {
            DB::rollBack();
            return exceptionMessage($e);
        }
    }

    /**
     * @OA\Get (
     * path="/verify-token",
     * summary="Verify Token",
     * description="Check that token is valid or not",
     * operationId="verifyToken",
     * tags={"Authentication"},
     * security={{"apiAuth":{}}},
     * @OA\Parameter(
     *    description="Email",
     *    in="query",
     *    name="email",
     *    required=true,
     *    example="user1@mail.com",
     *    @OA\Schema(
     *       type="string",
     *       format="email"
     *    )
     * ),
     * @OA\Response(
     *    response=200,
     *    description="",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Valid token!")
     *        )
     *     )
     * )
     */
    public function verifyToken(VerifyTokenRequest $request)
    {
        try {
            $email = $request->get('email');
            $email = str_replace(" ", "+", $email);

            $user = getUserInfo($request->header('Authorization'));

            if ((empty($user)) || $user->email != $email) {
                return prepare_response(200, false, 'Invalid token!');
            }
            $user['followers_count'] = followersCount($user->id);
            $user['following_count'] = followingCount($user->id);
            $user->makeVisible(['token']);
            $user->type = getUserType($user->type);
            return prepare_response(200, true, __('messages.valid_token'), $user);
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }

    /**
     * @OA\Get (
     * path="/verify-otp",
     * summary="Verify OTP",
     * description="Check that OTP is valid or not",
     * operationId="verifyOTP",
     * tags={"Authentication"},
     * @OA\Parameter(
     *    description="Email",
     *    in="query",
     *    name="email",
     *    required=true,
     *    example="user1@mail.com",
     *    @OA\Schema(
     *       type="string",
     *       format="email"
     *    )
     * ),
     * @OA\Parameter(
     *    description="OTP",
     *    in="query",
     *    name="otp",
     *    required=true,
     *    example="123456",
     *    @OA\Schema(
     *       type="string",
     *       format="text"
     *    )
     * ),
     * @OA\Response(
     *    response=200,
     *    description="",
     *    @OA\JsonContent(
     *       @OA\Property(property="message", type="string", example="Valid OTP!")
     *        )
     *     )
     * )
     */
    public function verifyOTP(VerifyOTPRequest $request)
    {
        try {
            $email = $request->get('email');
            $email = str_replace(" ", "+", $email);
            $otp   = $request->otp;

            $user = User::where('email', $email)->where('password_reset_pin', $otp)->first();

            if (empty($user)) {
                return prepare_response(200, false, 'Invalid OTP!');
            }

            return prepare_response(200, true, __('messages.valid_otp'));
        } catch (Exception $e) {
            return exceptionMessage($e);
        }
    }
}
