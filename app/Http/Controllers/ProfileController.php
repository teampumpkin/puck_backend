<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\V4User;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
        function updateProfile(Request $request)
    {
        try{
            /** @var V4User $user */

            $user = Auth::guard('v4api')->user();

            $validated = $request->validate([
                'first_name' => 'nullable|string|max:255',
                'last_name' => 'nullable|string|max:255',
                'email' => 'nullable|email|unique:v4_users,email,' . $user->id,
                'phone' => 'nullable|string|max:20',
                'country' => 'nullable|string|max:100',
                'state' => 'nullable|string|max:100',
                'city' => 'nullable|string|max:100',
                'date_of_birth' => 'nullable|date',
                'enable_private_account' => 'nullable|boolean',
                'receive_news_offers' => 'nullable|boolean',
                'terms_accepted' => 'nullable|boolean',
                'is_onboarded' => 'nullable|boolean',
                'zip' => 'nullable|string|max:20',
            ]);

            // echo $request;

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
                    $teamValidated = $request->validate([
                        'team_name' => 'nullable|string|max:255',
                        'administrator_first_name' => 'nullable|string|max:255',
                        'administrator_last_name' => 'nullable|string|max:255',
                        'leagues' => 'nullable|array',
                        'website' => 'nullable|string|max:255',
                        'address' => 'nullable|string|max:255',
                        'team_years_running' => 'nullable|integer'
                    ]);

                    $user->teamProfile()->updateOrCreate([], $teamValidated);
                    $user->load('teamProfile');
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
                        'references.*.phone' => 'required_with:references|string|max:20'
                    ]);

                    if ($request->hasFile('resume')) {
//                        $path = Storage::putFile('resumes', $request->file('resume'));
                        $path = $request->file('resume')->store(
                            'resume/'.$request->user()->id, 's3'
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
                case 'academy':
                    $academyValidated = $request->validate([
                        "academy_name" => "nullable|string|max:255",
                        "administrator_first_name" => "nullable|string|max:255",
                        "administrator_last_name" => "nullable|string|max:255",
                        "teams" => "nullable|array",
                        "leagues" => "nullable|array",
                        "website" => "nullable|string|max:255",
                        "address" => "nullable|string|max:255",
                        "academy_years_running" => "nullable|integer",
                        "main_team_name" => "nullable|string|max:255",
                    ]);
                    $user->academyProfile()->updateOrCreate([], $academyValidated);
                    $user->load('academyProfile');
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
                        'references.*.phone' => 'required_with:references|string|max:20'
                    ]);
                    if ($request->hasFile('resume')) {
                        $path = $request->file('resume')->store(
                            'resume/'.$request->user()->id, 's3'
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
            }

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'user' => $user,
            ]);

        }catch(ValidationException $e){
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }catch(Exception $e){
            return response()->json([
                'message' => 'Profile update failed.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    function addChild(Request $request){
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
                'email' => 'nullable|email|unique:v4_users,email',
                'teams' => 'nullable|array',
                'leagues' => 'nullable|array',
            ]);

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
                'email' => $validatedData['email'] ?? null,
            ]);

            return response()->json([
                'message' => 'Child account created successfully',
                'parent' => $parent,
                'child' => $child
            ], 201);
        } catch(ValidationException $e){
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }catch(Exception $e){
            return response()->json([
                'message' => 'Child add failed.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
