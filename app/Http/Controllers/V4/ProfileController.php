<?php

namespace App\Http\Controllers\V4;

use App\Constants\MarketplaceTypes;
use App\Http\Controllers\Controller;
use App\Models\AcademyMember;
use App\Models\Evaluation;
use App\Models\EvaluationSubmission;
use App\Models\FavouriteUser;
use App\Models\TeamMember;
use App\Models\V4Academy;
use App\Models\V4Follow;
use App\Models\V4UserReport;
use App\Models\V4PlayerAchievement;
use App\Models\V4PlayerPortfolio;
use App\Models\EvaluatorAssignment;
use App\Models\V4PlayerPortfolioSub;
use App\Models\V4Team;
use App\Models\V4UploadedMedia;
use App\Models\V4Post;
use App\Models\V4User;
use Carbon\Carbon;
use Exception;
use App\Contracts\ErrorTrackerInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    protected $errorTracker;

    public function __construct(ErrorTrackerInterface $errorTracker)
    {
        $this->errorTracker = $errorTracker;
    }

    public function getProfileData(Request $request)
    {
        try {
            /** @var V4User $user */
            $user = Auth::guard('v4api')->user();

            // Load the appropriate profile based on role
            $profileData = null;
            switch ($user->role) {
                case 'player':
                    $user->load('playerProfile');
                    $profileData = $user->playerProfile;
                    break;
                case 'coach':
                    $user->load('coachProfile');
                    $profileData = $user->coachProfile;
                    break;
                case 'team':
                    $user->load('teamProfile.team');
                    $profileData = $user->teamProfile;
                    break;
                case 'scout':
                    $user->load('scoutProfile');
                    $profileData = $user->scoutProfile;
                    break;
                case 'academy':
                    $user->load('academyProfile.academy');
                    $profileData = $user->academyProfile;
                    break;
                case 'organizer':
                    $user->load('organizerProfile');
                    $profileData = $user->organizerProfile;
                    break;
                case 'adviser':
                    $user->load('adviserProfile');
                    $profileData = $user->adviserProfile;
                    break;
                case 'parent':
                    $user->load('parentProfile');
                    $profileData = $user->parentProfile;
                    $user->load('children.playerProfile');
                    break;
                case 'fan':
                    $user->load('fanProfile');
                    $profileData = $user->fanProfile;
                    break;
                case 'evaluator':
                    $user->load('evaluatorProfile');
                    $profileData = $user->evaluatorProfile;
                    break;
            }

            // Create a standardized response
            $userData = $user->toArray();

            // Remove the specific profile fields to avoid duplication
            unset(
                $userData['player_profile'],
                $userData['coach_profile'],
                $userData['team_profile'],
                $userData['scout_profile'],
                $userData['academy_profile'],
                $userData['organizer_profile'],
                $userData['adviser_profile'],
                $userData['parent_profile'],
                $userData['fan_profile']
            );

            try {
                $token = $request->bearerToken();

                $baseUrl = config('app.env') === 'production' ? config('CHAT_APP_HOST_PRODUCTION') : env('CHAT_APP_HOST');

                $payload = [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'name' => $user->name,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'date_of_birth' => $user->date_of_birth,
                    'country' => $user->country,
                    'state' => $user->state,
                    'city' => $user->city,
                    'zip' => $user->zip,
                    'is_child' => $user->is_child,
                    'parent_id' => $user->parent_id,
                    'username' => $user->username,
                    'role' => $user->role,
                    'age' => $user->age,
                    'profile_photo' => $user->profile_photo,

                ];

                $response = Http::withToken($token)
                    ->put($baseUrl . '/user/update', $payload);

                if ($response->successful() && isset($response->json()['_id'])) {
                    Log::info('User updated successfully', $response->json());
                } else {
                    Log::warning('Update User API failed', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                }
            } catch (\Throwable $e) {
                // Track error in Sentry
                $this->errorTracker->captureException($e, [
                    'action' => 'unknown_method',
                ]);
                Log::error('Update User Profile API error', ['error' => $e->getMessage()]);
            }

            // Add the profile data under a standardized field name
            $userData['profile'] = $profileData;
            return response()->json([
                'success' => true,
                'message' => 'Profile data retrieved successfully',
                'user' => $userData,
            ]);
        } catch (Exception $e) {
            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => 'unknown_method',
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve profile data',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function updateProfile(Request $request)
    {
        try {
            /** @var V4User $user */

            $user = Auth::guard('v4api')->user();

            $isFirstTimeOnboarding = !$user->is_onboarded;

            $rules = [
                'team_id' => 'nullable|exists:v4_teams,id',
                'academy_id' => 'nullable|exists:v4_academies,id',
                'first_name' => 'nullable|string|max:255',
                'last_name' => 'nullable|string|max:255',
                'email' => 'nullable|email',
                'phone' => 'nullable|string|max:20',
                'country' => 'nullable|string|max:100',
                'state' => 'nullable|string|max:100',
                'city' => 'nullable|string|max:100',
                'date_of_birth' => 'nullable|date',
                'zip' => 'nullable|string|max:20',
                'is_onboarded' => 'nullable|boolean',
            ];

            // These fields are always optional
            $rules['enable_private_account'] = 'nullable|boolean';
            $rules['receive_news_offers'] = 'nullable|boolean';

            // terms_accepted validation only if it's not already true
            if (!$user->terms_accepted) {
                if ($isFirstTimeOnboarding && ($user->role !== 'coach' && $user->role !== 'parent')) {
                    $rules['terms_accepted'] = 'required|boolean';
                } else {
                    $rules['terms_accepted'] = 'nullable|boolean';
                }
            }

            // Add profile photo validation if present
            if ($request->hasFile('profile_photo')) {
                $rules['profile_photo'] = 'file|image|max:5120'; // 5MB max
            }

            $validated = $request->validate($rules);

            // Handle profile photo upload if present
            if ($request->hasFile('profile_photo')) {
                $path = $request->file('profile_photo')->store(
                    'profile_photos/' . $user->id,
                    's3'
                );
                $photoUrl = Storage::disk('s3')->url($path);
                $validated['profile_photo'] = $photoUrl;
            } elseif ($request->has('profile_photo') && ($request->input('profile_photo') === null || $request->input('profile_photo') === '')) {
                $validated['profile_photo'] = null;
            }

            $user->update($validated);
            $user->refresh();

            switch ($user->role) {
                case 'player':
                    $playerValidated = $request->validate([
                        'teams' => 'nullable|array',
                        'leagues' => 'nullable|array',
                        'handedness' => 'nullable|in:left,right,ambidextrous',
                        'weight' => 'nullable|numeric',
                        'height' => 'nullable|numeric',
                        'position' => 'nullable|string|max:100',
                        'gender' => 'nullable|in:male,female,other',
                    ]);
                    $user->playerProfile()->updateOrCreate(
                        ['v4_user_id' => $user->id],
                        $playerValidated
                    );
                    $user->load('playerProfile');
                    break;

                case 'coach':
                    $coachValidated = $request->validate([
                        'leagues' => 'nullable|array',
                        'teams' => 'nullable|array',
                    ]);
                    $user->coachProfile()->updateOrCreate([], $coachValidated);
                    $user->load('coachProfile');
                    break;

                case 'academy':
                    $academyProfileValidated = $request->validate([
                        "academy_name" => "nullable|string|max:255",
                        "administrator_first_name" => "nullable|string|max:255",
                        "administrator_last_name" => "nullable|string|max:255",
                        "leagues" => "nullable|array",
                        "website" => "nullable|string|max:255",
                        "address" => "nullable|string|max:255",
                        "academy_years_running" => "nullable|integer",
                    ]);

                    $academyValidated = $academyProfileValidated;
                    if (!empty($validated['profile_photo'])) {
                        $academyValidated['profile_photo'] = $validated['profile_photo'];
                    }
                    $academyValidated['phone'] = $validated['phone'] ?? null;

                    $v4Academy = null;
                    $academyId = $validated['academy_id'] ?? null;

                    if (!$user->is_onboarded) {
                        $v4Academy = V4Academy::create($academyValidated);
                        $token = $request->bearerToken();
                        $baseUrl = config('app.env') === 'production' ? config('CHAT_APP_HOST_PRODUCTION') : env('CHAT_APP_HOST');
                        $requestData = [
                            'type' => 'group',
                            'participants' => [$user->id],
                            'name' => $v4Academy->academy_name,
                        ];

                        if (!empty($validated['profile_photo'])) {
                            $requestData['groupImage'] = $validated['profile_photo'];
                        }
                        $response = Http::withHeaders([
                            'Authorization' => 'Bearer ' . $token,
                            'Content-Type' => 'application/json',
                        ])->post($baseUrl . '/conversation/create', $requestData);
                        if ($response->successful() && isset($response->json()['_id'])) {
                            $conversationId = $response->json()['_id'];
                            $v4Academy->update(['conversation_id' => $conversationId]);
                        }
                    } else {
                        $v4Academy = V4Academy::find($validated['academy_id']);
                        if ($v4Academy) {
                            $token = $request->bearerToken();
                            $baseUrl = config('app.env') === 'production' ? config('CHAT_APP_HOST_PRODUCTION') : env('CHAT_APP_HOST');
                            if ($v4Academy->conversation_id == null) {
                                $requestData = [
                                    'type' => 'group',
                                    'participants' => [$user->id],
                                    'name' => $v4Academy->academy_name,
                                ];

                                if (!empty($validated['profile_photo'])) {
                                    $requestData['groupImage'] = $validated['profile_photo'];
                                }

                                $response = Http::withHeaders([
                                    'Authorization' => 'Bearer ' . $token,
                                    'Content-Type' => 'application/json',
                                ])->post($baseUrl . '/conversation/create', $requestData);
                                if ($response->successful() && isset($response->json()['_id'])) {
                                    $conversationId = $response->json()['_id'];
                                    $academyValidated['conversation_id'] = $conversationId;
                                }
                            } else {
                                $conversationId = $v4Academy->conversation_id;
                                $requestData = [
                                    'conversationId' => $conversationId,
                                    'type' => 'group',
                                    'name' => $v4Academy->academy_name,
                                ];
                                if (!empty($validated['profile_photo'])) {
                                    $requestData['groupImage'] = $validated['profile_photo'];
                                }
                                $response = Http::withHeaders([
                                    'Authorization' => 'Bearer ' . $token,
                                    'Content-Type' => 'application/json',
                                ])->put($baseUrl . '/conversation/update', $requestData);
                                // Check if the update was successful
                                if ($response->successful() && isset($response->json()['_id'])) {
                                    $updatedConversationId = $response->json()['_id'];  // Assuming the updated conversation ID is returned
                                    $academyValidated['conversation_id'] = $updatedConversationId;
                                }
                            }
                            $v4Academy->update($academyValidated);
                        } else {
                            // Fallback
                            $v4Academy = V4Academy::create($academyValidated);
                            $token = $request->bearerToken();
                            $baseUrl = config('app.env') === 'production' ? config('CHAT_APP_HOST_PRODUCTION') : env('CHAT_APP_HOST');
                            $requestData = [
                                'type' => 'group',
                                'participants' => [$user->id],
                                'name' => $v4Academy->academy_name,
                            ];
                            if (!empty($validated['profile_photo'])) {
                                $requestData['groupImage'] = $validated['profile_photo'];
                            }
                            $response = Http::withHeaders([
                                'Authorization' => 'Bearer ' . $token,
                                'Content-Type' => 'application/json',
                            ])->post($baseUrl . '/conversation/create', $requestData);
                            if ($response->successful() && isset($response->json()['_id'])) {
                                $conversationId = $response->json()['_id'];
                                $v4Academy->update(['conversation_id' => $conversationId]);
                            }
                        }
                    }

                    $academyProfileValidated['academy_id'] = $v4Academy->id;
                    $user->academyProfile()->updateOrCreate([], $academyProfileValidated);
                    $user->load('academyProfile.academy');
                    break;

                case 'team':
                    $teamProfileValidated = $request->validate([
                        'team_name' => 'nullable|string|max:255',
                        'administrator_first_name' => 'nullable|string|max:255',
                        'administrator_last_name' => 'nullable|string|max:255',
                        'leagues' => 'nullable|array',
                        'website' => 'nullable|string|max:255',
                        'address' => 'nullable|string|max:255',
                        'team_years_running' => 'nullable|integer',
                    ]);

                    $teamValidated = $teamProfileValidated;
                    if (!empty($validated['profile_photo'])) {
                        $teamValidated['profile_photo'] = $validated['profile_photo'];
                    }
                    $teamValidated['phone'] = $validated['phone'] ?? null;

                    $v4team = null;
                    $teamId = $validated['team_id'] ?? null;

                    if (!$user->is_onboarded) {
                        $v4team = V4Team::create($teamValidated);
                        $token = $request->bearerToken();
                        $baseUrl = config('app.env') === 'production' ? config('CHAT_APP_HOST_PRODUCTION') : env('CHAT_APP_HOST');
                        $requestData = [
                            'type' => 'group',
                            'participants' => [$user->id],
                            'name' => $v4team->team_name,
                        ];
                        if (!empty($validated['profile_photo'])) {
                            $requestData['groupImage'] = $validated['profile_photo'];
                        }
                        $response = Http::withHeaders([
                            'Authorization' => 'Bearer ' . $token,
                            'Content-Type' => 'application/json',
                        ])->post($baseUrl . '/conversation/create', $requestData);
                        if ($response->successful() && isset($response->json()['_id'])) {
                            $conversationId = $response->json()['_id'];
                            $v4team->update(['conversation_id' => $conversationId]);
                        }
                    } else {
                        $v4team = V4Team::find($teamId);
                        if ($v4team) {
                            $token = $request->bearerToken();
                            $baseUrl = config('app.env') === 'production' ? config('CHAT_APP_HOST_PRODUCTION') : env('CHAT_APP_HOST');

                            if ($v4team->conversation_id == null) {
                                $requestData = [
                                    'type' => 'group',
                                    'participants' => [$user->id],
                                    'name' => $v4team->team_name,
                                ];
                                if (!empty($validated['profile_photo'])) {
                                    $requestData['groupImage'] = $validated['profile_photo'];
                                }
                                $response = Http::withHeaders([
                                    'Authorization' => 'Bearer ' . $token,
                                    'Content-Type' => 'application/json',
                                ])->post($baseUrl . '/conversation/create', $requestData);
                                if ($response->successful() && isset($response->json()['_id'])) {
                                    $conversationId = $response->json()['_id'];
                                    $teamValidated['conversation_id'] = $conversationId;
                                }
                            } else {
                                $conversationId = $v4team->conversation_id;
                                $requestData = [
                                    'conversationId' => $conversationId,
                                    'type' => 'group',
                                    'name' => $v4team->team_name,
                                ];
                                if (!empty($validated['profile_photo'])) {
                                    $requestData['groupImage'] = $validated['profile_photo'];
                                }
                                $response = Http::withHeaders([
                                    'Authorization' => 'Bearer ' . $token,
                                    'Content-Type' => 'application/json',
                                ])->post($baseUrl . '/conversation/update', $requestData);
                                if ($response->successful() && isset($response->json()['_id'])) {
                                    $conversationId = $response->json()['_id'];
                                    $teamValidated['conversation_id'] = $conversationId;
                                }
                            }
                            $v4team->update($teamValidated);
                        } else {
                            // Fallback
                            $v4team = V4Team::create($teamValidated);

                            $token = $request->bearerToken();
                            $baseUrl = config('app.env') === 'production' ? config('CHAT_APP_HOST_PRODUCTION') : env('CHAT_APP_HOST');
                            $requestData = [
                                'type' => 'group',
                                'participants' => [$user->id],
                                'name' => $v4team->team_name,
                            ];
                            if (!empty($validated['profile_photo'])) {
                                $requestData['groupImage'] = $validated['profile_photo'];
                            }
                            $response = Http::withHeaders([
                                'Authorization' => 'Bearer ' . $token,
                                'Content-Type' => 'application/json',
                            ])->post($baseUrl . '/conversation/create', $requestData);
                            if ($response->successful() && isset($response->json()['_id'])) {
                                $conversationId = $response->json()['_id'];
                                $v4team->update(['conversation_id' => $conversationId]);
                            }
                        }
                    }

                    $teamProfileValidated['team_id'] = $v4team->id;
                    $user->teamProfile()->updateOrCreate([], $teamProfileValidated);
                    $user->load('teamProfile.team');
                    break;

                case 'scout':
                    $scoutValidated = $request->validate([
                        'leagues' => 'nullable|array',
                        'teams' => 'nullable|array',
                        'scouting_years' => 'nullable|integer',
                        'level_hockey_played' => 'nullable|string|max:255',
                        'current_involvement_level' => 'nullable|string|max:255',
                        'current_sport_role' => 'nullable|string|max:255',
                        'resume' => 'nullable|file|mimes:pdf|max:10240',
                        'references' => 'nullable|array',
                        'references.*.name' => 'required_with:references|string|max:255',
                        'references.*.email' => 'required_with:references|email|max:255',
                        'references.*.phone' => 'required_with:references|string|max:20',
                    ]);

                    if ($request->hasFile('resume')) {
                        $path = $request->file('resume')->store(
                            'resume/' . $request->user()->id,
                            's3'
                        );
                        $resumeUrl = Storage::disk('s3')->url($path);

                        $scoutValidated['resume'] = $resumeUrl;
                    }

                    $user->scoutProfile()->updateOrCreate([], $scoutValidated);
                    $user->load('scoutProfile');
                    break;

                case 'fan':
                    $user->fanProfile()->updateOrCreate([], []);
                    $user->load('fanProfile');
                    break;
                case 'organizer':
                    $organizerValidated = $request->validate([
                        "business_name" => "nullable|string|max:255",
                        "business_phone" => "nullable|string|max:20",
                        "address" => "nullable|string|max:255",
                        "website" => "nullable|string|max:255",
                        "number_years_organizing" => "nullable|integer",
                        "leagues" => "nullable|array",
                        "link_of_previous_events" => "nullable|array",
                        "number_of_events_organized" => "nullable|integer",
                    ]);
                    $user->organizerProfile()->updateOrCreate([], $organizerValidated);
                    $user->load('organizerProfile');
                    break;
                case 'adviser':
                    $adviserValidated = $request->validate([
                        'leagues' => 'nullable|array',
                        'teams' => 'nullable|array',
                        'business_name' => 'nullable|string|max:255',
                        'business_phone' => 'nullable|string|max:20',
                        'website' => 'nullable|string|max:255',
                        'address' => 'nullable|string|max:255',
                        'level_hockey_played' => 'nullable|string|max:255',
                        'current_involvement_level' => 'nullable|string|max:255',
                        'current_sport_role' => 'nullable|string|max:255',
                        'number_of_years_experience' => 'nullable|integer',
                        'resume' => 'nullable|file|mimes:pdf|max:10240',
                        'references' => 'nullable|array',
                        'references.*.name' => 'required_with:references|string|max:255',
                        'references.*.email' => 'required_with:references|email|max:255',
                        'references.*.phone' => 'required_with:references|string|max:20',
                    ]);
                    if ($request->hasFile('resume')) {
                        $path = $request->file('resume')->store(
                            'resume/' . $request->user()->id,
                            's3'
                        );
                        $resumeUrl = Storage::disk('s3')->url($path);

                        $adviserValidated['resume'] = $resumeUrl;
                    }
                    $user->adviserProfile()->updateOrCreate([], $adviserValidated);
                    $user->load('adviserProfile');
                    break;
                case 'parent':
                    $user->parentProfile()->updateOrCreate([], []);
                    $user->load('parentProfile');
                    break;
                case 'evaluator':
                    $evaluatorValidated = $request->validate([
                        'leagues' => 'nullable|array',
                        'address' => 'nullable|string|max:255',
                        'level_hockey_played' => 'nullable|string|max:255',
                        'current_involvement_level' => 'nullable|string|max:255',
                        'current_sport_role' => 'nullable|string|max:255',
                        'number_of_years_experience' => 'nullable|integer',
                        'resume' => 'nullable|file|mimes:pdf|max:10240',
                        'references' => 'nullable|array',
                        'references.*.name' => 'required_with:references|string|max:255',
                        'references.*.email' => 'required_with:references|email|max:255',
                        'references.*.phone' => 'required_with:references|string|max:20',
                    ]);
                    if ($request->hasFile('resume')) {
                        $path = $request->file('resume')->store(
                            'resume/' . $request->user()->id,
                            's3'
                        );
                        $resumeUrl = Storage::disk('s3')->url($path);
                        $evaluatorValidated['resume'] = $resumeUrl;
                    }
                    $user->evaluatorProfile()->updateOrCreate([], $evaluatorValidated);
                    $user->load('evaluatorProfile');
                    break;
            }

            // Create a standardized response
            $userData = $user->toArray();

            // Remove the specific profile fields to avoid duplication
            unset(
                $userData['player_profile'],
                $userData['coach_profile'],
                $userData['team_profile'],
                $userData['scout_profile'],
                $userData['academy_profile'],
                $userData['organizer_profile'],
                $userData['adviser_profile'],
                $userData['parent_profile'],
                $userData['fan_profile'],
                $userData['evaluator_profile']
            );

            // Add the profile data under a standardized field name
            switch ($user->role) {
                case 'player':
                    $userData['profile'] = $user->playerProfile;
                    break;
                case 'coach':
                    $userData['profile'] = $user->coachProfile;
                    break;
                case 'team':
                    $userData['profile'] = $user->teamProfile;
                    break;
                case 'scout':
                    $userData['profile'] = $user->scoutProfile;
                    break;
                case 'academy':
                    $userData['profile'] = $user->academyProfile;
                    break;
                case 'organizer':
                    $userData['profile'] = $user->organizerProfile;
                    break;
                case 'adviser':
                    $userData['profile'] = $user->adviserProfile;
                    break;
                case 'parent':
                    $userData['profile'] = $user->parentProfile;
                    break;
                case 'fan':
                    $userData['profile'] = $user->fanProfile;
                    break;

                case 'evaluator':
                    $userData['profile'] = $user->evaluatorProfile;
                    break;
            }

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'user' => $userData,
            ]);
        } catch (ValidationException $e) {
            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => 'unknown_method',
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Profile update failed.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function deleteUserAccount(Request $request, $id)
    {
        try {
            $authUser = Auth::guard('v4api')->user();
            if (!$authUser) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            $user = V4User::find($id);
            if (!$user) {
                return response()->json(['message' => 'User not found'], 404);
            }

            // Authorization logic
            $isSelf = ($authUser->id == $id);
            $isParentDeletingChild = (
                $authUser->role === 'parent' &&
                $user->parent_id == $authUser->id
            );

            if (!$isSelf && !$isParentDeletingChild) {
                return response()->json([
                    'message' => 'You are not authorized to delete this user account'
                ], 403);
            }

            $user->delete();

            return response()->json([
                'message' => 'User account deleted successfully'
            ], 200);
        } catch (ValidationException $e) {
            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => 'unknown_method',
            ]);
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'User account deletion failed.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function deleteUserAccountFromAdmin(Request $request, $id)
    {
        try {

            $user = V4User::find($id);
            if (!$user) {
                return response()->json(['message' => 'User not found'], 404);
            }

            $user->delete();

            return response()->json([
                'message' => 'User account deleted successfully'
            ], 200);
        } catch (ValidationException $e) {
            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => 'unknown_method',
            ]);
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'User account deletion failed.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }


    public function addChild(Request $request)
    {
        try {
            $parent = Auth::guard('v4api')->user();

            $validatedData = $request->validate([
                'first_name' => 'required|string|max:50',
                'last_name' => 'required|string|max:50',
                'date_of_birth' => 'required|date|before:today',
                'gender' => 'required|in:male,female,other',
                'username' => 'required|unique:v4_users,username',
                'password' => 'required|min:6',
                'position' => 'nullable|string|max:100',
                'email' => 'nullable|email',
                'teams' => 'nullable|array',
                'leagues' => 'nullable|array',
                // Add permission validations
                'permissions' => 'nullable|array',
                'permissions.can_chat' => 'boolean',
                'permissions.can_view_events' => 'boolean',
                'permissions.can_view_feed' => 'boolean',
                'permissions.can_view_messages' => 'boolean',
                'permissions.can_accept_invites' => 'boolean',
                'permissions.can_send_friend_requests' => 'boolean',
                'permissions.can_use_marketplace' => 'boolean',
            ], [
                'username.unique' => 'Username already taken',
            ]);

            // Use database transaction to ensure data consistency
            $result = DB::transaction(function () use ($parent, $validatedData) {
                // Create child user with parent's information
                $child = V4User::create([
                    'parent_id' => $parent->id,
                    'is_child' => true,
                    'role' => 'player',
                    'first_name' => $validatedData['first_name'],
                    'last_name' => $validatedData['last_name'],
                    'date_of_birth' => $validatedData['date_of_birth'],
                    'gender' => $validatedData['gender'],
                    'username' => $validatedData['username'],
                    'password' => Hash::make($validatedData['password']),
                    // Pass parent's contact and location information to child
                    'email' => null, // Children do not require email; keep unique constraint for non-children
                    'phone' => $parent->phone,
                    'country' => $parent->country,
                    'state' => $parent->state,
                    'city' => $parent->city,
                    // Pass parent's account status flags to child
                    'terms_accepted' => $parent->terms_accepted,
                    'is_onboarded' => $parent->is_onboarded,
                ]);

                // Create player profile with permissions
                $playerProfile = new \App\Models\PlayerProfile();
                $playerProfile->v4_user_id = $child->id;
                $playerProfile->gender = $validatedData['gender'];
                $playerProfile->position = $validatedData['position'] ?? null;
                $playerProfile->teams = $validatedData['teams'] ?? null;
                $playerProfile->leagues = $validatedData['leagues'] ?? null;

                // Set default permissions if not provided
                $permissions = $validatedData['permissions'] ?? [
                    'can_chat' => true,
                    'can_view_events' => true,
                    'can_view_feed' => true,
                    'can_view_messages' => true,
                    'can_accept_invites' => true,
                    'can_send_friend_requests' => true,
                    'can_use_marketplace' => true,
                ];

                $playerProfile->permissions = $permissions;
                $playerProfile->save();

                // Create standardized response for parent
                $parentData = $parent->toArray();

                // Remove the specific profile fields to avoid duplication
                unset(
                    $parentData['player_profile'],
                    $parentData['coach_profile'],
                    $parentData['team_profile'],
                    $parentData['scout_profile'],
                    $parentData['academy_profile'],
                    $parentData['organizer_profile'],
                    $parentData['adviser_profile'],
                    $parentData['parent_profile'],
                    $parentData['fan_profile']
                );

                // Add the profile data under a standardized field name
                switch ($parent->role) {
                    case 'parent':
                        $parentData['profile'] = $parent->parentProfile;
                        break;
                    // Add other cases if needed
                }

                // Child will be a player, so load player profile
                $child->load('playerProfile');
                $childData = $child->toArray();

                // Remove the specific profile fields to avoid duplication
                unset(
                    $childData['player_profile'],
                    $childData['coach_profile'],
                    $childData['team_profile'],
                    $childData['scout_profile'],
                    $childData['academy_profile'],
                    $childData['organizer_profile'],
                    $childData['adviser_profile'],
                    $childData['parent_profile'],
                    $childData['fan_profile']
                );

                // Add the profile data under a standardized field name
                $childData['profile'] = $child->playerProfile;

                return [
                    'parent' => $parentData,
                    'child' => $childData,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Child account created successfully',
                'parent' => $result['parent'],
                'child' => $result['child'],
            ], 201);
        } catch (ValidationException $e) {
            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => 'unknown_method',
            ]);
            return response()->json([
                'success' => false,
                'message' => $e->errors()['username'][0] ?? 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Child add failed.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
    public function updateChildPermissions(Request $request, $childId)
    {
        try {
            // Get the authenticated parent user
            $parent = Auth::guard('v4api')->user();

            // Verify this is the parent of the child
            $child = V4User::where('id', $childId)
                ->where('parent_id', $parent->id)
                ->first();

            if (!$child) {
                return response()->json([
                    'success' => false,
                    'message' => 'Child not found or not authorized',
                ], 404);
            }

            // Validate the permissions data
            $validatedData = $request->validate([
                'permissions' => 'required|array',
                'permissions.can_chat' => 'boolean',
                'permissions.can_view_events' => 'boolean',
                'permissions.can_view_feed' => 'boolean',
                'permissions.can_view_messages' => 'boolean',
                'permissions.can_accept_invites' => 'boolean',
                'permissions.can_send_friend_requests' => 'boolean',
                'permissions.can_use_marketplace' => 'boolean',
            ]);

            // Update the child's permissions
            $child->playerProfile()->update([
                'permissions' => $validatedData['permissions'],
            ]);

            // Load the updated profile
            $child->load('playerProfile');

            // Format the response data
            $childData = $child->toArray();

            // Remove the specific profile fields to avoid duplication
            unset(
                $childData['player_profile'],
                $childData['coach_profile'],
                $childData['team_profile'],
                $childData['scout_profile'],
                $childData['academy_profile'],
                $childData['organizer_profile'],
                $childData['adviser_profile'],
                $childData['parent_profile'],
                $childData['fan_profile']
            );

            // Add the profile data under a standardized field name
            $childData['profile'] = $child->playerProfile;

            return response()->json([
                'success' => true,
                'message' => 'Child permissions updated successfully',
                'child' => $childData,
            ]);
        } catch (ValidationException $e) {
            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => 'unknown_method',
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update child permissions',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function updateChildCredentials(Request $request, $childId)
    {
        try {
            // Get the authenticated parent user
            $parent = Auth::guard('v4api')->user();

            // Verify this is the parent of the child
            $child = V4User::where('id', $childId)
                ->where('parent_id', $parent->id)
                ->first();

            if (!$child) {
                return response()->json([
                    'success' => false,
                    'message' => 'Child not found or not authorized',
                ], 404);
            }

            // Validate the credentials data
            $validatedData = $request->validate([
                'username' => 'sometimes|required|string|unique:v4_users,username,' . $child->id,
                'password' => 'sometimes|required|string|min:6',
            ]);

            // Prepare update data
            $updateData = [];

            if (isset($validatedData['username'])) {
                $updateData['username'] = $validatedData['username'];
            }

            if (isset($validatedData['password'])) {
                $updateData['password'] = Hash::make($validatedData['password']);
            }

            // Update the child's credentials
            $child->update($updateData);

            // Format the response data
            $childData = $child->toArray();

            // Remove the specific profile fields to avoid duplication
            unset(
                $childData['player_profile'],
                $childData['coach_profile'],
                $childData['team_profile'],
                $childData['scout_profile'],
                $childData['academy_profile'],
                $childData['organizer_profile'],
                $childData['adviser_profile'],
                $childData['parent_profile'],
                $childData['fan_profile']
            );

            // Add the profile data under a standardized field name
            $child->load('playerProfile');
            $childData['profile'] = $child->playerProfile;

            return response()->json([
                'success' => true,
                'message' => 'Child credentials updated successfully',
                'child' => $childData,
            ]);
        } catch (ValidationException $e) {
            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => 'unknown_method',
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update child credentials',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    //for parent to update child profile
    public function updateChildProfile(Request $request, $childId)
    {
        try {
            $parent = Auth::guard('v4api')->user();

            $child = V4User::where('id', $childId)
                ->where('parent_id', $parent->id)
                ->first();

            if (!$child) {
                return response()->json([
                    'success' => false,
                    'message' => 'Child not found or not authorized',
                ], 404);
            }

            $userRules = [
                'first_name' => 'nullable|string|max:255',
                'last_name' => 'nullable|string|max:255',
                'date_of_birth' => 'nullable|date',
                'country' => 'nullable|string|max:100',
                'state' => 'nullable|string|max:100',
                'city' => 'nullable|string|max:100',
                'phone' => 'nullable|string|max:20',
                'enable_private_account' => 'nullable|boolean'
            ];

            $profileRules = [
                'teams' => 'nullable|array',
                'leagues' => 'nullable|array',
                'position' => 'nullable|string|max:100',
                'handedness' => 'nullable|in:left,right,ambidextrous',
                'weight' => 'nullable|numeric',
                'height' => 'nullable|numeric',
                'gender' => 'nullable|in:male,female,other',
            ];

            if ($request->hasFile('profile_photo')) {
                $userRules['profile_photo'] = 'file|image|max:5120';
            }

            $validatedUserData = $request->validate($userRules);
            $validatedProfileData = $request->validate($profileRules);

            if ($request->hasFile('profile_photo')) {
                $path = $request->file('profile_photo')->store(
                    'profile_photos/' . $child->id,
                    's3'
                );
                $photoUrl = Storage::disk('s3')->url($path);
                $validatedUserData['profile_photo'] = $photoUrl;
            }

            $result = DB::transaction(function () use ($child, $validatedUserData, $validatedProfileData) {
                $child->update($validatedUserData);

                $child->playerProfile()->updateOrCreate(
                    ['v4_user_id' => $child->id],
                    $validatedProfileData
                );

                $child->refresh();
                $child->load('playerProfile');

                return $child;
            });

            $childData = $result->toArray();

            unset(
                $childData['player_profile'],
                $childData['coach_profile'],
                $childData['team_profile'],
                $childData['scout_profile'],
                $childData['academy_profile'],
                $childData['organizer_profile'],
                $childData['adviser_profile'],
                $childData['parent_profile'],
                $childData['fan_profile']
            );

            $childData['profile'] = $result->playerProfile;

            return response()->json([
                'success' => true,
                'message' => 'Child profile updated successfully',
                'child' => $childData,
            ]);
        } catch (ValidationException $e) {
            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => 'unknown_method',
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update child profile',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Search users by first name or last name with pagination
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function searchUsers(Request $request)
    {
        // Validate request parameters
        $request->validate([
            'q' => 'nullable|string|max:255',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:10000',
            'state' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'roles' => 'nullable|array',
            'roles.*' => 'string|in:player,team,coach,scout,organizer,parent,fan,academy,adviser,evaluator'
        ]);

        // Get search parameters
        $searchTerm = $request->input('q', '');
        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 15);
        $state = $request->input('state', null);
        $city = $request->input('city', null);
        $country = $request->input('country', null);
        $roles = $request->input('roles', []);

        // Get current authenticated user
        $currentUser = Auth::guard('v4api')->user();

        // Build the query
        $query = V4User::query()
            ->select(['id', 'first_name', 'last_name', 'role', 'profile_photo', 'email', 'phone'])
            ->with([
                'teamProfile:id,v4_user_id,team_id',
                'teamProfile.team:id,team_name,profile_photo,administrator_first_name,administrator_last_name,email,phone',
                'academyProfile:id,v4_user_id,academy_id',
                'academyProfile.academy:id,academy_name,profile_photo,administrator_first_name,administrator_last_name,email,phone',
            ])
            ->whereNotIn('role', ['super-admin', 'admin', 'manager'])
            ->where('is_onboarded', true)
            ->where('id', '!=', $currentUser->id); // Exclude current user from search results

        // Apply search filter if search term is provided
        if (!empty($searchTerm)) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('first_name', 'ilike', "%{$searchTerm}%")
                    ->orWhere('last_name', 'ilike', "%{$searchTerm}%")
                    ->orWhere('email', 'ilike', "%{$searchTerm}%")
                    ->orWhere('phone', 'ilike', "%{$searchTerm}%")
                    ->orWhereHas('teamProfile.team', function ($teamQuery) use ($searchTerm) {
                        $teamQuery->where('team_name', 'ilike', "%{$searchTerm}%")
                            ->orWhere('administrator_first_name', 'ilike', "%{$searchTerm}%")
                            ->orWhere('administrator_last_name', 'ilike', "%{$searchTerm}%")
                            ->orWhere('email', 'ilike', "%{$searchTerm}%")
                            ->orWhere('phone', 'ilike', "%{$searchTerm}%");
                    })
                    ->orWhereHas('academyProfile.academy', function ($academyQuery) use ($searchTerm) {
                        $academyQuery->where('academy_name', 'ilike', "%{$searchTerm}%")
                            ->orWhere('administrator_first_name', 'ilike', "%{$searchTerm}%")
                            ->orWhere('administrator_last_name', 'ilike', "%{$searchTerm}%")
                            ->orWhere('email', 'ilike', "%{$searchTerm}%")
                            ->orWhere('phone', 'ilike', "%{$searchTerm}%");
                    });
            });
        }

        if (!empty($state)) {
            $query->where('state', 'ilike', "%{$state}%");
        }

        if (!empty($city)) {
            $query->where('city', 'ilike', "%{$city}%");
        }

        if (!empty($country)) {
            $query->where('country', 'ilike', "%{$country}%");
        }

        if (!empty($roles)) {
            $query->whereIn('role', $roles);
        }

        // Execute the query with pagination
        $users = $query->paginate($perPage, ['*'], 'page', $page);

        // Format the response for FlutterFlow compatibility
        return response()->json([
            'data' => $users->items(),
            'pagination' => [
                'total' => $users->total(),
                'per_page' => $users->perPage(),
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'from' => $users->firstItem() ?? 0,
                'to' => $users->lastItem() ?? 0,
                'has_more_pages' => $users->hasMorePages(),
            ],
        ]);
    }


    public function getProfileBatchData(Request $request)
    {
        try {
            //validation
            $validated = $request->validate([
                'convoIds' => 'required|array',
                'convoIds.*' => 'required|array',
                'convoIds.*.*' => 'required|array',
                'convoIds.*.*.*' => 'string',
            ]);

            $result = [];

            foreach ($validated['convoIds'] as $convoMap) {

                $entryResult = [];

                foreach ($convoMap as $conversationKey => $userIds) {
                    if (!is_string($conversationKey)) {
                        continue;
                    }

                    $userResult = [];

                    if (!empty($userIds)) {
                        $filteredUserIds = array_filter($userIds, fn($id) => is_numeric($id));

                        if (empty($filteredUserIds)) {
                            Log::warning('Skipped conversation because no valid numeric user IDs found', [
                                'conversationKey' => $conversationKey,
                                'userIds' => $userIds,
                            ]);
                            continue;
                        }

                        Log::info('Fetching valid user IDs', ['filteredUserIds' => $filteredUserIds]);


                        // all users
                        $users = V4User::whereIn('id', $filteredUserIds)
                            ->select(['id', 'first_name', 'last_name', 'role', 'profile_photo'])
                            ->get();

                        foreach ($users as $user) {
                            $userResult[$user->id] = [
                                'id' => $user->id,
                                'first_name' => $user->first_name,
                                'last_name' => $user->last_name,
                                'role' => $user->role,
                                'profile_photo' => $user->profile_photo,
                                'name' => $user->name,
                            ];
                        }
                    }
                    $entryResult[$conversationKey] = $userResult;
                }

                $result[] = $entryResult;
            }

            return response()->json([
                'success' => true,
                'users' => $result,
            ]);
        } catch (ValidationException $e) {
            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => 'unknown_method',
            ]);
            Log::warning('Validation failed', ['errors' => $e->errors()]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Unhandled exception in getProfileBatchData', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve batch profile data',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function searchAndSortUsers(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'q' => 'nullable|string|max:255',
                'page' => 'nullable|integer|min:1',
                'role' => 'nullable|string|in:player,child,coach,scout,parent,team,academy,organizer,fan,adviser,evaluator',
                'per_page' => 'nullable|integer|min:1|max:9999999999',
                'sort_by' => 'nullable|string|in:first_name,last_name,role,created_at',
                'sort_order' => 'nullable|string|in:asc,desc',
            ]);

            $searchTerm = $validated['q'] ?? null;
            $role = $validated['role'] ?? null;

            $page = $validated['page'] ?? 1;
            $perPage = $validated['per_page'] ?? 15;
            $sortBy = $validated['sort_by'] ?? 'first_name';
            $sortOrder = $validated['sort_order'] ?? 'asc';

            $cacheKey = 'admin_users_search_' . md5(json_encode([
                'q' => $searchTerm,
                'role' => $role,
                'page' => $page,
                'per_page' => $perPage,
                'sort_by' => $sortBy,
                'sort_order' => $sortOrder,
            ]));

            $users = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($searchTerm, $role, $page, $perPage, $sortBy, $sortOrder) {
                $query = V4User::query()
                    ->whereNotIn('role', ['super-admin', 'admin', 'manager']);

                if ($searchTerm) {
                    $query->where(function ($q) use ($searchTerm) {
                        $q->where('first_name', 'ilike', "%{$searchTerm}%")
                            ->orWhere('last_name', 'ilike', "%{$searchTerm}%")
                            ->orWhere('email', 'ilike', "%{$searchTerm}%")
                            ->orWhere('phone', 'ilike', "%{$searchTerm}%");
                    });
                }

                if ($role) {
                    // Handle player role - show only non-child players
                    if ($role === 'player') {
                        $query->where('role', 'player')
                            ->where('is_child', false);
                    }
                    // Handle child role - show only child players
                    elseif ($role === 'child') {
                        $query->where('role', 'player')
                            ->where('is_child', true);
                    }
                    // Handle all other roles normally
                    else {
                        $query->where('role', $role);
                    }
                }

                if ($role) {
                    $query->with(match ($role) {
                        'player' => 'playerProfile:id,v4_user_id,teams,leagues,position,gender',
                        'child' => 'playerProfile:id,v4_user_id,teams,leagues,position,gender',
                        'coach' => 'coachProfile:id,v4_user_id,leagues,teams',
                        'team' => 'teamProfile:id,v4_user_id,team_name,leagues',
                        'scout' => 'scoutProfile:id,v4_user_id,scouting_years',
                        'parent' => 'parentProfile:id,v4_user_id',
                        'academy' => 'academyProfile:id,v4_user_id',
                        'organizer' => 'organizerProfile:id,v4_user_id',
                        'adviser' => 'adviserProfile:id,v4_user_id',
                        'fan' => 'fanProfile:id,v4_user_id',
                        'evaluator' => 'evaluatorProfile:id,v4_user_id,is_verified',
                        default => null,
                    });
                } else {
                    $query->with([
                        'playerProfile:id,v4_user_id,teams,leagues,handedness,weight,height,position,gender,permissions',
                        'coachProfile:id,v4_user_id,leagues,teams',
                        'teamProfile:id,v4_user_id,team_name,administrator_first_name,administrator_last_name,leagues,website,address,team_years_running',
                        'scoutProfile:id,v4_user_id,scouting_years,level_hockey_played,current_involvement_level,current_sport_role,leagues,teams,resume,references',
                        'academyProfile:id,v4_user_id',
                        'organizerProfile:id,v4_user_id',
                        'adviserProfile:id,v4_user_id',
                        'parentProfile:id,v4_user_id',
                        'evaluatorProfile:id,v4_user_id,is_verified,references,resume,number_of_years_experience,current_sport_role,leagues,current_involvement_level,level_hockey_played',
                        'fanProfile:id,v4_user_id',
                    ]);
                }

                return $query
                    ->orderBy($sortBy, $sortOrder)
                    ->paginate($perPage, ['*'], 'page', $page);
            });

            $data = $users->map(
                function ($user) {
                    return [
                        'parentId' => $user->parent_id ?? null,
                        'parentName' => $user->parent->name ?? null,
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'status' => $user->is_suspended
                            ? 'suspended'
                            : ($user->is_banned ? 'banned' : 'active'),
                        'role' => $user->role,
                        'country' => $user->country,
                        'createdAt' => $user->created_at,
                        'age' => $user->age,
                        'phone' => $user->phone,
                        'avatar' => $user->profile_photo,
                        'profileData' => $user->profile_data,
                    ];
                }
            );

            return response()->json([
                'data' => $data,
                'pagination' => [
                    'total' => $users->total(),
                    'per_page' => $users->perPage(),
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'from' => $users->firstItem() ?? 0,
                    'to' => $users->lastItem() ?? 0,
                    'has_more_pages' => $users->hasMorePages(),
                ],
            ]);
        } catch (ValidationException $e) {
            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => 'unknown_method',
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function searchAndSortAdminUsers(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'q' => 'nullable|string|max:255',
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100',
                'sort_by' => 'nullable|string|in:first_name,last_name,role',
                'sort_order' => 'nullable|string|in:asc,desc',
            ]);

            $searchTerm = $validated['q'] ?? '';
            $page = $validated['page'] ?? 1;
            $perPage = $validated['per_page'] ?? 15;
            $sortBy = $validated['sort_by'] ?? 'first_name';
            $sortOrder = $validated['sort_order'] ?? 'asc';

            // Create a unique cache key based on query params
            $cacheKey = 'admin_users_search_' . md5(json_encode([
                'q' => $searchTerm,
                'page' => $page,
                'per_page' => $perPage,
                'sort_by' => $sortBy,
                'sort_order' => $sortOrder,
            ]));

            // Cache results for 10 minutes
            $users = Cache::remember($cacheKey, 600, function () use ($searchTerm, $page, $perPage, $sortBy, $sortOrder) {
                $query = V4User::query()
                    ->whereIn('role', ['super-admin', 'admin', 'manager'])
                    ->with([
                        'superAdminProfile:id,v4_user_id,is_verified',
                    ]);

                if (!empty($searchTerm)) {
                    $query->where(function ($q) use ($searchTerm) {
                        $q->where('first_name', 'ilike', "%{$searchTerm}%")
                            ->orWhere('last_name', 'ilike', "%{$searchTerm}%")
                            ->orWhere('email', 'ilike', "%{$searchTerm}%")
                            ->orWhere('phone', 'ilike', "%{$searchTerm}%");
                    });
                }

                $query->orderBy($sortBy, $sortOrder);

                return $query->paginate($perPage, ['*'], 'page', $page);
            });

            $data = $users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'status' => $user->is_suspended
                        ? 'suspended'
                        : ($user->is_banned ? 'banned' : 'active'),
                    'role' => $user->role,
                    'country' => $user->country,
                    'createdAt' => $user->created_at,
                    'age' => $user->age,
                    'phone' => $user->phone,
                    'avatar' => $user->profile_picture,
                    'profileData' => $user->profile_data,
                ];
            });

            return response()->json([
                'data' => $data,
                'pagination' => [
                    'total' => $users->total(),
                    'per_page' => $users->perPage(),
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'from' => $users->firstItem() ?? 0,
                    'to' => $users->lastItem() ?? 0,
                    'has_more_pages' => $users->hasMorePages(),
                ],
            ]);
        } catch (ValidationException $e) {
            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => 'unknown_method',
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function toggleVerificationEvaluator($id): JsonResponse
    {
        try {
            $user = V4User::findOrFail($id);

            // Check if the user has the 'evaluator' role
            if ($user->role !== 'evaluator') {
                return response()->json([
                    'success' => false,
                    'message' => 'User is not an evaluator.',
                ], 403);
            }

            // Check if evaluator profile exists
            if (!$user->evaluatorProfile) {
                return response()->json([
                    'success' => false,
                    'message' => 'Evaluator profile not found for this user.',
                ], 404);
            }

            // Toggle the is_verified flag
            $user->evaluatorProfile->is_verified = !$user->evaluatorProfile->is_verified;
            $user->evaluatorProfile->save();

            // Refresh the relationship
            $user->load('evaluatorProfile');

            // Get the updated status
            $status = $user->evaluatorProfile->is_verified ? 'verified' : 'unverified';

            return response()->json([
                'success' => true,
                'message' => "Evaluator has been successfully {$status}.",
                'data' => $user,
            ]);
        } catch (ModelNotFoundException $e) {
            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => 'unknown_method',
            ]);
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
                'errors' => $e,
            ], 404);
        } catch (ValidationException $e) {
            Log::error(
                'An Validation error occurred.' . $e->getMessage(),
                [
                    'user_id' => Auth::id(),
                    'user_id' => $id,
                    'trace' => $e->getTraceAsString(),
                ]
            );
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => 'unknown_method',
            ]);
            Log::error(
                'An unexpected error occurred.' . $e->getMessage(),
                [
                    'user_id' => Auth::id(),
                    'user_id' => $id,
                    'trace' => $e->getTraceAsString(),
                ]
            );
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function getAdminUserDetailsById($id): JsonResponse
    {
        try {

            $user = V4User::findOrFail($id);
            $user->adminRole = str_replace('-', '', $user->role);

            $user->systemStats = [
                'usersManaged' => V4User::whereNotIn('role', ['super-admin'])->count(),
            ];

            $user->lastLogin = $user->updated_at;

            $user->status = "active";

            return response()->json($user);
        } catch (ValidationException $e) {
            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => 'unknown_method',
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function getUserAdminDetailsById($id): JsonResponse
    {
        try {
            $user = V4User::findOrFail($id);

            $profileData = null;
            switch ($user->role) {
                case 'player':
                    $user->load('playerProfile');
                    $profileData = $user->playerProfile;
                    break;
                case 'coach':
                    $user->load('coachProfile');
                    $profileData = $user->coachProfile;
                    break;
                case 'team':
                    $user->load('teamProfile.team');
                    $profileData = $user->teamProfile;
                    break;
                case 'scout':
                    $user->load('scoutProfile');
                    $profileData = $user->scoutProfile;
                    break;
                case 'academy':
                    $user->load('academyProfile.academy');
                    $profileData = $user->academyProfile;
                    break;
                case 'organizer':
                    $user->load('organizerProfile');
                    $profileData = $user->organizerProfile;
                    break;
                case 'adviser':
                    $user->load('adviserProfile');
                    $profileData = $user->adviserProfile;
                    break;
                case 'parent':
                    $user->load('parentProfile');
                    $profileData = $user->parentProfile;
                    $user->load('children.playerProfile');
                    break;
                case 'fan':
                    $user->load('fanProfile');
                    $profileData = $user->fanProfile;
                    break;
                case 'evaluator':
                    $user->load('evaluatorProfile');
                    $profileData = $user->evaluatorProfile;
                    break;
            }

            return response()->json([
                'id' => $user->id,
                'profilePicture' => $user->profile_photo,
                'fullName' => $user->name,
                'status' => $user->is_suspended
                    ? 'suspended'
                    : ($user->is_banned ? 'banned' : 'active'),
                'basicInfo' => [
                    'firstName' => $user->first_name,
                    'lastName' => $user->last_name,
                    'fullName' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'country' => $user->country,
                    'dateOfBirth' => $user->date_of_birth,
                    'province' => $user->state ?? $user->province ?? null,
                    'city' => $user->city,
                    'league' => $profileData->leagues ?? null,
                    'leagues' => $profileData->leagues ?? null,
                    'team' => $profileData->teams ?? null,
                    'weight' => $profileData->weight ?? null,
                    'height' => $profileData->height ?? null,
                    'position' => $profileData->position ?? null,
                    'handedness' => $profileData->handedness ?? null,
                    'administratorFullName' => $profileData->administrator_name ?? null,
                    'administratorEmail' => $profileData->email ?? null,
                    'teamWebsite' => $profileData->website ?? null,
                    'teamAddress' => $profileData->teamAddress ?? null,
                    'teamCity' => $user->city ?? null,
                    'teamStateProvince' => $user->state . ',' . $user->province,
                    'state' => $user->state,
                    'teamZipPostalCode' => $user->zip,
                    'stateProvince' => $user->state . ',' . $user->province,
                    'teamCountry' => $user->country,
                    'yearsRunning' => $profileData->team_years_running ?? null,
                    'specializations' => MarketplaceTypes::all(),
                    'academyName' => $user->academyProfile->academy->academy_name ?? null,
                    'teamName' => $user->teamProfile->team->team_name ?? null,
                ],
                'socialStats' => [
                    'followers' => $user->followers_count,
                    'following' => $user->followings_count
                ],
            ]);
        } catch (ValidationException $e) {
            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => 'unknown_method',
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function updateUserAdminDetailsById(Request $request, $id): JsonResponse
    {
        try {
            $user = V4User::findOrFail($id);

            if ($request->has('fullName')) {
                $parts = explode(' ', $request->fullName, 2);
                $request->merge([
                    'first_name' => $parts[0] ?? null,
                    'last_name' => $parts[1] ?? null,
                ]);
            }

            if ($request->has('basicInfo')) {

                $basic = $request->basicInfo;

                $request->merge([
                    'email' => $basic['email'] ?? null,
                    'phone' => $basic['phone'] ?? null,
                    'country' => $basic['country'] ?? null,
                    'state' => $basic['province'] ?? null,
                    'city' => $basic['city'] ?? null,
                    'date_of_birth' => $basic['dateOfBirth'] ?? null,

                    'handedness' => $basic['handedness'] ?? null,
                    'weight' => $basic['weight'] ?? null,
                    'height' => $basic['height'] ?? null,
                    'position' => $basic['position'] ?? null,

                    'teams' => isset($basic['team']) ? [$basic['team']] : null,
                    'leagues' => isset($basic['league']) ? [$basic['league']] : null,
                ]);

                if (isset($basic['teamName'])) {

                    $adminParts = explode(' ', $basic['administratorFullName'] ?? '', 2);

                    $request->merge([
                        'phone' => $basic['organizationPhone'] ?? null,
                        'country' => $basic['teamCountry'] ?? null,
                        'state' => $basic['state'] ?? $basic['province'] ?? null,
                        'city' => $basic['teamCity'] ?? null,
                        'zip' => $basic['teamZipPostalCode'] ?? null,

                        'team_name' => $basic['teamName'] ?? null,
                        'administrator_first_name' => $adminParts[0] ?? null,
                        'administrator_last_name' => $adminParts[1] ?? null,
                        'website' => $basic['teamWebsite'] ?? null,
                        'address' => $basic['teamAddress'] ?? null,
                        'team_years_running' => isset($basic['yearsRunning'])
                            ? (int) $basic['yearsRunning']
                            : null,
                        'leagues' => [$basic['league'] ?? null],
                    ]);
                }
                if ($user->role === 'academy') {

                    // Administrator full name (academy owner/director)

                    $request->merge([
                        'academy_name' => $basic['academyName'] ?? null,
                        'administrator_first_name' => $adminParts[0] ?? null,
                        'administrator_last_name' => $adminParts[1] ?? null,

                        // academy contact
                        'phone' => $basic['phone'] ?? null,
                        'address' => $basic['address'] ?? null,
                        'city' => $basic['city'] ?? null,
                        'state' => $basic['province'] ?? null,
                        'country' => $basic['country'] ?? null,

                        // leagues array
                        'leagues' => !empty($basic['leagues']) ? $basic['leagues'] : [],

                        // founded → academy_years_running
                        'academy_years_running' =>
                            !empty($basic['founded']) ? (int) $basic['founded'] : null,
                    ]);
                }
            }

            $rules = [
                'team_id' => 'nullable|exists:v4_teams,id',
                'academy_id' => 'nullable|exists:v4_academies,id',
                'first_name' => 'nullable|string|max:255',
                'last_name' => 'nullable|string|max:255',
                'email' => 'nullable|email',
                'phone' => 'nullable|string|max:20',
                'country' => 'nullable|string|max:100',
                'state' => 'nullable|string|max:100',
                'city' => 'nullable|string|max:100',
                'date_of_birth' => 'nullable|date',
                'zip' => 'nullable|string|max:20',

                'enable_private_account' => 'nullable|boolean',
                'receive_news_offers' => 'nullable|boolean',
            ];

            if ($request->hasFile('profile_photo')) {
                $rules['profile_photo'] = 'file|image|max:5120';
            }

            $validated = $request->validate($rules);

            if ($request->hasFile('profile_photo')) {
                $path = $request->file('profile_photo')->store("profile_photos/{$user->id}", 's3');
                $validated['profile_photo'] = Storage::disk('s3')->url($path);
            }

            $user->update($validated);
            $user->refresh();

            switch ($user->role) {

                case 'player':
                    $playerValidated = $request->validate([
                        'teams' => 'nullable|array',
                        'leagues' => 'nullable|array',
                        'handedness' => 'nullable|in:left,right,ambidextrous',
                        'weight' => 'nullable|numeric',
                        'height' => 'nullable|numeric',
                        'position' => 'nullable|string|max:100',
                        'gender' => 'nullable|in:male,female,other',
                    ]);

                    $user->playerProfile()->updateOrCreate(
                        ['v4_user_id' => $user->id],
                        $playerValidated
                    );

                    $user->load('playerProfile');
                    break;

                case 'coach':
                    $coachValidated = $request->validate([
                        'leagues' => 'nullable|array',
                        'teams' => 'nullable|array',
                    ]);
                    $user->coachProfile()->updateOrCreate([], $coachValidated);
                    $user->load('coachProfile');
                    break;

                case 'team':
                    $teamData = $request->validate([
                        'team_name' => 'nullable|string|max:255',
                        'administrator_first_name' => 'nullable|string|max:255',
                        'administrator_last_name' => 'nullable|string|max:255',
                        'leagues' => 'nullable|array',
                        'website' => 'nullable|string|max:255',
                        'address' => 'nullable|string|max:255',
                        'team_years_running' => 'nullable|integer',
                    ]);

                    if (!empty($validated['phone'])) {
                        $teamData['phone'] = $validated['phone'];
                    }

                    if (!empty($validated['profile_photo'])) {
                        $teamData['profile_photo'] = $validated['profile_photo'];
                    }

                    $teamProfile = $user->teamProfile;
                    if (!$teamProfile || !$teamProfile->team) {
                        throw new Exception("Team profile or team not found.");
                    }

                    $team = $teamProfile->team;
                    $team->update($teamData);

                    $user->load('teamProfile.team');
                    break;

                case 'academy':
                    $academyData = $request->validate([
                        "academy_name" => "nullable|string|max:255",
                        "administrator_first_name" => "nullable|string|max:255",
                        "administrator_last_name" => "nullable|string|max:255",
                        "leagues" => "nullable|array",
                        "website" => "nullable|string|max:255",
                        "address" => "nullable|string|max:255",
                        "academy_years_running" => "nullable|integer",
                    ]);

                    if (isset($academyData['leagues']) && is_array($academyData['leagues'])) {
                        $academyData['leagues'] = array_filter($academyData['leagues']);
                    }

                    if (!empty($validated['phone'])) {
                        $academyData['phone'] = $validated['phone'];
                    }

                    if (!empty($validated['profile_photo'])) {
                        $academyData['profile_photo'] = $validated['profile_photo'];
                    }

                    $academyProfile = $user->academyProfile;

                    if (!$academyProfile || !$academyProfile->academy) {
                        throw new Exception("Academy profile or academy record not found for user.");
                    }

                    $academyModel = $academyProfile->academy;

                    $academyModel->update($academyData);

                    $user->load('academyProfile.academy');
                    break;

                case 'scout':
                case 'adviser':
                case 'evaluator':

                    $baseRules = [
                        'leagues' => 'nullable|array',
                        'teams' => 'nullable|array',
                        'address' => 'nullable|string|max:255',
                        'level_hockey_played' => 'nullable|string|max:255',
                        'current_involvement_level' => 'nullable|string|max:255',
                        'current_sport_role' => 'nullable|string|max:255',
                        'number_of_years_experience' => 'nullable|integer',
                        'resume' => 'nullable|file|mimes:pdf|max:10240',
                        'references' => 'nullable|array',
                        'references.*.name' => 'required_with:references|string|max:255',
                        'references.*.email' => 'required_with:references|email|max:255',
                        'references.*.phone' => 'required_with:references|string|max:20',
                    ];

                    $validatedProfile = $request->validate($baseRules);

                    if ($request->hasFile('resume')) {
                        $path = $request->file('resume')->store("resume/{$user->id}", 's3');
                        $validatedProfile['resume'] = Storage::disk('s3')->url($path);
                    }

                    $relation = $user->role . 'Profile';
                    $user->{$relation}()->updateOrCreate([], $validatedProfile);

                    $user->load($relation);
                    break;

                case 'organizer':
                    $organizerValidated = $request->validate([
                        "business_name" => "nullable|string|max:255",
                        "business_phone" => "nullable|string|max:20",
                        "address" => "nullable|string|max:255",
                        "website" => "nullable|string|max:255",
                        "number_years_organizing" => "nullable|integer",
                        "leagues" => "nullable|array",
                        "link_of_previous_events" => "nullable|array",
                        "number_of_events_organized" => "nullable|integer",
                    ]);
                    $user->organizerProfile()->updateOrCreate([], $organizerValidated);
                    $user->load('organizerProfile');
                    break;

                case 'parent':
                    $user->parentProfile()->updateOrCreate([], []);
                    $user->load('parentProfile');
                    break;

                case 'fan':
                    $user->fanProfile()->updateOrCreate([], []);
                    $user->load('fanProfile');
                    break;
            }

            $userData = $user->toArray();

            unset(
                $userData['player_profile'],
                $userData['coach_profile'],
                $userData['team_profile'],
                $userData['scout_profile'],
                $userData['academy_profile'],
                $userData['organizer_profile'],
                $userData['adviser_profile'],
                $userData['parent_profile'],
                $userData['fan_profile'],
                $userData['evaluator_profile']
            );

            $profileRelation = $user->role . 'Profile';
            $userData['profile'] = $user->{$profileRelation};

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'user' => $userData,
            ]);
        } catch (ValidationException $e) {
            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => 'unknown_method',
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Profile update failed.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function getUserDetailsById($id): JsonResponse
    {
        try {

            $authUser = Auth::guard('v4api')->user();

            $user = V4User::findOrFail($id);

            $profileData = null;
            switch ($user->role) {
                case 'player':
                    $user->load('playerProfile');
                    $profileData = $user->playerProfile;
                    break;
                case 'coach':
                    $user->load('coachProfile');
                    $profileData = $user->coachProfile;
                    break;
                case 'team':
                    $user->load('teamProfile.team');
                    $profileData = $user->teamProfile;
                    break;
                case 'scout':
                    $user->load('scoutProfile');
                    $profileData = $user->scoutProfile;
                    break;
                case 'academy':
                    $user->load('academyProfile.academy.teams');
                    $profileData = $user->academyProfile;
                    break;
                case 'organizer':
                    $user->load('organizerProfile');
                    $profileData = $user->organizerProfile;
                    break;
                case 'adviser':
                    $user->load('adviserProfile');
                    $profileData = $user->adviserProfile;
                    break;
                case 'parent':
                    $user->load('parentProfile');
                    $profileData = $user->parentProfile;
                    $user->load('children.playerProfile');
                    break;
                case 'fan':
                    $user->load('fanProfile');
                    $profileData = $user->fanProfile;
                    break;
                case 'evaluator':
                    $user->load('evaluatorProfile');
                    $profileData = $user->evaluatorProfile;
                    break;
            }

            // Create a standardized response
            $userData = $user->toArray();

            // Remove the specific profile fields to avoid duplication
            unset(
                $userData['player_profile'],
                $userData['coach_profile'],
                $userData['team_profile'],
                $userData['scout_profile'],
                $userData['academy_profile'],
                $userData['organizer_profile'],
                $userData['adviser_profile'],
                $userData['parent_profile'],
                $userData['fan_profile']
            );

            // Add the profile data under a standardized field name
            $userData['profile'] = $profileData;

            $userData['is_following'] = $user->isFollowedBy($authUser->id);
            $userData['has_received_request'] = $user->hasSendPendingRequest($authUser->id);
            $userData['note'] = $user->getFollowNote($authUser->id);
            $userData['has_sent_request'] = $user->hasPendingRequest($authUser->id);

            $userData['conversation_id'] = $user->getConversationWith($authUser->id);

            $userData['is_favourite'] = FavouriteUser::isFavourite($authUser->id, $user->id);


            if ($authUser->role == 'team') {
                $authUser->load('teamProfile.team');
                $userData['is_team_members'] = false;
                if ($authUser->teamProfile && $authUser->teamProfile->team) {
                    $userData['is_team_members'] = $authUser->teamProfile->team->isMember($user->id);
                }
            }

            if ($authUser->role === 'academy') {
                $authUser->load('academyProfile.academy.teams');
                $userData['academy_teams_member'] = $authUser->academyProfile->academy->adminAcademiesTeamsWithMember($user->id);
                $userData['academy_members'] = $authUser->academyProfile->academy->adminAcademiesMembers($user->id);
            }

            return response()->json([
                'success' => true,
                'message' => 'Profile data retrieved successfully',
                'user' => $userData,
            ]);
        } catch (ValidationException $e) {
            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => 'unknown_method',
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function getUserMediaDetailsById($id): JsonResponse
    {
        try {
            $user = V4User::findOrFail($id);

            $posts = V4Post::with(['media'])->where('user_id', $id)->get();
            $images = [];
            $videos = [];

            foreach ($posts as $post) {
                foreach ($post->media as $media) {

                    $mediaItem = [
                        'id' => $media->id,
                        'url' => $media->url, // change if your column name differs
                        'uploadedAt' => $media->created_at,
                    ];

                    if ($media->type === 'image') {
                        $images[] = $mediaItem;
                    } elseif ($media->type === 'video') {
                        $videos[] = $mediaItem;
                    }
                }
            }
            return response()->json([
                'images' => $images,
                'videos' => $videos,
            ]);
        } catch (ValidationException $e) {
            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => 'unknown_method',
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function getUserChildrenDetailsById($id): JsonResponse
    {
        try {
            $users = V4User::where('parent_id', $id)
                ->get();
            $children = collect();
            foreach ($users as $user) {
                $baseInfo = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'dateOfBirth' => $user->date_of_birth,
                    'gender' => $user->playerProfile->gender,
                    'position' => $user->playerProfile->position,
                    'team' => $user->playerProfile->teams,
                    'username' => $user->username,
                ];
                $children->push($baseInfo);
            }
            return response()->json([
                'children' => $children
            ]);
        } catch (ValidationException $e) {
            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => 'unknown_method',
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function getUserPlayersDetailsById($id): JsonResponse
    {
        try {
            $users = V4User::with([
                'teamProfile.team.members.player' => function ($q) {
                    $q->where('role', 'player');
                },
                'teamProfile.team.members.player.playerProfile'
            ])->findOrFail($id);

            $members = $users->teamProfile->team->members;

            $players = $members
                ->filter(fn($member) => $member->player !== null)
                ->map(function ($member) {
                    $player = $member->player;

                    return [
                        'id' => $player->id,
                        'name' => $player->name,
                        'position' => $player->playerProfile->position ?? null,
                        'age' => $player->age,
                        'height' => $player->playerProfile->height ?? null,
                        'weight' => $player->playerProfile->weight ?? null,
                        'avatar' => $player->profile_photo,
                        'role' => $player->role,
                        'status' => $player->is_suspended
                            ? 'suspended'
                            : ($player->is_banned ? 'banned' : 'active'),
                    ];
                })
                ->values();

            return response()->json($players);
        } catch (ValidationException $e) {
            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => 'unknown_method',
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function getUserCoachesDetailsById($id): JsonResponse
    {
        try {
            $users = V4User::with([
                'academyProfile.academy.members.player' => function ($q) {
                    $q->where('role', 'coach');
                },
                'academyProfile.academy.members.player.coachProfile',
                'teamProfile.team.members.player' => function ($q) {
                    $q->where('role', 'coach');
                },
                'teamProfile.team.members.player.coachProfile'
            ])->findOrFail($id);

            if ($users->role == "team") {
                $members = $users->teamProfile->team->members;

                $players = $members
                    ->filter(fn($member) => $member->player !== null)
                    ->map(function ($member) {
                        $player = $member->player;

                        return [
                            'id' => $player->id,
                            'name' => $player->name,
                            'position' => $player->coachProfile->position ?? null,
                            'age' => $player->age,
                            'height' => $player->coachProfile->height ?? null,
                            'weight' => $player->coachProfile->weight ?? null,
                            'avatar' => $player->profile_photo,
                            'role' => $player->role,
                            'experience' => null,
                            'status' => $player->is_suspended
                                ? 'suspended'
                                : ($player->is_banned ? 'banned' : 'active'),
                        ];
                    })
                    ->values();

                return response()->json($players);
            } else {
                $members = $users->academyProfile->academy->members;
                $players = $members
                    ->filter(fn($member) => $member->player !== null)
                    ->map(function ($member) {
                        $player = $member->player;

                        return [
                            'id' => $player->id,
                            'name' => $player->name,
                            'position' => $player->coachProfile->position ?? null,
                            'age' => $player->age,
                            'height' => $player->coachProfile->height ?? null,
                            'weight' => $player->coachProfile->weight ?? null,
                            'avatar' => $player->profile_photo,
                            'role' => $player->role,
                            'experience' => null,
                            'status' => $player->is_suspended
                                ? 'suspended'
                                : ($player->is_banned ? 'banned' : 'active'),
                        ];
                    })
                    ->values();

                return response()->json(['coaches' => $players]);
            }
        } catch (ValidationException $e) {
            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => 'unknown_method',
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function getUserTeamsDetailsById($id): JsonResponse
    {
        try {

            $user = V4User::with([
                'academyProfile.academy'
            ])->findOrFail($id);

            if ($user->role == 'academy') {
                $result = V4Team::where('academy_id', $user->academyProfile->academy->id)
                    ->get();

                $teams = $result
                    ->map(function ($member) {
                        $player = $member;
                        return [
                            'id' => $player->id,
                            'name' => $player->team_name,
                            'leagues' => $player->leagues ?? null,
                            'startDate' => $player->created_at,
                            'players' => $player->player_members_count,
                            'logo' => $player->profile_photo,
                            'status' => $player->is_suspended
                                ? 'suspended'
                                : ($player->is_banned ? 'banned' : 'active'),
                        ];
                    })
                    ->values();


                return response()->json(['teams' => $teams]);
            } else {
                $result = TeamMember::with([
                    'team'
                ])->where('player_id', $id)->get();


                $teams = $result
                    ->map(function ($member) {
                        $player = $member->team;
                        return [
                            'id' => $player->id,
                            'name' => $player->team_name,
                            'leagues' => $player->leagues ?? null,
                            'startDate' => $player->created_at,
                            'players' => $player->player_members_count,
                            'logo' => $player->profile_photo,
                            'status' => $player->is_suspended
                                ? 'suspended'
                                : ($player->is_banned ? 'banned' : 'active'),
                        ];
                    })
                    ->values();


                return response()->json(['teams' => $teams]);
            }
        } catch (ValidationException $e) {
            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => 'unknown_method',
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function getUserReportsDetailsById($id): JsonResponse
    {
        try {
            $result = V4UserReport::with([
                'reportedUser',
                'reportingUser',
                'reason'
            ])->where('reported_user_id', $id)->get();

            return response()->json($result);
        } catch (ValidationException $e) {
            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => 'unknown_method',
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function getUserScoutsDetailsById($id): JsonResponse
    {
        try {
            $users = V4User::with([
                'academyProfile.academy.members.player' => function ($q) {
                    $q->where('role', 'scout');
                },
                'academyProfile.academy.members.player.scoutProfile',
                'teamProfile.team.members.player' => function ($q) {
                    $q->where('role', 'scout');
                },
                'teamProfile.team.members.player.scoutProfile'
            ])->findOrFail($id);

            if ($users->role == "team") {
                $members = $users->teamProfile->team->members;

                $players = $members
                    ->filter(fn($member) => $member->player !== null)
                    ->map(function ($member) {
                        $player = $member->player;

                        return [
                            'id' => $player->id,
                            'name' => $player->name,
                            'position' => $player->scoutProfile->position ?? null,
                            'age' => $player->age,
                            'height' => $player->scoutProfile->height ?? null,
                            'weight' => $player->scoutProfile->weight ?? null,
                            'avatar' => $player->profile_photo,
                            'role' => $player->role,
                            'experience' => null,
                            'status' => $player->is_suspended
                                ? 'suspended'
                                : ($player->is_banned ? 'banned' : 'active'),
                        ];
                    })
                    ->values();

                return response()->json($players);
            } else {
                $members = $users->academyProfile->academy->members;
                $players = $members
                    ->filter(fn($member) => $member->player !== null)
                    ->map(function ($member) {
                        $player = $member->player;

                        return [
                            'id' => $player->id,
                            'name' => $player->name,
                            'position' => $player->scoutProfile->position ?? null,
                            'age' => $player->age,
                            'height' => $player->scoutProfile->height ?? null,
                            'weight' => $player->scoutProfile->weight ?? null,
                            'avatar' => $player->profile_photo,
                            'role' => $player->role,
                            'experience' => null,
                            'status' => $player->is_suspended
                                ? 'suspended'
                                : ($player->is_banned ? 'banned' : 'active'),
                        ];
                    })
                    ->values();

                return response()->json(['coaches' => $players]);
            }
        } catch (ValidationException $e) {
            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => 'unknown_method',
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function getUserAdminsDetailsById($id): JsonResponse
    {
        try {
            $users = V4User::with([
                'teamProfile.team.admins'
            ])->findOrFail($id);

            $admins = $users->teamProfile->team->admins;
            $players = $admins
                ->map(function ($admin) {
                    $player = $admin;
                    return [
                        'id' => $player->id,
                        'name' => $player->name,
                        'role' => $player->designation,
                        'phone' => $player->phone,
                        'email' => $player->email,
                        'age' => $player->age,
                        'avatar' => $player->profile_photo,
                    ];
                })
                ->values();

            return response()->json($players);
        } catch (ValidationException $e) {
            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => 'unknown_method',
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function getUserEvaluationsDetailsById($id): JsonResponse
    {
        try {

            $assignments = EvaluatorAssignment::with([
                'evaluation',
                'submission.paymentRequest.inAppPurchase.marketplaceItem',
            ])->where('evaluator_id', $id)
                // ->whereIn('status', [EvaluatorAssignment::STATUS_COMPLETED, ])
                ->orderBy('assigned_at', 'desc')
                ->get();
            $formattedEvaluations = collect();

            foreach ($assignments as $assignment) {
                if ($assignment->evaluation != null) {
                    $overallRating = $assignment->evaluation->computeAggregatedRating() ?? $assignment->evaluation->overall_rating;
                }
                $baseData = [
                    'id' => $assignment->id,
                    'playerName' => $assignment->submission->player->name,
                    'playerPosition' => $assignment->submission->player->playerProfile->position,
                    'evaluationDate' => Carbon::parse($assignment->created_at)->format('d-m-Y'),
                    'overallRating' => $overallRating ?? null,
                    'status' => $assignment->status,
                    'category' => $assignment->submission->paymentRequest->inAppPurchase->marketplaceItem->title
                ];

                $formattedEvaluations->push($baseData);
            }


            return response()->json(
                [
                    'evaluations' => $formattedEvaluations,
                ]
            );
        } catch (ValidationException $e) {
            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => 'unknown_method',
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function getUserEvaluationDetailsById($id): JsonResponse
    {
        try {

            $submission = EvaluationSubmission::with([
                'paymentRequest.inAppPurchase.marketplaceItems',
            ])
                ->where('player_id', $id)
                ->whereIn('status', [
                    EvaluationSubmission::STATUS_COMPLETED
                ])
                ->whereHas('paymentRequest.inAppPurchase.marketplaceItems', function ($q) {
                    $q->where('type', MarketplaceTypes::PERSONALIZED_VIDEO_EVALUATION);
                })
                ->latest()
                ->first();

            if (!$submission) {
                return response()->json([
                    'skating' => null,
                    'compete' => null,
                    'hockeyIQ' => null,
                    'skills' => null,
                ], 404);
            }


            $submission->load([
                'evaluation.answers:id,evaluation_id,question_id,rating',
                'evaluation.answers.question:id,category_id',
                'evaluation.answers.question.category:id,slug,name',
            ]);

            $result = [
                'skating' => null,
                'compete' => null,
                'hockeyIQ' => null,
                'skills' => null,
            ];

            foreach ($submission->evaluation->answers as $answer) {
                $category = $answer->question->category ?? null;

                if (!$category) {
                    continue;
                }

                switch ($category->slug) {
                    case 'skating':
                        $result['skating'] = $answer->rating;
                        break;

                    case 'compete':
                        $result['compete'] = $answer->rating;
                        break;

                    case 'hockey-iq':
                        $result['hockeyIQ'] = $answer->rating; // convert slug → camelCase
                        break;

                    case 'skills':
                        $result['skills'] = $answer->rating;
                        break;
                }
            }

            return response()->json(
                $result
            );
        } catch (ValidationException $e) {
            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => 'unknown_method',
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function getUserAchievementsDetailsById($id): JsonResponse
    {
        try {

            $achievements = V4PlayerAchievement::where('player_id', $id)
                ->orderBy('created_at', 'asc')
                ->get();

            return response()->json(
                $achievements
            );
        } catch (ValidationException $e) {
            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => 'unknown_method',
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }


    public function getUserPortfolioDetailsById($id): JsonResponse
    {
        try {
            $portfolios = V4PlayerPortfolio::with(['subs.subable', 'player'])
                ->where(function ($q) use ($id) {
                    $q->where('is_public', true)
                        ->orWhere('player_id', $id);
                })
                ->orderBy('created_at', 'desc')
                ->get();

            $achievementsMap = [];
            $videosMap = [];

            foreach ($portfolios as $portfolio) {
                foreach ($portfolio->subs as $sub) {
                    if (!$sub->subable) {
                        continue;
                    }

                    switch ($sub->subable_type) {

                        case V4PlayerAchievement::class:
                            $achievementsMap[$sub->subable->id] = [
                                'id' => $sub->subable->id,
                                'title' => $sub->subable->title ?? null,
                                'file_path' => $sub->subable->file_path ?? null,
                                'details' => $sub->subable->details ?? null,
                                'description' => $sub->subable->description ?? null,
                            ];
                            break;

                        case V4UploadedMedia::class:
                            $videosMap[$sub->subable->id] = [
                                'id' => $sub->subable->id,
                                'title' => $sub->subable->meta['original_name'] ?? null,
                                'file_path' => $sub->subable->file_path ?? null,
                            ];
                            break;

                        default:
                            break;
                    }
                }
            }

            // Convert maps to indexed arrays
            $allAchievements = array_values($achievementsMap);
            $allVideos = array_values($videosMap);

            return response()->json([
                'success' => true,
                'achievements' => $allAchievements,
                'videos' => $allVideos,
            ], 200);
        } catch (ValidationException $e) {
            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => 'unknown_method',
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function getAllAvailableEvaluators(Request $request): JsonResponse
    {
        try {
            // Validate incoming request
            $validated = $request->validate([
                'q' => 'nullable|string|max:255',
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100',
                'sort_by' => 'nullable|string|in:first_name,last_name,role',
                'sort_order' => 'nullable|string|in:asc,desc',
            ]);

            // Assign variables with default values
            $searchTerm = $validated['q'] ?? '';
            $page = $validated['page'] ?? 1;
            $perPage = $validated['per_page'] ?? 1000;
            $sortBy = $validated['sort_by'] ?? 'first_name';
            $sortOrder = $validated['sort_order'] ?? 'asc';

            // Build query for evaluators
            $query = V4User::query()
                ->where('role', 'evaluator')
                ->with([
                    'evaluatorProfile',
                    'evaluatorAssignments'
                ])
                ->whereHas('evaluatorProfile', function ($q) {
                    $q->where('is_verified', true); // Ensure only verified evaluators are included
                });

            // Apply search filter if provided
            if (!empty($searchTerm)) {
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('first_name', 'ilike', "%{$searchTerm}%")
                        ->orWhere('last_name', 'ilike', "%{$searchTerm}%");
                });
            }

            // Apply sorting
            $query->orderBy($sortBy, $sortOrder);

            // Fetch paginated users
            $users = $query->paginate($perPage, ['*'], 'page', $page);

            // Apply the map function on the items (not the paginator)
            $data = $users->items(); // Get the items collection
            $data = collect($data)->map(function ($user) {
                $incompleteAssignmentCount = $user->evaluatorAssignments
                    ->filter(fn($a) => $a->status === 'pending')
                    ->count();

                $completeAssignmentCount = $user->evaluatorAssignments
                    ->filter(fn($a) => in_array($a->status, ['complete', 'rejected']))
                    ->count();
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'status' => 'pending_assignment',
                    'isAvailable' => true,
                    'specializations' => MarketplaceTypes::all(),
                    "profileData" => [
                        'is_verified' => $user->evaluatorProfile->is_verified,
                    ],
                    "currentWorkload" => $incompleteAssignmentCount,
                    "rating" => 4.8,
                    "completedEvaluations" => $completeAssignmentCount,
                ];
            });

            // Return JSON response with data and pagination
            return response()->json([
                'data' => $data,
                'pagination' => [
                    'total' => $users->total(),
                    'per_page' => $users->perPage(),
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'from' => $users->firstItem() ?? 0,
                    'to' => $users->lastItem() ?? 0,
                    'has_more_pages' => $users->hasMorePages(),
                ],
            ]);
        } catch (ValidationException $e) {
            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => 'unknown_method',
            ]);
            // Return validation error response
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            // Log the error for better debugging
            Log::error('Error fetching evaluators: ' . $e->getMessage(), [
                'exception' => $e,
                'request' => $request->all(),
            ]);

            // Return generic error response
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function setEvaluationVisibility(Request $request): JsonResponse
    {
        try {
            $user = Auth::guard('v4api')->user();

            if (!$user || $user->role !== 'player') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only players can update evaluations.',
                ], 403);
            }

            $validated = $request->validate([
                'evaluation_id' => 'required|integer|exists:evaluations,id',
                'is_public' => 'nullable|boolean',
            ]);

            $evaluationId = $validated['evaluation_id'];
            $isPublic = $validated['is_public'] ?? false;
            $playerId = $user->id;

            $evaluation = Evaluation::with([
                'submission.paymentRequest.inAppPurchase.marketplaceItems'
            ])->find($evaluationId);

            if (!$evaluation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Evaluation not found.',
                ], 404);
            }

            if ($evaluation->submission->player_id !== $playerId) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot modify evaluations of other players.',
                ], 403);
            }

            $marketplaceItem = $evaluation->submission->paymentRequest
                ->inAppPurchase->marketplaceItems->first();

            if (
                !$marketplaceItem ||
                $marketplaceItem->type !== MarketplaceTypes::PERSONALIZED_VIDEO_EVALUATION
            ) {

                return response()->json([
                    'success' => false,
                    'message' => 'Only personalized video evaluations can be selected.',
                ], 400);
            }

            if ($evaluation->status !== Evaluation::STATUS_SUBMITTED) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only completed evaluations can be selected.',
                ], 400);
            }

            DB::beginTransaction();

            try {
                Evaluation::whereHas('submission', function ($q) use ($playerId) {
                    $q->where('player_id', $playerId);
                })
                    ->where('id', '!=', $evaluation->id)
                    ->update([
                        'is_selected' => false,
                        'is_public' => false,
                    ]);


                // Update selected evaluation
                $evaluation->is_selected = true;
                $evaluation->is_public = $isPublic ? true : false;
                $evaluation->save();

                DB::commit();
            } catch (\Throwable $e) {
                // Track error in Sentry
                $this->errorTracker->captureException($e, [
                    'action' => 'unknown_method',
                ]);
                DB::rollBack();
                throw $e;
            }

            return response()->json([
                'success' => true,
                'message' => 'Evaluation visibility updated successfully.',
                'data' => [
                    'evaluation_id' => $evaluation->id,
                    'is_selected' => $evaluation->is_selected,
                    'is_public' => $evaluation->is_public,
                ],
            ], 200);
        } catch (Exception $e) {
            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => 'unknown_method',
            ]);
            Log::error('Error updating evaluation visibility: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update evaluation visibility.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }



    // Player Achievements
    /**
     * Get all achievements for authenticated player
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getAchievements(Request $request, int $userId): JsonResponse
    {
        try {
            $user = V4User::find($userId);

            // Validate user must be a player
            if (!$user || $user->role !== 'player') {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only players can view achievements.',
                ], 403);
            }

            $playerId = $user->id;

            // 1️⃣ Fetch Achievements
            $achievements = V4PlayerAchievement::where('player_id', $playerId)
                ->orderBy('created_at', 'asc')
                ->get();

            // 2️⃣ Fetch Completed Evaluations (same logic from getStatusFilteredMyReports)
            $completedSubmissions = EvaluationSubmission::with([
                'paymentRequest.inAppPurchase.marketplaceItems',
            ])
                ->where('player_id', $playerId)
                ->whereIn('status', [
                    EvaluationSubmission::STATUS_COMPLETED
                ])
                ->whereHas('paymentRequest.inAppPurchase.marketplaceItems', function ($q) {
                    $q->where('type', MarketplaceTypes::PERSONALIZED_VIDEO_EVALUATION);
                })
                ->orderBy('created_at', 'desc')
                ->get();

            $evaluations = $completedSubmissions->map(function ($submission) {

                $marketplaceItem = $submission->paymentRequest->inAppPurchase->marketplaceItems->first();

                // Latest evaluation
                $latestEvaluation = Evaluation::with(['answers.question.category'])
                    ->where('submission_id', $submission->id)
                    ->orderBy('created_at', 'desc')
                    ->first();

                $ratings = [];
                if ($latestEvaluation && $latestEvaluation->is_selected) {
                    $categoryRatings = [];

                    foreach ($latestEvaluation->answers as $answer) {
                        $category = $answer->question->category;
                        $slug = $category->slug;

                        if (!isset($categoryRatings[$slug])) {
                            $categoryRatings[$slug] = [
                                'name' => $category->name,
                                'total' => 0,
                                'count' => 0,
                            ];
                        }

                        $categoryRatings[$slug]['total'] += $answer->rating;
                        $categoryRatings[$slug]['count']++;
                    }

                    foreach ($categoryRatings as $slug => $data) {
                        $ratings[] = [
                            'title' => $data['name'],
                            'value' => round($data['total'] / max(1, $data['count']), 1),
                        ];
                    }
                }

                return [
                    'evaluation_id' => $latestEvaluation ? $latestEvaluation->id : null,
                    'submission_id' => $submission->id,
                    'status' => $submission->status,
                    'created_at' => $submission->created_at->toISOString(),
                    'marketplace_title' => $marketplaceItem->title ?? null,
                    'marketplace_type' => $marketplaceItem->type ?? null,
                    'in_app_purchase_sku' => $submission->paymentRequest->inAppPurchase->sku ?? null,
                    'date_time' => $latestEvaluation ? $latestEvaluation->created_at->toISOString() : null,
                    'is_selected' => $latestEvaluation ? $latestEvaluation->is_selected : null,
                    'is_public' => $latestEvaluation ? $latestEvaluation->is_public : null,
                    'ratings' => $ratings,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Achievements and evaluations retrieved successfully',
                'data' => [
                    'achievements' => $achievements,
                    'evaluations' => $evaluations,
                    'total_achievements' => $achievements->count(),
                    'total_evaluations' => $evaluations->count(),
                    'player_id' => $playerId,
                ],
            ], 200);
        } catch (Exception $e) {
            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => 'unknown_method',
            ]);
            Log::error('Error getting achievements/evaluations: ' . $e->getMessage(), [
                'user_id' => Auth::guard('v4api')->id(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve achievements and evaluations',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }



    /**
     * Create a new achievement for authenticated player with image upload
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createAchievement(Request $request): JsonResponse
    {
        try {
            $user = Auth::guard('v4api')->user();

            // Validate user must be a player
            if (!$user || $user->role !== 'player') {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only players can create achievements.',
                ], 403);
            }

            // Validate request
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'image' => 'required|image|mimes:jpeg,jpg,png|max:5120', // 5MB max
                'details' => 'nullable|string',
                'meta' => 'nullable|array',
            ]);

            $imageUrl = null;

            // Handle image upload if provided
            if ($request->hasFile('image')) {
                $file = $request->file('image');

                // Generate unique filename
                $filename = 'achievement_' . $user->id . '_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

                // Upload to S3
                $path = $file->storeAs('player-achievements/' . $user->id, $filename, 's3');
                $imageUrl = Storage::disk('s3')->url($path);
            }

            // Create achievement
            $achievement = V4PlayerAchievement::create([
                'player_id' => $user->id,
                'title' => $validated['title'],
                'file_path' => $imageUrl,
                'details' => $validated['details'] ?? null,
                'meta' => $validated['meta'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Achievement created successfully',
                'data' => [
                    'achievement' => $achievement,
                ],
            ], 201);
        } catch (ValidationException $e) {
            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => 'unknown_method',
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Error creating achievement: ' . $e->getMessage(), [
                'user_id' => Auth::guard('v4api')->id(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create achievement',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Update an existing achievement for authenticated player with optional image upload
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateAchievement(Request $request): JsonResponse
    {
        try {
            $user = Auth::guard('v4api')->user();

            // Validate user must be a player
            if (!$user || $user->role !== 'player') {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only players can update achievements.',
                ], 403);
            }

            // Validate request - now includes achievement_id in body
            $validated = $request->validate([
                'achievement_id' => 'required|integer|exists:v4_player_achievements,id',
                'title' => 'sometimes|required|string|max:255',
                'image' => 'nullable|image|mimes:jpeg,jpg,png,gif|max:5120', // 5MB max
                'details' => 'nullable|string',
                'meta' => 'nullable|array',
            ]);

            $achievementId = $validated['achievement_id'];

            // Find achievement
            $achievement = V4PlayerAchievement::find($achievementId);

            if (!$achievement) {
                return response()->json([
                    'success' => false,
                    'message' => 'Achievement not found',
                ], 404);
            }

            // Verify ownership - achievement must belong to the authenticated player
            if ($achievement->player_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: This achievement does not belong to you.',
                ], 403);
            }

            // Prepare update data
            $updateData = [];

            if (isset($validated['title'])) {
                $updateData['title'] = $validated['title'];
            }

            if (isset($validated['details'])) {
                $updateData['details'] = $validated['details'];
            }

            if (isset($validated['meta'])) {
                $updateData['meta'] = $validated['meta'];
            }

            // Handle image upload if provided
            if ($request->hasFile('image')) {
                $file = $request->file('image');

                // Delete old image from S3 if exists
                if ($achievement->file_path) {
                    // Extract path from URL
                    $oldPath = parse_url($achievement->file_path, PHP_URL_PATH);
                    $oldPath = ltrim($oldPath, '/');

                    // Try to delete old file
                    try {
                        Storage::disk('s3')->delete($oldPath);
                    } catch (Exception $e) {
                        // Track error in Sentry
                        $this->errorTracker->captureException($e, [
                            'action' => 'unknown_method',
                        ]);
                        Log::warning('Failed to delete old achievement image', [
                            'achievement_id' => $achievementId,
                            'old_path' => $oldPath,
                            'error' => $e->getMessage()
                        ]);
                    }
                }

                // Generate unique filename
                $filename = 'achievement_' . $user->id . '_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

                // Upload new image to S3
                $path = $file->storeAs('player-achievements/' . $user->id, $filename, 's3');
                $imageUrl = Storage::disk('s3')->url($path);

                $updateData['file_path'] = $imageUrl;
            }

            // Update achievement
            $achievement->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Achievement updated successfully',
                'data' => [
                    'achievement' => $achievement->fresh(),
                ],
            ], 200);
        } catch (ValidationException $e) {
            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => 'unknown_method',
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Error updating achievement: ' . $e->getMessage(), [
                'achievement_id' => $request->input('achievement_id'),
                'user_id' => Auth::guard('v4api')->id(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update achievement',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Delete (soft delete) an achievement for authenticated player
     *
     * @param Request $request
     * @param int $achievementId
     * @return JsonResponse
     */
    public function deleteAchievement(Request $request, int $achievementId): JsonResponse
    {
        try {
            $user = Auth::guard('v4api')->user();

            // Validate user must be a player
            if (!$user || $user->role !== 'player') {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only players can delete achievements.',
                ], 403);
            }

            // Find achievement
            $achievement = V4PlayerAchievement::find($achievementId);

            if (!$achievement) {
                return response()->json([
                    'success' => false,
                    'message' => 'Achievement not found',
                ], 404);
            }

            // Verify ownership - achievement must belong to the authenticated player
            if ($achievement->player_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: This achievement does not belong to you.',
                ], 403);
            }

            // Soft delete achievement
            $achievement->delete();

            return response()->json([
                'success' => true,
                'message' => 'Achievement deleted successfully',
                'data' => [
                    'achievement_id' => $achievementId,
                    'deleted_at' => now()->toISOString(),
                ],
            ], 200);
        } catch (Exception $e) {
            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => 'unknown_method',
            ]);
            Log::error('Error deleting achievement: ' . $e->getMessage(), [
                'achievement_id' => $achievementId,
                'user_id' => Auth::guard('v4api')->id(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete achievement',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }


    public function addRemoveFavouriteUsers(Request $request)
    {
        $user = Auth::guard('v4api')->user();

        // 1️⃣ Check role permissions
        $allowedRoles = ['scout', 'coach', 'adviser', 'fan'];

        if (!in_array($user->role, $allowedRoles)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to manage favourites.',
            ], 403);
        }

        // 2️⃣ Validate request structure
        $request->validate([
            'add' => 'array',
            'remove' => 'array',
            'add.*' => 'integer|exists:v4_users,id',
            'remove.*' => 'integer|exists:v4_users,id',
        ]);

        $addIds = $request->add ?? [];
        $removeIds = $request->remove ?? [];

        // 3️⃣ Validate: all provided user IDs must exist
        $allIds = array_unique(array_merge($addIds, $removeIds));

        if (!empty($allIds)) {
            $validUsers = V4User::whereIn('id', $allIds)
                ->pluck('id')
                ->toArray();

            $invalid = array_diff($allIds, $validUsers);
            if (!empty($invalid)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid user IDs found.',
                    'invalid_user_ids' => array_values($invalid)
                ], 400);
            }
        }

        // 4️⃣ Validate: Remove IDs must already exist in favourite table
        if (!empty($removeIds)) {
            $existingFavs = FavouriteUser::where('user_id', $user->id)
                ->whereIn('favourite_id', $removeIds)
                ->pluck('favourite_id')
                ->toArray();

            $notFavs = array_diff($removeIds, $existingFavs);
            if (!empty($notFavs)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Some users are not in your favourite list.',
                    'invalid_user_ids' => array_values($notFavs)
                ], 400);
            }
        }

        // 5️⃣ Validate: Add IDs must NOT exist already
        if (!empty($addIds)) {
            $existingFavs = FavouriteUser::where('user_id', $user->id)
                ->whereIn('favourite_id', $addIds)
                ->pluck('favourite_id')
                ->toArray();

            $alreadyFavs = array_intersect($addIds, $existingFavs);
            if (!empty($alreadyFavs)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Some users are already favourited.',
                    'already_favourited_user_ids' => array_values($alreadyFavs)
                ], 400);
            }
        }

        // 6️⃣ DB Transaction
        DB::beginTransaction();
        try {
            if (!empty($removeIds)) {
                FavouriteUser::where('user_id', $user->id)
                    ->whereIn('favourite_id', $removeIds)
                    ->delete();
            }

            if (!empty($addIds)) {
                $insertData = [];
                foreach ($addIds as $favId) {

                    $token = $request->bearerToken();

                    $baseUrl = config('app.env') === 'production' ? config('CHAT_APP_HOST_PRODUCTION') : env('CHAT_APP_HOST');

                    $response = Http::withHeaders([
                        'Authorization' => 'Bearer ' . $token,
                        'Content-Type' => 'application/json',
                    ])->post($baseUrl . '/conversation/create', [
                                'type' => 'single',
                                'participants' => [$user->id, $favId],
                            ]);

                    $conversationId = null;
                    if ($response->successful() && isset($response->json()['_id'])) {
                        $conversationId = $response->json()['_id'];
                    } else {
                        Log::warning('Conversation API failed', [
                            'status' => $response->status(),
                            'body' => $response->body(),
                        ]);
                    }
                    $insertData[] = [
                        'user_id' => $user->id,
                        'favourite_id' => $favId,
                        'conversation_id' => $conversationId,
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                }

                FavouriteUser::insert($insertData);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Favourite users updated successfully.',
            ]);
        } catch (Exception $e) {
            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => 'unknown_method',
            ]);
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update favourite users.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function getFavouriteUsers(Request $request, int $userId): JsonResponse
    {
        try {
            $user = V4User::find($userId);
            if (!$user || !in_array($user->role, ['scout', 'coach', 'adviser', 'fan'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 401);
            }

            $favouriteUsers = FavouriteUser::with(['favourite:id,first_name,last_name,profile_photo,username,role'])
                ->where('user_id', $user->id)
                ->get();



            return response()->json([
                'success' => true,
                'message' => 'Favourites retrieved successfully.',
                'data' => $favouriteUsers
            ]);
        } catch (Exception $e) {
            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => 'unknown_method',
            ]);
            Log::error('Failed to fetch favourite users.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $user->id ?? null,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve favourite users.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error.',
            ], 500);
        }
    }

    public function updateSuperAdminProfile(Request $request): JsonResponse
    {
        try {
            /** @var V4User $user */
            $user = Auth::guard('v4api')->user();

            if ($user->role !== 'super-admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Only super admin can update this profile.',
                ], 403);
            }

            // Validation rules
            $rules = [
                'first_name' => 'nullable|string|max:255',
                'last_name' => 'nullable|string|max:255',
                'email' => 'nullable|email',
                'phone' => 'nullable|string|max:20',
            ];

            if ($request->hasFile('profile_photo')) {
                $rules['profile_photo'] = 'file|image|max:5120'; // 5 MB
            }

            $validated = $request->validate($rules);

            // Upload image if present
            if ($request->hasFile('profile_photo')) {
                $path = $request->file('profile_photo')->store(
                    'profile_photos/' . $user->id,
                    's3'
                );
                $validated['profile_photo'] = Storage::disk('s3')->url($path);
            }

            // Update Super Admin
            $user->update($validated);
            $user->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Super admin profile updated successfully',
                'user' => [
                    'id' => $user->id,
                    'firstName' => $user->first_name,
                    'lastName' => $user->last_name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'profilePhoto' => $user->profile_photo,
                ],
            ]);
        } catch (ValidationException $e) {
            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => 'unknown_method',
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Profile update failed.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function updateSuperAdminPassword(Request $request): JsonResponse
    {
        try {
            /** @var V4User $user */
            $user = Auth::guard('v4api')->user();

            if ($user->role !== 'super-admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Only super admin can update password.',
                ], 403);
            }

            // Validate input
            $validated = $request->validate([
                'current_password' => 'required|string',
                'password' => 'required|string|min:8|confirmed',
            ]);

            // Check current password
            if (!Hash::check($validated['current_password'], $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Current password is incorrect.',
                ], 422);
            }

            // Update password
            $user->update([
                'password' => Hash::make($validated['password']),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Password updated successfully.',
            ]);
        } catch (ValidationException $e) {
            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => 'unknown_method',
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Password update failed.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
