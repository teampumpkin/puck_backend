<?php

namespace Tests\Feature;

use App\Models\V4User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

// Sentry FLUTTER-AQ: an academy viewing a fan/team/... profile prefetches
// GET teams/{userId}; roles that can't be on teams answered 500 "Coming soon".
class TeamsForProfileTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): V4User
    {
        return V4User::forceCreate(['first_name' => 'U' . Str::random(4), 'email' => Str::random(8) . '@t.io', 'role' => $role]);
    }

    private function teamsOf(int $userId)
    {
        return $this->withHeaders(['Authorization' => 'Bearer ' . auth('v4api')->login($this->user('academy'))])
            ->getJson("/api/v4/teams/{$userId}");
    }

    public function test_roles_without_teams_get_an_empty_list(): void
    {
        foreach (['fan', 'team', 'parent', 'organizer', 'evaluator'] as $role) {
            $this->teamsOf($this->user($role)->id)
                ->assertOk()
                ->assertJsonPath('success', true)
                ->assertJsonPath('data', []);
        }
    }

    public function test_academy_without_academy_record_gets_an_empty_list(): void
    {
        $this->teamsOf($this->user('academy')->id)
            ->assertOk()
            ->assertJsonPath('data', []);
    }

    public function test_unknown_user_is_404(): void
    {
        $this->teamsOf(999999)->assertNotFound();
    }

    public function test_player_still_gets_teams_list(): void
    {
        $this->teamsOf($this->user('player')->id)->assertOk()->assertJsonPath('data', []);
    }
}
