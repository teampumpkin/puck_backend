<?php

namespace App\Http\Controllers\V4;

use App\Http\Controllers\Controller;
use App\Models\TeamMember;
use App\Models\V4Team;
use App\Models\V4User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpFoundation\JsonResponse;

class V4TeamController extends Controller
{

    /**
     * CREATE TEAM
     */
    public function createTeam(Request $request)
    {
        try {
            $validated = $request->validate([
                'team_name' => 'required|string|max:255',
                'administrator_first_name' => 'required|string|max:255',
                'administrator_last_name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'leagues' => 'required|array',
                'phone' => 'required|string|max:255',
                'website' => 'nullable|string|max:255',
                'address' => 'nullable|string|max:255',
                'team_years_running' => 'nullable|integer',
                'city' => 'nullable|string|max:255',
                'state' => 'nullable|string|max:255',
                'zipcode' => 'nullable|string|max:255',
                'country' => 'nullable|string|max:255',
                'profile_photo' => 'nullable|file|image|max:5120',
            ]);

            $profilePhotoUrl = null;

            if ($request->hasFile('profile_photo')) {
                $file = $request->file('profile_photo');

                if ($file->isValid()) {

                    $mimeType = $file->getClientMimeType();

                    // Only allow images
                    if (str_starts_with($mimeType, 'image/')) {

                        $filename = 'team_profile_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

                        // Store in S3 -> folder teams/{teamId}/
                        $path = $file->storeAs(
                            'teams/' . ($teamId ?? 'temp'),
                            $filename,
                            's3'
                        );

                        // URL to save in DB
                        $profilePhotoUrl = Storage::disk('s3')->url($path);
                    }
                }
            }


            // Create team
            $validated['profile_photo'] = $profilePhotoUrl;
            $team = V4Team::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Team created successfully',
                'team' => $team
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Team creation failed',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * UPDATE TEAM
     */
    public function updateTeam(Request $request, $teamId)
    {
        $team = V4Team::find($teamId);

        if (!$team) {
            return response()->json([
                'success' => false,
                'message' => 'Team not found'
            ], 404);
        }

        try {
            $validated = $request->validate([
                'team_name' => 'sometimes|string|max:255',
                'administrator_first_name' => 'sometimes|string|max:255',
                'administrator_last_name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|max:255',
                'leagues' => 'sometimes|array',
                'phone' => 'sometimes|string|max:255',
                'website' => 'nullable|string|max:255',
                'address' => 'nullable|string|max:255',
                'team_years_running' => 'nullable|integer',
                'city' => 'nullable|string|max:255',
                'state' => 'nullable|string|max:255',
                'zipcode' => 'nullable|string|max:255',
                'country' => 'nullable|string|max:255',
                'profile_photo' => 'nullable|file|image|max:5120',
            ]);

            if ($request->hasFile('profile_photo')) {
                $file = $request->file('profile_photo');

                if ($file->isValid()) {

                    $mimeType = $file->getClientMimeType();

                    // Only allow images
                    if (str_starts_with($mimeType, 'image/')) {

                        $filename = 'team_profile_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

                        // Store in S3 -> folder teams/{teamId}/
                        $path = $file->storeAs(
                            'teams/' . ($teamId ?? 'temp'),
                            $filename,
                            's3'
                        );

                        // URL to save in DB
                        $profilePhotoUrl = Storage::disk('s3')->url($path);
                        $validated['profile_photo'] = $profilePhotoUrl;
                    }
                }
            }

            // Update team
            $team->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Team updated successfully',
                'team' => $team
            ]);


        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Team update failed',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }


    /**
     * DELETE TEAM
     */
    public function deleteTeam($teamId)
    {
        $team = V4Team::find($teamId);

        if (!$team) {
            return response()->json([
                'success' => false,
                'message' => 'Team not found'
            ], 404);
        }

        try {
            $team->delete();

            return response()->json([
                'success' => true,
                'message' => 'Team deleted successfully'
            ]);

        } catch (Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Team delete failed',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Add / Remove Team Members
     */
    public function addRemoveTeamMembers(Request $request, $teamId)
    {
        $user = Auth::guard('v4api')->user();

        // --- Validate: Team must exist and role must be 'team'
        $team = V4Team::where('id', $teamId);

        if (!$team) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid team Id',
            ], 400);
        }

        // Validate request
        $request->validate([
            'add' => 'array',
            'remove' => 'array',
        ]);


        $addIds = $request->add ?? [];
        $removeIds = $request->remove ?? [];

        // --- Validate: All user_ids in add & remove must exist & be players
        $userIdsToCheck = array_unique(array_merge($addIds, $removeIds));

        if (!empty($userIdsToCheck)) {
            $validPlayers = V4User::whereIn('id', $userIdsToCheck)
                ->where('role', 'player')
                ->pluck('id')
                ->toArray();

            $invalidUsers = array_diff($userIdsToCheck, $validPlayers);
            if (!empty($invalidUsers)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Some user IDs are not valid players',
                    'invalid_user_ids' => array_values($invalidUsers)
                ], 400);
            }
        }

