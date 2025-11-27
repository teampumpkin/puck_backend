<?php

namespace App\Http\Controllers\V4;

use App\Http\Controllers\Controller;
use App\Models\AcademyMember;
use App\Models\V4Academy;
use App\Models\V4User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Exception;

class V4AcademyController extends Controller
{
    /**
     * Add / Remove Academy Members
     */
    public function addRemoveAcademyMembers(Request $request, $academyId)
    {
        $user = Auth::guard('v4api')->user();


        // academy validation
        $academy = V4Academy::find($academyId);
        if (!$academy) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid Academy ID',
            ], 400);
        }

        // Validate request
        $request->validate([
            'add' => 'array',
            'remove' => 'array',
            'add.*' => 'integer|exists:v4_users,id',
            'remove.*' => 'integer|exists:v4_users,id',
        ]);


        $addIds = $request->add ?? [];
        $removeIds = $request->remove ?? [];

        // --- Validate: All user_ids in add & remove must exist & be players
        $userIdsToCheck = array_unique(array_merge($addIds, $removeIds));

        if (!empty($userIdsToCheck)) {
            $validUsers = V4User::whereIn('id', $userIdsToCheck)
                ->whereIn('role', ['player', 'coach', 'scout'])
                ->pluck('id')
                ->toArray();

            $invalidUsers = array_diff($userIdsToCheck, $validUsers);
            if (!empty($invalidUsers)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Some user IDs are not valid player, coach, scout',
                    'invalid_user_ids' => array_values($invalidUsers)
                ], 400);
            }
        }

        // --- Validate: Remove IDs must already be in academy_members
        if (!empty($removeIds)) {
            $existingMembers = AcademyMember::where('academy_id', $academyId)
                ->whereIn('player_id', $removeIds)
                ->pluck('player_id')
                ->toArray();

            $notMembers = array_diff($removeIds, $existingMembers);
            if (!empty($notMembers)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Some users are not academy members',
                    'invalid_user_ids' => array_values($notMembers)
                ], 400);
            }
        }

        // --- Validate: Add IDs must NOT already exist
        if (!empty($addIds)) {
            $existingMembers = AcademyMember::where('academy_id', $academyId)
                ->whereIn('player_id', $addIds)
                ->pluck('player_id')
                ->toArray();

            $alreadyMembers = array_diff($addIds, array_diff($addIds, $existingMembers));
            if (!empty($alreadyMembers)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Some users are already academy members',
                    'already_members' => array_values($alreadyMembers)
                ], 400);
            }
        }

        // --- DB Transaction: perform add / remove
        DB::beginTransaction();

        try {

            // Soft delete removeIds
            if (!empty($removeIds)) {
                AcademyMember::where('academy_id', $academyId)
                    ->whereIn('player_id', $removeIds)
                    ->update(['removed_by' => $user->id]);

                AcademyMember::where('academy_id', $academyId)
                    ->whereIn('player_id', $removeIds)
                    ->delete();

                $token = $request->bearerToken();
                $baseUrl = config('app.env') === 'production' ? config('CHAT_APP_HOST_PRODUCTION') : env('CHAT_APP_HOST');
                Http::withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                ])->put($baseUrl . '/conversation/update', [
                            'conversationId' => $academy->conversation_id,
                            'type' => 'group',
                            'removeParticipants' => $removeIds,
                        ]);
            }

            // Insert addIds
            if (!empty($addIds)) {
                $insertData = [];
                foreach ($addIds as $pid) {
                    $insertData[] = [
                        'academy_id' => $academyId,
                        'player_id' => $pid,
                        'added_by' => $user->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                AcademyMember::insert($insertData);

                $token = $request->bearerToken();
                $baseUrl = config('app.env') === 'production' ? config('CHAT_APP_HOST_PRODUCTION') : env('CHAT_APP_HOST');
                Http::withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                ])->put($baseUrl . '/conversation/update', [
                            'conversationId' => $academy->conversation_id,
                            'type' => 'group',
                            'addParticipants' => $addIds,
                        ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Academy members updated successfully',
            ]);
        } catch (Exception $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update academy members',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
