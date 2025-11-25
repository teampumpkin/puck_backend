<?php

namespace App\Http\Controllers\V4;

use App\Http\Controllers\Controller;
use App\Models\TeamMember;
use App\Models\V4Team;
use App\Models\V4User;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class V4TeamController extends Controller
{
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

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update team members',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
