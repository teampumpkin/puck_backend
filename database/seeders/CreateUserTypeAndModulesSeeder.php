<?php

namespace Database\Seeders;

use App\Models\PrcModule;
use App\Models\PrcUserType;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CreateUserTypeAndModulesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('prc_user_type_modules')->truncate();
        PrcModule::truncate();
        PrcUserType::truncate();

        $timestamp = Carbon::now();

        PrcUserType::insert([
            [
                'type_name'  => 'Developer',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'type_name'  => 'Player',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'type_name'  => 'Evaluator',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'type_name'  => 'Team',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'type_name'  => 'Academy',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'type_name'  => 'Fan',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'type_name'  => 'Scout',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'type_name'  => 'Admin',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'type_name'  => 'Parent',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]
        ]);

        PrcModule::insert([
            [
                'module_name' => 'Get Profile',
                'api_route'   => 'get-profile',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ],
            [
                'module_name' => 'Edit Profile',
                'api_route'   => 'edit-profile',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ],
            [
                'module_name' => 'Change Password',
                'api_route'   => 'change-password',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ],
            [
                'module_name' => 'Get Top Players',
                'api_route'   => 'get-top-players',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ],
            [
                'module_name' => 'Get All Players',
                'api_route'   => 'get-all-players',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ],
            [
                'module_name' => 'Save/UnSave User',
                'api_route'   => 'save-unsave-user',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ],
            [
                'module_name' => 'Follow/UnFollow User',
                'api_route'   => 'follow-unfollow-user',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ],
            [
                'module_name' => 'Block/UnBlock User',
                'api_route'   => 'block-unblock-user',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ],
            [
                'module_name' => 'Get all followers',
                'api_route'   => 'get-followers',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ],
            [
                'module_name' => 'Get all Followings',
                'api_route'   => 'get-followings',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ],
            [
                'module_name' => 'Filter Users',
                'api_route'   => 'filter',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ],
            [
                'module_name' => 'Get user Information',
                'api_route'   => 'get-user-profile',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ],
            [
                'module_name' => 'Team List',
                'api_route'   => 'team-list',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ],
            [
                'module_name' => 'Team Details',
                'api_route'   => 'team',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ],
            [
                'module_name' => 'Get All Skill for Evaluating',
                'api_route'   => 'get-skills',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ],
            [
                'module_name' => 'Dashboard',
                'api_route'   => 'dashboard',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ],
            [
                'module_name' => 'Get All Evaluators',
                'api_route'   => 'get-all-evaluators',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ],
            [
                'module_name' => 'Get All Teams',
                'api_route'   => 'get-all-teams',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ],
            [
                'module_name' => 'Evaluator Status Change',
                'api_route'   => 'evaluator-status-change',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ],
            [
                'module_name' => 'Team Status Change',
                'api_route'   => 'team-status-change',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ],
            [
                'module_name' => 'Get Reports',
                'api_route'   => 'get-reports',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ],
            [
                'module_name' => 'Get Report Details',
                'api_route'   => 'get-report',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ],
            [
                'module_name' => 'Save/UnSave Report',
                'api_route'   => 'save-unsave-report',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ],
            [
                'module_name' => 'Get Saved Players',
                'api_route'   => 'get-saved-players',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ],
            [
                'module_name' => 'Get Saved Teams',
                'api_route'   => 'get-saved-teams',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ],
            [
                'module_name' => 'Get Saved Reports',
                'api_route'   => 'get-saved-reports',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ], [
                'module_name' => 'Add Manager',
                'api_route'   => 'add-manager',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ],
            [
                'module_name' => 'Add Coach',
                'api_route'   => 'add-coach',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ],
            [
                'module_name' => 'Add Player',
                'api_route'   => 'add-player',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ],
            [
                'module_name' => 'Get Scout Request',
                'api_route'   => 'get-scout-requests',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ],
            [
                'module_name' => 'Request Status Update',
                'api_route'   => 'request-status-update',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ],
            [
                'module_name' => 'Submit scouting report',
                'api_route'   => 'submit-scouting-report',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ],
            [
                'module_name' => 'Publish Scouting Report',
                'api_route'   => 'publish-scouting-report',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ],
            [
                'module_name' => 'Send Scout Request',
                'api_route'   => 'send-scout-request',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ],
            [
                'module_name' => 'Cancel Scout Request',
                'api_route'   => 'cancel-scout-request',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ],
            [
                'module_name' => 'Medias',
                'api_route'   => 'medias',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ],
            [
                'module_name' => 'Media Upload',
                'api_route'   => 'media-upload',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ],
            [
                'module_name' => 'Scout Status Change',
                'api_route'   => 'scout-status-change',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ],
            [
                'module_name' => 'Modules',
                'api_route'   => 'modules',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ],
            [
                'module_name' => 'User Types',
                'api_route'   => 'user-types',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ],
            [
                'module_name' => 'Update Permission',
                'api_route'   => 'update-permission',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ],
            [
                'module_name' => 'Academy List',
                'api_route'   => 'get-all-academies',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ], [
                'module_name' => 'Add New Module',
                'api_route'   => 'add-module',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ], [
                'module_name' => 'Remove Media',
                'api_route'   => 'media-delete',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ], [
                'module_name' => 'Player Position',
                'api_route'   => 'get-positions',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ], [
                'module_name' => 'Filters',
                'api_route'   => 'get-filters',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ], [
                'module_name' => 'Edit Team Member',
                'api_route'   => 'edit-team-member',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ], [
                'module_name' => 'Remove Team Member',
                'api_route'   => 'remove-team-member',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ], [
                'module_name' => 'Zoho Plans',
                'api_route'   => 'zoho-plans',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ], [
                'module_name' => 'Generate Payement Page',
                'api_route'   => 'generate-payment-page',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ], [
                'module_name' => 'Chat Id',
                'api_route'   => 'get-chat-id',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ], [
                'module_name' => 'Recent Chat',
                'api_route'   => 'recent-chats',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ], [
                'module_name' => 'Cancel Subscription',
                'api_route'   => 'cancel-subscription',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ],
            [
                'module_name' => 'Media Download',
                'api_route'   => 'media-download',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ],
            [
                'module_name' => 'Get Profile Picture',
                'api_route'   => 'get-profile-picture',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ],
            [
                'module_name' => 'Get Playable List',
                'api_route'   => 'get-playables',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ],
            [
                'module_name' => 'Get Notification Preferences',
                'api_route'   => 'notification-preferences',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ],
            [
                'module_name' => 'Update Notification Preferences',
                'api_route'   => 'update-notification-preferences',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ],
            [
                'module_name' => 'Activate / Deactivate Account',
                'api_route'   => 'change-status-account',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ],
            [
                'module_name' => 'Register Evaluator',
                'api_route'   => 'register-evaluator',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ],
            [
                'module_name' => 'Pay Playable',
                'api_route'   => 'pay-playable',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ],
            [
                'module_name' => 'Create Customer Stripe',
                'api_route'   => 'stripe/create-customer',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ],
            [
                'module_name' => 'Get Customer Stripe',
                'api_route'   => 'stripe/get-customer',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ],
            [
                'module_name' => 'Create Payment Method Stripe',
                'api_route'   => 'stripe/create-payment-method',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ],
            [
                'module_name' => 'Attach Payment Method Stripe',
                'api_route'   => 'stripe/attach-payment-method',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ],
            [
                'module_name' => 'Detach Payment Method Stripe',
                'api_route'   => 'stripe/detach-payment-method',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ],
            [
                'module_name' => 'Get Payment Methods Stripe',
                'api_route'   => 'stripe/get-payment-methods',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ],
            [
                'module_name' => 'Create Payment Intent Stripe',
                'api_route'   => 'stripe/create-payment-intent',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ],
            [
                'module_name' => 'Confirm Payment Intent Stripe',
                'api_route'   => 'stripe/confirm-payment-intent',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ],
            [
                'module_name' => 'Get Skills categories',
                'api_route'   => 'v2/get-skills',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ],
            [
                'module_name' => 'Submit Scouting Report',
                'api_route'   => 'v3/submit-scouting-report',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ],
            [
                'module_name' => 'Edit Media Name',
                'api_route'   => 'media-edit',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ],
            [
                'module_name' => 'Edit Scout Request',
                'api_route'   => 'update-scout-request',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ],
            [
                'module_name' => 'Get Payments list',
                'api_route'   => 'stripe/get-payments',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ],
            [
                'module_name' => 'Search V2',
                'api_route'   => 'v2/search',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ],
            [
                'module_name' => 'Get Players V2',
                'api_route'   => 'v2/get-players',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ],
            [
                'module_name' => 'Get Teams List V2',
                'api_route'   => 'v2/team-list',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ],
            [
                'module_name' => 'Get Saved Teams V2',
                'api_route'   => 'v2/get-saved-teams',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ],
            [
                'module_name' => 'Get Scout Request V2',
                'api_route'   => 'v2/get-scout-requests',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ],
            [
                'module_name' => 'Get Reports V2',
                'api_route'   => 'v2/get-reports',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ],
            [
                'module_name' => 'Get Report V2',
                'api_route'   => 'v2/get-report',
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ],
        ]);

        // Deleveloper - 1
        $user_type_modules = PrcUserType::with('allowModules')->where('type_name', 'Developer')->first();

        if (empty($user_type_modules->allowModules->toArray())) {
            $prc_modules = PrcModule::where('status', 1)->get();

            foreach ($prc_modules as $prc_module) {
                $user_type_modules->allowModules()->attach($prc_module->id);
            }
        }

        // Player - 2
        $user_type_modules = PrcUserType::with('allowModules')->where('type_name', 'Player')->first();

        if (empty($user_type_modules->allowModules->toArray())) {
            $prc_modules = PrcModule::where('status', 1)->get();

            $permits = [1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,21,22,23,24,25,26,30,31,32,33,34,36,37,44,45,46,47,51,52,54,55,56,57,58,59,61,62,63,64,65,66,67,68,69,70,72,74,75,76,77,78,79,80,81];

            foreach ($prc_modules as $prc_module) {
                if(in_array($prc_module->id, $permits)){
                    $user_type_modules->allowModules()->attach($prc_module->id);
                }
            }
        }

        // Evaluator - 3
        $user_type_modules = PrcUserType::with('allowModules')->where('type_name', 'Evaluator')->first();

        if (empty($user_type_modules->allowModules->toArray())) {
            $prc_modules = PrcModule::where('status', 1)->get();

            $permits = [1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,21,22,23,24,25,26,30,31,32,33,34,36,46,47,51,52,54,55,56,57,58,59,61,62,63,64,65,66,67,68,69,70,71,72,74,75,76,77,78,79,80,81];

            foreach ($prc_modules as $prc_module) {
                if(in_array($prc_module->id, $permits)){
                    $user_type_modules->allowModules()->attach($prc_module->id);
                }
            }
        }

        // Team - 4
        $user_type_modules = PrcUserType::with('allowModules')->where('type_name', 'Team')->first();

        if (empty($user_type_modules->allowModules->toArray())) {
            $prc_modules = PrcModule::where('status', 1)->get();

            $permits = [1,2,3,4,5,6,7,8,9,10,11,12,13,14,21,22,23,24,25,26,27,28,29,34,36,42,46,47,48,51,52,54,55,56,57,58,59,61,62,63,64,65,66,67,68,69,72,74,75,76,77,78,79,80,81];

            foreach ($prc_modules as $prc_module) {
                if(in_array($prc_module->id, $permits)){
                    $user_type_modules->allowModules()->attach($prc_module->id);
                }
            }
        }

        // Academy - 5
        $user_type_modules = PrcUserType::with('allowModules')->where('type_name', 'Academy')->first();

        if (empty($user_type_modules->allowModules->toArray())) {
            $prc_modules = PrcModule::where('status', 1)->get();

            $permits = [1,2,3,4,5,6,7,8,9,10,11,12,13,14,21,22,23,24,25,26,27,28,29,34,36,37,46,47,48,49,50,51,52,53,54,55,56,57,58,59,61,62,63,64,65,66,67,68,69,72,74,75,76,77,78,79,80,81];

            foreach ($prc_modules as $prc_module) {
                if(in_array($prc_module->id, $permits)){
                    $user_type_modules->allowModules()->attach($prc_module->id);
                }
            }
        }

        // Fan - 6
        $user_type_modules = PrcUserType::with('allowModules')->where('type_name', 'Fan')->first();

        if (empty($user_type_modules->allowModules->toArray())) {
            $prc_modules = PrcModule::where('status', 1)->get();

            $permits = [1,2,3,4,5,6,7,8,9,10,11,12,13,14,21,22,23,24,25,34,36,42,46,47,51,52,54,55,56,57,58,59,61,62,63,64,65,66,67,68,69,72,74,75,76,77,78,79,80,81];

            foreach ($prc_modules as $prc_module) {
                if(in_array($prc_module->id, $permits)){
                    $user_type_modules->allowModules()->attach($prc_module->id);
                }
            }
        }

        // Scout - 7
        $user_type_modules = PrcUserType::with('allowModules')->where('type_name', 'Scout')->first();

        if (empty($user_type_modules->allowModules->toArray())) {
            $prc_modules = PrcModule::where('status', 1)->get();

            $permits = [1,2,3,4,5,6,7,8,9,10,11,12,13,14,21,22,23,24,25,26,34,36,46,47,51,52,54,55,56,57,58,59,61,62,63,64,65,66,67,68,69,72,74,75,76,77,78,79,80,81];

            foreach ($prc_modules as $prc_module) {
                if(in_array($prc_module->id, $permits)){
                    $user_type_modules->allowModules()->attach($prc_module->id);
                }
            }
        }

        // Admin - 8
        $user_type_modules = PrcUserType::with('allowModules')->where('type_name', 'Admin')->first();

        if (empty($user_type_modules->allowModules->toArray())) {
            $prc_modules = PrcModule::where('status', 1)->get();

            $permits = [1,2,3,5,11,12,13,14,15,16,17,18,19,20,30,31,34,36,38,42,46,47,54,55,56,57,58,59,60,61,62,63,64,65,66,67,68,69,70,71,72,73,74,75,76,77,78,79,80,81];

            foreach ($prc_modules as $prc_module) {
                if(in_array($prc_module->id, $permits)){
                    $user_type_modules->allowModules()->attach($prc_module->id);
                }
            }
        }

        // Parent - 9
        $user_type_modules = PrcUserType::with('allowModules')->where('type_name', 'Parent')->first();

        if (empty($user_type_modules->allowModules->toArray())) {
            $prc_modules = PrcModule::where('status', 1)->get();

            $permits = [1,2,3,4,5,6,7,8,9,10,11,12,24,25,34,36,46,47,51,52,54,55,56,57,58,59,61,62,63,64,65,66,67,68,69,72,74,75,76,77,78,79,80,81];

            foreach ($prc_modules as $prc_module) {
                if(in_array($prc_module->id, $permits)){
                    $user_type_modules->allowModules()->attach($prc_module->id);
                }
            }
        }
    }
}
