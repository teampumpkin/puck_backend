<?php

namespace App\Http\Controllers\API\V2;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\RegisterEvaluatorRequest;
use App\Http\Requests\API\V2RegisterRequest;
use App\Mail\LoginDetails;
use App\Mail\VerificationMail;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

class V2AuthController extends Controller
{
    /**
     * @OA\Post(
     * path="/v2/register",
     * summary="Sign up",
     * description="Register with PRC",
     * operationId="registerV2",
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
     *       @OA\Property(property="guardian_first_name", type="string", example="parent"),
     *       @OA\Property(property="guardian_email", type="string", format="email", example="parent@mail.com"),
     *       @OA\Property(property="type", type="integer", example="1"),
     *       @OA\Property(property="marketplace_email_allowed", type="boolean", example=true)
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
    public function register(V2RegisterRequest $request)
    {
        DB::beginTransaction();
        try {
            $request_data = $request->all();
            $request_data['email'] = Str::lower($request_data['email']);

            $age = Carbon::parse($request_data['dob'])->age;
            if ($age < 14) {
                return prepare_response(400, false, __('messages.below_age'));
            }

            $checkEmail = User::whereRaw('lower(email) = ? ', [$request_data['email']])->first();

            if ($checkEmail) {
                return prepare_response(400, false, __('messages.email_taken'));
            }

            $request_data['marketplace_email_allowed'] = checkEmpty($request_data, 'marketplace_email_allowed', true);
            $request_data['password']                  = Hash::make($request_data['password']);
            $request_data['token']                     = generateToken();
            $request_data['type']                      = strtolower($request_data['type']);
            $request_data['sub_type']                  = (!empty($request_data['sub_type'])) ? strtolower($request_data['sub_type']) : '';
            $request_data['city']                      = !empty($request_data['city_id']) ? getCity($request_data['city_id']) : null;
            $request_data['region']                    = !empty($request_data['state_id']) ? getState($request_data['state_id']) : null;
            $request_data['country']                   = !empty($request_data['country_id']) ? getCountry($request_data['country_id']) : null;
            $request_data['country_short_name']        = !empty($request_data['country_id']) ? getCountryShortName($request_data['country_id']) : '';
            $request_data['status']                    = "Active"; //Active or Pending

            if ($age >= 14 && $age <= 16) {
                $request_data['status'] = "Pending";
            }

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
            $request_data['is_email_verified'] = 0;

            if (!empty($request_data['profile_picture'])) {
                try {
                    $image_data = base64_decode($request_data['profile_picture']);

                    $imageName = 'image_' . time() . '.' . 'png';

                    Storage::disk('s3')->put($imageName, $image_data);

                    $s3 = Storage::disk('s3')->getAdapter()->getClient();
                    $url = $s3->getObjectUrl(env('AWS_BUCKET'), env("PROFILE_PICTURE_FOLDER") . $imageName);

                    $request_data['profile_picture'] = null;

                    $request_data['s3_profile_picture'] = $url;
                } catch (Exception $e) {
                    Log::info("Something went wrong uploading image to user -> " . $request_data['email']);
                }
            }

            $user                              = User::create($request_data);

            $email_token = generateToken();

            $user->email_token = $email_token;
            $user->save();

            $email_data = [
                'token' => $email_token
            ];

            try {
                Mail::to($user->email)->send(new VerificationMail($email_data));
            } catch (Exception $e) {
                Log::info("Something went wrong in sending verification email to email -> " . $user->email);
            }
            DB::commit();

            return prepare_response(201, true, __('messages.register_success'));
        } catch (Exception $e) {
            DB::rollBack();
            return exceptionMessage($e);
        }
    }

    public function registerEvaluator(RegisterEvaluatorRequest $request)
    {
        DB::beginTransaction();
        try {
            $request_data = $request->all();
            $request_data['email'] = Str::lower($request_data['email']);

            /* $age = Carbon::parse($request_data['dob'])->age;
            if ($age < 18) {
                return prepare_response(400, false, __('messages.at_least_18_age'));
            } */

            $checkEmail = User::whereRaw('lower(email) = ? ', [$request_data['email']])->first();

            if ($checkEmail) {
                return prepare_response(400, false, __('messages.email_taken'));
            }

            $pass = Str::random(8);

            $request_data['marketplace_email_allowed'] = checkEmpty($request_data, 'marketplace_email_allowed', true);
            $request_data['password']                  = Hash::make($pass);
            $request_data['token']                     = generateToken();
            $request_data['type']                      = 3;
            $request_data['status']                    = "Active"; //Active or Pending

            $request_data['is_email_verified'] = 1;
            $user                              = User::create($request_data);

            $user->save();

            $login_details = [
                'email' => $user->email,
                'password' => $pass
            ];

            try {
                Mail::to($user->email)->send(new LoginDetails($login_details));
            } catch (Exception $e) {
                Log::info("Something went wrong in sending LoginDetails email to email -> " . $user->email);
            }

            DB::commit();

            return prepare_response(201, true, __('messages.register_success'));
        } catch (Exception $e) {
            DB::rollBack();
            return exceptionMessage($e);
        }
    }
}