        // --- Validate: Remove IDs must already be in team_members
        if (!empty($removeIds)) {
            $existingMembers = TeamMember::where('team_id', $teamId)
                ->whereIn('player_id', $removeIds)
                ->pluck('player_id')
                ->toArray();

            $notMembers = array_diff($removeIds, $existingMembers);
            if (!empty($notMembers)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Some users are not team members',
                    'invalid_user_ids' => array_values($notMembers)
                ], 400);
            }
        }

        // --- Validate: Add IDs must NOT already exist
        if (!empty($addIds)) {
            $existingMembers = TeamMember::where('team_id', $teamId)
                ->whereIn('player_id', $addIds)
                ->pluck('player_id')
                ->toArray();

            $alreadyMembers = array_diff($addIds, array_diff($addIds, $existingMembers));
            if (!empty($alreadyMembers)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Some users are already team members',
                    'already_members' => array_values($alreadyMembers)
                ], 400);
            }
        }

        // --- DB Transaction: perform add / remove
        DB::beginTransaction();

        try {

            // Soft delete removeIds
            if (!empty($removeIds)) {
                TeamMember::where('team_id', $teamId)
                    ->whereIn('player_id', $removeIds)
                    ->update(['removed_by' => $user->id]);

                TeamMember::where('team_id', $teamId)
                    ->whereIn('player_id', $removeIds)
                    ->delete();
            }

            // Insert addIds
            if (!empty($addIds)) {
                $insertData = [];
                foreach ($addIds as $pid) {
                    $insertData[] = [
                        'team_id' => $teamId,
                        'player_id' => $pid,
                        'added_by' => $user->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                TeamMember::insert($insertData);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Team members updated successfully',
            ]);
        } catch (Exception $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update team members',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getTeamDetails(Request $request, $teamId): JsonResponse
    {
        try {
            $validator = Validator::make(['teamId' => $teamId], [
                'teamId' => 'required|integer|exists:v4_teams,id',
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $teamDetails = V4Team::findOrFail($teamId);

            return response()->json([
                'success' => true,
                'message' => 'Fetched Team Details successfully',
                'data' => $teamDetails
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Team not found',
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch team details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getTeamMembers(Request $request, $teamId): JsonResponse
    {
        try {
            $validator = Validator::make(['teamId' => $teamId], [
                'teamId' => 'required|integer|exists:v4_teams,id',
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $team = V4Team::findOrFail($teamId);
            $teamMembers = TeamMember::with(['player:id,first_name,last_name,role,profile_photo,email,country,date_of_birth,state,city,zip,username,enable_private_account'])->where('team_id', $team->id)->get();

            return response()->json([
                'success' => true,
                'message' => 'Fetched Team Members successfully',
                'data' => $teamMembers
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Team not found',
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch team members',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getMyTeamsForProfile(Request $request): JsonResponse
    {
        try {

            $user = Auth::guard('v4api')->user();

            $teams = V4Team::with('members')->get();

            return response()->json([
                'success' => true,
                'message' => 'Fetched Teams',
                'data' => $teams
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update team members',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
