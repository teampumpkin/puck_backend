<?php

namespace App\Http\Controllers\V4;

use App\Http\Controllers\Controller;
use App\Models\V4User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function getProfileData()
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
                    $user->load('teamProfile');
                    $profileData = $user->teamProfile;
                    break;
                case 'scout':
                    $user->load('scoutProfile');
                    $profileData = $user->scoutProfile;
                    break;
                case 'academy':
                    $user->load('academyProfile');
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

            return response()->json([
                'success' => true,
                'message' => 'Profile data retrieved successfully',
                'user'    => $userData,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve profile data',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function updateProfile(Request $request)
    {
        try {
            /** @var V4User $user */

            $user = Auth::guard('v4api')->user();

            $isFirstTimeOnboarding = ! $user->is_onboarded;

            $rules = [
                'first_name'    => 'nullable|string|max:255',
                'last_name'     => 'nullable|string|max:255',
                'email'         => 'nullable|email',
                'phone'         => 'nullable|string|max:20',
                'country'       => 'nullable|string|max:100',
                'state'         => 'nullable|string|max:100',
                'city'          => 'nullable|string|max:100',
                'date_of_birth' => 'nullable|date',
                'zip'           => 'nullable|string|max:20',
                'is_onboarded'  => 'nullable|boolean',
            ];

            // These fields are always optional
            $rules['enable_private_account'] = 'nullable|boolean';
            $rules['receive_news_offers']    = 'nullable|boolean';

            // terms_accepted validation only if it's not already true
            if (! $user->terms_accepted) {
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
                $photoUrl                   = Storage::disk('s3')->url($path);
                $validated['profile_photo'] = $photoUrl;
            }

            $user->update($validated);
            $user->refresh();

            switch ($user->role) {
                case 'player':
                    $playerValidated = $request->validate([
                        'teams'      => 'nullable|array',
                        'leagues'    => 'nullable|array',
                        'handedness' => 'nullable|in:left,right,ambidextrous',
                        'weight'     => 'nullable|numeric',
                        'height'     => 'nullable|numeric',
                        'position'   => 'nullable|string|max:100',
                        'gender'     => 'nullable|in:male,female,other',
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
                        'teams'   => 'nullable|array',
                    ]);
                    $user->coachProfile()->updateOrCreate([], $coachValidated);
                    $user->load('coachProfile');
                    break;

                case 'team':
                    $teamValidated = $request->validate([
                        'team_name'                => 'nullable|string|max:255',
                        'administrator_first_name' => 'nullable|string|max:255',
                        'administrator_last_name'  => 'nullable|string|max:255',
                        'leagues'                  => 'nullable|array',
                        'website'                  => 'nullable|string|max:255',
                        'address'                  => 'nullable|string|max:255',
                        'team_years_running'       => 'nullable|integer',
                    ]);

                    $user->teamProfile()->updateOrCreate([], $teamValidated);
                    $user->load('teamProfile');
                    break;

                case 'scout':
                    $scoutValidated = $request->validate([
                        'leagues'                   => 'nullable|array',
                        'teams'                     => 'nullable|array',
                        'scouting_years'            => 'nullable|integer',
                        'level_hockey_played'       => 'nullable|string|max:255',
                        'current_involvement_level' => 'nullable|string|max:255',
                        'current_sport_role'        => 'nullable|string|max:255',
                        'resume'                    => 'nullable|file|mimes:pdf|max:10240',
                        'references'                => 'nullable|array',
                        'references.*.name'         => 'required_with:references|string|max:255',
                        'references.*.email'        => 'required_with:references|email|max:255',
                        'references.*.phone'        => 'required_with:references|string|max:20',
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
                        "business_name"              => "nullable|string|max:255",
                        "business_phone"             => "nullable|string|max:20",
                        "address"                    => "nullable|string|max:255",
                        "website"                    => "nullable|string|max:255",
                        "number_years_organizing"    => "nullable|integer",
                        "leagues"                    => "nullable|array",
                        "link_of_previous_events"    => "nullable|array",
                        "number_of_events_organized" => "nullable|integer",
                    ]);
                    $user->organizerProfile()->updateOrCreate([], $organizerValidated);
                    $user->load('organizerProfile');
                    break;
                case 'academy':
                    $academyValidated = $request->validate([
                        "academy_name"             => "nullable|string|max:255",
                        "administrator_first_name" => "nullable|string|max:255",
                        "administrator_last_name"  => "nullable|string|max:255",
                        "teams"                    => "nullable|array",
                        "leagues"                  => "nullable|array",
                        "website"                  => "nullable|string|max:255",
                        "address"                  => "nullable|string|max:255",
                        "academy_years_running"    => "nullable|integer",
                        "main_team_name"           => "nullable|string|max:255",
                    ]);
                    $user->academyProfile()->updateOrCreate([], $academyValidated);
                    $user->load('academyProfile');
                    break;
                case 'adviser':
                    $adviserValidated = $request->validate([
                        'leagues'                    => 'nullable|array',
                        'teams'                      => 'nullable|array',
                        'business_name'              => 'nullable|string|max:255',
                        'business_phone'             => 'nullable|string|max:20',
                        'website'                    => 'nullable|string|max:255',
                        'address'                    => 'nullable|string|max:255',
                        'level_hockey_played'        => 'nullable|string|max:255',
                        'current_involvement_level'  => 'nullable|string|max:255',
                        'current_sport_role'         => 'nullable|string|max:255',
                        'number_of_years_experience' => 'nullable|integer',
                        'resume'                     => 'nullable|file|mimes:pdf|max:10240',
                        'references'                 => 'nullable|array',
                        'references.*.name'          => 'required_with:references|string|max:255',
                        'references.*.email'         => 'required_with:references|email|max:255',
                        'references.*.phone'         => 'required_with:references|string|max:20',
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
                        'leagues'                    => 'nullable|array',
                        'address'                    => 'nullable|string|max:255',
                        'level_hockey_played'        => 'nullable|string|max:255',
                        'current_involvement_level'  => 'nullable|string|max:255',
                        'current_sport_role'         => 'nullable|string|max:255',
                        'number_of_years_experience' => 'nullable|integer',
                        'resume'                     => 'nullable|file|mimes:pdf|max:10240',
                        'references'                 => 'nullable|array',
                        'references.*.name'          => 'required_with:references|string|max:255',
                        'references.*.email'         => 'required_with:references|email|max:255',
                        'references.*.phone'         => 'required_with:references|string|max:20',
                    ]);
                    if ($request->hasFile('resume')) {
                        $path = $request->file('resume')->store(
                            'resume/' . $request->user()->id,
                            's3'
                        );
                        $resumeUrl                    = Storage::disk('s3')->url($path);
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
                'user'    => $userData,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Profile update failed.',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function addChild(Request $request)
    {
        try {
            $parent = Auth::guard('v4api')->user();

            $validatedData = $request->validate([
                'first_name'                           => 'required|string|max:50',
                'last_name'                            => 'required|string|max:50',
                'date_of_birth'                        => 'required|date|before:today',
                'gender'                               => 'required|in:male,female,other',
                'username'                             => 'required|unique:v4_users,username',
                'password'                             => 'required|min:6',
                'position'                             => 'nullable|string|max:100',
                'email'                                => 'nullable|email',
                'teams'                                => 'nullable|array',
                'leagues'                              => 'nullable|array',
                // Add permission validations
                'permissions'                          => 'nullable|array',
                'permissions.can_chat'                 => 'boolean',
                'permissions.can_view_events'          => 'boolean',
                'permissions.can_view_feed'            => 'boolean',
                'permissions.can_view_messages'        => 'boolean',
                'permissions.can_accept_invites'       => 'boolean',
                'permissions.can_send_friend_requests' => 'boolean',
                'permissions.can_use_marketplace'      => 'boolean',
            ]);

            // Use database transaction to ensure data consistency
            $result = \DB::transaction(function () use ($parent, $validatedData) {
                // Create child user with parent's information
                $child = V4User::create([
                    'parent_id'      => $parent->id,
                    'is_child'       => true,
                    'role'           => 'player',
                    'first_name'     => $validatedData['first_name'],
                    'last_name'      => $validatedData['last_name'],
                    'date_of_birth'  => $validatedData['date_of_birth'],
                    'gender'         => $validatedData['gender'],
                    'username'       => $validatedData['username'],
                    'password'       => Hash::make($validatedData['password']),
                    // Pass parent's contact and location information to child
                    'email'          => null, // Children do not require email; keep unique constraint for non-children
                    'phone'          => $parent->phone,
                    'country'        => $parent->country,
                    'state'          => $parent->state,
                    'city'           => $parent->city,
                    // Pass parent's account status flags to child
                    'terms_accepted' => $parent->terms_accepted,
                    'is_onboarded'   => $parent->is_onboarded,
                ]);

                // Create player profile with permissions
                $playerProfile             = new \App\Models\PlayerProfile();
                $playerProfile->v4_user_id = $child->id;
                $playerProfile->gender     = $validatedData['gender'];
                $playerProfile->position   = $validatedData['position'] ?? null;
                $playerProfile->teams      = $validatedData['teams'] ?? null;
                $playerProfile->leagues    = $validatedData['leagues'] ?? null;

                // Set default permissions if not provided
                $permissions = $validatedData['permissions'] ?? [
                    'can_chat'                 => true,
                    'can_view_events'          => true,
                    'can_view_feed'            => true,
                    'can_view_messages'        => true,
                    'can_accept_invites'       => true,
                    'can_send_friend_requests' => true,
                    'can_use_marketplace'      => true,
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
                    'child'  => $childData,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Child account created successfully',
                'parent'  => $result['parent'],
                'child'   => $result['child'],
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Child add failed.',
                'error'   => config('app.debug') ? $e->getMessage() : null,
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

            if (! $child) {
                return response()->json([
                    'success' => false,
                    'message' => 'Child not found or not authorized',
                ], 404);
            }

            // Validate the permissions data
            $validatedData = $request->validate([
                'permissions'                          => 'required|array',
                'permissions.can_chat'                 => 'boolean',
                'permissions.can_view_events'          => 'boolean',
                'permissions.can_view_feed'            => 'boolean',
                'permissions.can_view_messages'        => 'boolean',
                'permissions.can_accept_invites'       => 'boolean',
                'permissions.can_send_friend_requests' => 'boolean',
                'permissions.can_use_marketplace'      => 'boolean',
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
                'child'   => $childData,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update child permissions',
                'error'   => config('app.debug') ? $e->getMessage() : null,
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

            if (! $child) {
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
                'child'   => $childData,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update child credentials',
                'error'   => config('app.debug') ? $e->getMessage() : null,
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
            'q'        => 'nullable|string|max:255',
            'page'     => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        // Get search parameters
        $searchTerm = $request->input('q', '');
        $page       = $request->input('page', 1);
        $perPage    = $request->input('per_page', 15);

        // Get current authenticated user
        $currentUser = Auth::guard('v4api')->user();

        // Build the query
        $query = V4User::query()
            ->select(['id', 'first_name', 'last_name', 'role', 'profile_photo as profile_picture'])
            ->where('id', '!=', $currentUser->id); // Exclude current user from search results

        // Apply search filter if search term is provided
        if (! empty($searchTerm)) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('first_name', 'ilike', "%{$searchTerm}%")
                    ->orWhere('last_name', 'ilike', "%{$searchTerm}%");
            });
        }

        // Execute the query with pagination
        $users = $query->paginate($perPage, ['*'], 'page', $page);

        // Format the response for FlutterFlow compatibility
        return response()->json([
            'data'       => $users->items(),
            'pagination' => [
                'total'          => $users->total(),
                'per_page'       => $users->perPage(),
                'current_page'   => $users->currentPage(),
                'last_page'      => $users->lastPage(),
                'from'           => $users->firstItem() ?? 0,
                'to'             => $users->lastItem() ?? 0,
                'has_more_pages' => $users->hasMorePages(),
            ],
        ]);
    }

    public function getProfileBatchData(Request $request)
    {
        try {
            //validation
            $validated = $request->validate([
                'convoIds'       => 'required|array',
                'convoIds.*'     => 'required|array',
                'convoIds.*.*'   => 'required|array',
                'convoIds.*.*.*' => 'required|string',
            ]);

            $result = [];

            foreach ($validated['convoIds'] as $convoMap) {
                $entryResult = [];

                foreach ($convoMap as $conversationKey => $userIds) {
                    if (! is_string($conversationKey)) {
                        continue;
                    }

                    $userResult = [];

                    if (! empty($userIds)) {
                        // all users
                        $users = V4User::whereIn('id', $userIds)
                            ->select(['id', 'first_name', 'last_name', 'role', 'profile_photo'])
                            ->get();

                        foreach ($users as $user) {
                            $userResult[$user->id] = [
                                'id'            => $user->id,
                                'first_name'    => $user->first_name,
                                'last_name'     => $user->last_name,
                                'role'          => $user->role,
                                'profile_photo' => $user->profile_photo,
                            ];
                        }
                    }
                    $entryResult[$conversationKey] = $userResult;
                }

                $result[] = $entryResult;
            }

            return response()->json([
                'success' => true,
                'users'   => $result,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve batch profile data',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function searchAndSortUsers(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'q'          => 'nullable|string|max:255',
                'page'       => 'nullable|integer|min:1',
                'per_page'   => 'nullable|integer|min:1|max:100',
                'sort_by'    => 'nullable|string|in:first_name,last_name,role',
                'sort_order' => 'nullable|string|in:asc,desc',
            ]);

            $searchTerm = $validated['q'] ?? '';
            $page       = $validated['page'] ?? 1;
            $perPage    = $validated['per_page'] ?? 15;
            $sortBy     = $validated['sort_by'] ?? 'first_name';
            $sortOrder  = $validated['sort_order'] ?? 'asc';

            $currentUser = Auth::guard('v4api')->user();


            $query = V4User::query()
                ->where('id', '!=', $currentUser->id)
                ->whereNotIn('role', ['super-admin', 'admin', 'manager']);

            if (! empty($searchTerm)) {
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('first_name', 'ilike', "%{$searchTerm}%")
                        ->orWhere('last_name', 'ilike', "%{$searchTerm}%");
                });
            }

            // Optimized eager loading: Only load relationship IDs or necessary fields
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

            $query->orderBy($sortBy, $sortOrder);

            $users = $query->paginate($perPage, ['*'], 'page', $page);

            $data = $users
                ->map(function ($user) {
                    return [
                        'id'          => $user->id,
                        'name'        => $user->name,
                        'email'       => $user->email,
                        'status'      => 'active',
                        'role'        => $user->role,
                        'country'     => $user->country,
                        'createdAt'   => $user->created_at,
                        'age'         => $user->age,
                        'phone'       => $user->phone,
                        'avatar'      => $user->profile_picture,
                        'profileData' => $user->profile_data, // accessor
                    ];
                });

            return response()->json([
                'data'       => $data,
                'pagination' => [
                    'total'          => $users->total(),
                    'per_page'       => $users->perPage(),
                    'current_page'   => $users->currentPage(),
                    'last_page'      => $users->lastPage(),
                    'from'           => $users->firstItem() ?? 0,
                    'to'             => $users->lastItem() ?? 0,
                    'has_more_pages' => $users->hasMorePages(),
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function searchAndSortAdminUsers(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'q'          => 'nullable|string|max:255',
                'page'       => 'nullable|integer|min:1',
                'per_page'   => 'nullable|integer|min:1|max:100',
                'sort_by'    => 'nullable|string|in:first_name,last_name,role',
                'sort_order' => 'nullable|string|in:asc,desc',
            ]);

            $searchTerm = $validated['q'] ?? '';
            $page       = $validated['page'] ?? 1;
            $perPage    = $validated['per_page'] ?? 15;
            $sortBy     = $validated['sort_by'] ?? 'first_name';
            $sortOrder  = $validated['sort_order'] ?? 'asc';

            $currentUser = Auth::guard('v4api')->user();

            $query = V4User::query()
                ->where('id', '!=', $currentUser->id)
                ->whereIn('role', ['super-admin', 'admin', 'manager']);

            if (! empty($searchTerm)) {
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('first_name', 'ilike', "%{$searchTerm}%")
                        ->orWhere('last_name', 'ilike', "%{$searchTerm}%");
                });
            }

            // Optimized eager loading: Only load relationship IDs or necessary fields
            $query->with([
                'superAdminProfile:id,v4_user_id',
            ]);

            $query->orderBy($sortBy, $sortOrder);

            $users = $query->paginate($perPage, ['*'], 'page', $page);

            $data = $users
                ->map(function ($user) {
                    return [
                        'id'          => $user->id,
                        'name'        => $user->name,
                        'email'       => $user->email,
                        'status'      => 'active',
                        'role'        => $user->role,
                        'country'     => $user->country,
                        'createdAt'   => $user->created_at,
                        'age'         => $user->age,
                        'phone'       => $user->phone,
                        'avatar'      => $user->profile_picture,
                        'profileData' => $user->profile_data, // accessor
                    ];
                });

            return response()->json([
                'data'       => $data,
                'pagination' => [
                    'total'          => $users->total(),
                    'per_page'       => $users->perPage(),
                    'current_page'   => $users->currentPage(),
                    'last_page'      => $users->lastPage(),
                    'from'           => $users->firstItem() ?? 0,
                    'to'             => $users->lastItem() ?? 0,
                    'has_more_pages' => $users->hasMorePages(),
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error'   => config('app.debug') ? $e->getMessage() : null,
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
            if (! $user->evaluatorProfile) {
                return response()->json([
                    'success' => false,
                    'message' => 'Evaluator profile not found for this user.',
                ], 404);
            }

            // Toggle the is_verified flag
            $user->evaluatorProfile->is_verified = ! $user->evaluatorProfile->is_verified;
            $user->evaluatorProfile->save();

            // Refresh the relationship
            $user->load('evaluatorProfile');

            // Get the updated status
            $status = $user->evaluatorProfile->is_verified ? 'verified' : 'unverified';

            return response()->json([
                'success' => true,
                'message' => "Evaluator has been successfully {$status}.",
                'data'    => $user,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
                'errors'  => $e,
            ], 404);
        } catch (ValidationException $e) {
            Log::error(
                'An Validation error occurred.' . $e->getMessage(),
                [
                    'user_id'     => Auth::id(),
                    'user_id' => $id,
                    'trace'       => $e->getTraceAsString(),
                ]
            );
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error(
                'An unexpected error occurred.' . $e->getMessage(),
                [
                    'user_id'     => Auth::id(),
                    'user_id' => $id,
                    'trace'       => $e->getTraceAsString(),
                ]
            );
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred.',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function getAllUserDetailsById($id): JsonResponse
    {
        try {

            $user = V4User::findOrFail($id);

            $userData = $user;

            return response()->json([
                'success' => true,
                'message' => 'Profile data retrieved successfully',
                'user'    => $userData,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
