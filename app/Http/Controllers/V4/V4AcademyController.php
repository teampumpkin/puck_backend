<?php

namespace App\Http\Controllers\V4;

use App\Http\Controllers\Controller;
use App\Models\AcademyMember;
use App\Models\V4Academy;
use App\Models\V4AcademyAdmin;
use App\Models\V4User;
use Illuminate\Validation\ValidationException;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Contracts\ErrorTrackerInterface;

class V4AcademyController extends Controller
{
    protected $errorTracker;

    public function __construct(ErrorTrackerInterface $errorTracker)
    {
        $this->errorTracker = $errorTracker;
    }

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

                // Try to update chat conversation (non-blocking)
                try {
                    $token = $request->bearerToken();
                    $baseUrl = config('services.chat.host');
                    Http::withHeaders([
                        'Authorization' => 'Bearer ' . $token,
                        'Content-Type' => 'application/json',
                    ])->put($baseUrl . '/conversation/update', [
                        'conversationId' => $academy->conversation_id,
                        'type' => 'group',
                        'removeParticipants' => $removeIds,
                    ]);
                } catch (Exception $chatError) {
                    // Log but don't fail the main operation
                    Log::warning('Failed to update chat conversation for removed members', [
                        'academy_id' => $academyId,
                        'error' => $chatError->getMessage()
                    ]);
                }
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

                // Try to update chat conversation (non-blocking)
                try {
                    $token = $request->bearerToken();
                    $baseUrl = config('services.chat.host');
                    Http::withHeaders([
                        'Authorization' => 'Bearer ' . $token,
                        'Content-Type' => 'application/json',
                    ])->put($baseUrl . '/conversation/update', [
                        'conversationId' => $academy->conversation_id,
                        'type' => 'group',
                        'addParticipants' => $addIds,
                    ]);
                } catch (Exception $chatError) {
                    // Log but don't fail the main operation
                    Log::warning('Failed to update chat conversation for added members', [
                        'academy_id' => $academyId,
                        'error' => $chatError->getMessage()
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Academy members updated successfully',
            ]);
        } catch (Exception $e) {

            DB::rollBack();



            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => __METHOD__,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update academy members',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getAcademyMembers(Request $request, $academyId, $role = null)
    {
        try {
            $authUser = auth()->user();

            if (!$authUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            // Validate just the id existence based on role
            $validator = Validator::make(['academyId' => $academyId], [
                'academyId' => 'required|integer|exists:v4_academies,id',
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $academyMembersQuery = AcademyMember::with([
                'player:id,first_name,last_name,role,profile_photo,email,country,date_of_birth,state,city,zip,username,enable_private_account'
            ])
                ->where('academy_id', $academyId);

            if ($role) {
                $academyMembersQuery->whereHas('player', function ($query) use ($role) {
                    $query->where('role', $role);
                });
            }

            // Map exactly same output structure as team member API
            $members = $academyMembersQuery->get();

            return response()->json([
                'success' => true,
                'message' => 'Fetched Members successfully',
                'data' => $members
            ]);
        } catch (ValidationException $e) {


            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => __METHOD__,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Not found',
            ], 404);
        } catch (Exception $e) {


            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => __METHOD__,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch members',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function getAcademyAdmins($academyId)
    {
        try {
            $validator = Validator::make(
                ['academyId' => $academyId],
                ['academyId' => 'required|integer|exists:v4_academies,id']
            );

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Academy not found',
                    'errors' => $validator->errors()
                ], 422);
            }

            $admins = V4AcademyAdmin::where('academy_id', $academyId)->get();

            return response()->json([
                'success' => true,
                'message' => 'Fetched academy admins successfully',
                'data' => $admins,
            ]);
        } catch (Exception $e) {


            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => __METHOD__,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch academy admins',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getAcademyAdminById($academyId, $id)
    {
        try {
            // Validate academy
            $validator = Validator::make(
                ['academyId' => $academyId],
                ['academyId' => 'required|integer|exists:v4_academies,id']
            );

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Academy not found',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Fetch admin belonging to this academy
            $admin = V4AcademyAdmin::where('academy_id', $academyId)->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Fetched academy admin successfully',
                'data' => $admin
            ], 200);
        } catch (ModelNotFoundException $e) {


            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => __METHOD__,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Academy admin not found',
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch academy admin',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function createAcademyAdmin(Request $request, $academyId)
    {
        try {
            $validator = Validator::make(
                ['academyId' => $academyId],
                ['academyId' => 'required|integer|exists:v4_academies,id']
            );

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Academy not found',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $request->validate([
                'profile_photo' => 'nullable|file|image|max:5120',
                'designation' => 'required|string|max:255',
                'name' => 'required|string|max:255',
                'email' => 'required|email',
                'phone' => 'required|string|max:50',
                'location' => 'required|string|max:255',
            ]);

            $data['academy_id'] = $academyId;

            if ($request->hasFile('profile_photo')) {
                $file = $request->file('profile_photo');
                $path = $file->storeAs('academy_admins', $file->getClientOriginalName(), 's3');
                $data['profile_photo'] = Storage::disk('s3')->url($path);
            }

            $admin = V4AcademyAdmin::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Academy admin created successfully',
                'data' => $admin
            ], 201);
        } catch (Exception $e) {


            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => __METHOD__,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create academy admin',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function updateAcademyAdmin(Request $request, $academyId, $id)
    {
        try {
            $validator = Validator::make(
                ['academyId' => $academyId],
                ['academyId' => 'required|integer|exists:v4_academies,id']
            );

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Academy not found',
                    'errors' => $validator->errors()
                ], 422);
            }

            $admin = V4AcademyAdmin::where('academy_id', $academyId)->findOrFail($id);

            $data = $request->validate([
                'profile_photo' => 'nullable|file|image|max:5120',
                'designation' => 'sometimes|string|max:255',
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email',
                'phone' => 'sometimes|string|max:50',
                'location' => 'sometimes|string|max:255',
            ]);

            if ($request->hasFile('profile_photo')) {
                $file = $request->file('profile_photo');
                $path = $file->storeAs('academy_admins', $file->getClientOriginalName(), 's3');
                $data['profile_photo'] = Storage::disk('s3')->url($path);
            }

            $admin->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Academy admin updated successfully',
                'data' => $admin
            ], 200);
        } catch (ModelNotFoundException $e) {


            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => __METHOD__,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Academy admin not found',
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update academy admin',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function deleteAcademyAdmin($academyId, $id)
    {
        try {
            $validator = Validator::make(
                ['academyId' => $academyId],
                ['academyId' => 'required|integer|exists:v4_academies,id']
            );

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Academy not found',
                    'errors' => $validator->errors()
                ], 422);
            }

            $admin = V4AcademyAdmin::where('academy_id', $academyId)->findOrFail($id);

            $admin->delete();

            return response()->json([
                'success' => true,
                'message' => 'Academy admin deleted successfully',
            ]);
        } catch (ModelNotFoundException $e) {


            // Track error in Sentry
            $this->errorTracker->captureException($e, [
                'action' => __METHOD__,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Academy admin not found',
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete academy admin',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
