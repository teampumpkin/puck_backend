<?php

namespace App\Http\Controllers\Api\Zapier;

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\V2\V2AuthController;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\EditProfileRequest;
use App\Http\Requests\API\V2RegisterRequest;
use App\Http\Requests\API\ZapierUserEditRequest;
use App\Models\City;
use App\Models\Country;
use App\Models\PrcPosition;
use App\Models\PrcUserType;
use App\Models\State;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ZapierController extends Controller
{

    /**
     * @OA\Post(
     * path="/zapier/register",
     * summary="Register user in Zapier (Warning: this also will create a user in PUCK)",
     * description="Register user in Zapier and Puck",
     * operationId="zapierRegister",
     * tags={"Zapier"},
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
     *       @OA\Property(property="type", type="integer", example="2"),
     *       @OA\Property(property="handedness", type="string", example="Left hand"),
     *       @OA\Property(property="weight", type="integer", example="60"),
     *       @OA\Property(property="height", type="string", example="5'6"),
     *       @OA\Property(property="marketplace_email_allowed", type="boolean", example=true),
     *       @OA\Property(property="is_email_verified", type="boolean", example=true)
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
    public function register(V2RegisterRequest $request) {
        return (new V2AuthController)->register($request);
    }

    /**
     * @OA\Put (
     * path="/zapier/edit",
     * summary="Edit user info (Warning: this also will update user in PUCK)",
     * description="This endpoint is called by Zapier when a record is update in the Zapier table. Endpoint will update user data in Puck and Zapier table",
     * operationId="editUserZapier",
     * tags={"Zapier"},
     * security={{"apiAuth":{}}},
     * @OA\RequestBody(
     *    required=true,
     *    description="Pass user details",
     *    @OA\JsonContent(
     *       required={"email"},
     *       @OA\Property(property="email", type="string", example="test@email.com"),
     *       @OA\Property(property="first_name", type="string", example="Test"),
     *       @OA\Property(property="last_name", type="string", example="User"),
     *       @OA\Property(property="dob", type="string", example="1990-04-04"),
     *       @OA\Property(property="city", type="string", example="Miami"),
     *       @OA\Property(property="state", type="string", example="Florida"),
     *       @OA\Property(property="country", type="string", example="United States"),
     *       @OA\Property(property="type", type="string", example="Player"),
     *       @OA\Property(property="position", type="string", example="Centre"),
     *       @OA\Property(property="handedness", type="string", example="Left hand"),
     *       @OA\Property(property="weight", type="integer", example="60"),
     *       @OA\Property(property="height", type="string", example="5'6"),
     *       @OA\Property(property="guardian_email", type="string", example=""),
     *       @OA\Property(property="marketplace_email_allowed", type="boolean", example=true),
     *       @OA\Property(property="is_email_verified", type="boolean", example=true)
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
    public function editUser(ZapierUserEditRequest $request) {
        $token = $request->header('Authorization');
        if ($token === null || $token === "") {
            return prepare_response(401, false, 'You have not access to this function');
        }
        if (env('ZAPIER_TOKEN') !== $token) {
            $admin = User::where('token', $token)->first();
            if (empty($admin) || ($admin->type != 1 && $admin->type != 8)){
                return prepare_response(401, false, 'You have not access to this function');
            }
        }
        DB::beginTransaction();
        try {
            $profile_data = $request->all();

            $user = User::where('email', $profile_data['email'])->first();

            if (empty($user)){
                return prepare_response(401, false, "User doesn't exist");
            }

            $user->first_name                = checkEmpty($profile_data, 'first_name', $user->first_name);
            $user->last_name                 = checkEmpty($profile_data, 'last_name', $user->last_name);
            $user->phone                     = checkEmpty($profile_data, 'phone', $user->phone);
            $user->dob                       = checkEmpty($profile_data, 'dob', $user->dob);
            $user->guardian_email            = checkEmpty($profile_data, 'guardian_email', $user->guardian_email);

            if (!empty($profile_data['type'])){
                $type = PrcUserType::where('type_name', 'ilike', '%' . $profile_data['type'] . '%')->first();
                if (!empty($type)){
                    $user->type                  = $type->id;
                }
            }

            if (!empty($profile_data['position'])){
                $position = PrcPosition::where('position_name', 'ilike', '%' . $profile_data['position'] . '%')->first();
                if (!empty($position)){
                    $user->position                  = $position->id;
                }
            }

            $user->handedness                = checkEmpty($profile_data, 'handedness', $user->handedness);
            $user->weight                    = checkEmpty($profile_data, 'weight', $user->weight);
            $user->height                    = checkEmpty($profile_data, 'height', $user->height);

            if (!empty($profile_data['city'])){
                $city = City::where('city_name', 'ilike', '%' . $profile_data['city'] . '%')->first();
                if (!empty($city)){
                    $user->city_id = $city->id;
                    $user->city = $city->city_name;
                }
            }
            if (!empty($profile_data['state'])){
                $state = State::where('state_name', 'ilike', '%' . $profile_data['state'] . '%')->first();
                if (!empty($state)){
                    $user->state_id = $state->id;
                    $user->region = $state->state_name;
                }
            }
            if (!empty($profile_data['country'])){
                $country = Country::where('country_name', 'ilike', '%' . $profile_data['country'] . '%')->first();   
                if (!empty($country)) {
                    $user->country_id = $country->id;
                    $user->country = $country->country_name;
                    $user->country_short_name = $country->short_name_2_digit;   
                }
            }

            $user->marketplace_email_allowed = checkEmpty($profile_data, 'marketplace_email_allowed', $user->marketplace_email_allowed);
            $user->is_email_verified         = checkEmpty($profile_data, 'is_email_verified', $user->is_email_verified);
            $user->save();
            DB::commit();
            return prepare_response(200, true, __('messages.profile_update_success'), $user, [], "1.0");
        } catch (Exception $e) {
            DB::rollBack();
            return exceptionMessage($e);
        }
    }
}
