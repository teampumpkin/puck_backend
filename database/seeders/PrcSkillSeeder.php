<?php

namespace Database\Seeders;

use App\Models\PrcSkill;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class PrcSkillSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $skills = PrcSkill::first();
        if (empty($skills)) {
            $timestamp = Carbon::now();

            PrcSkill::insert([
                [
                    'skill'           => 'Speed',
                    'player_type'     => 'player',
                    'player_sub_type' => '',
                    'created_at'      => $timestamp,
                    'updated_at'      => $timestamp
                ],
                [
                    'skill'           => 'Agility',
                    'player_type'     => 'player',
                    'player_sub_type' => '',
                    'created_at'      => $timestamp,
                    'updated_at'      => $timestamp
                ],
                [
                    'skill'           => 'Puckhandling',
                    'player_type'     => 'player',
                    'player_sub_type' => '',
                    'created_at'      => $timestamp,
                    'updated_at'      => $timestamp
                ],
                [
                    'skill'           => 'Strength',
                    'player_type'     => 'player',
                    'player_sub_type' => '',
                    'created_at'      => $timestamp,
                    'updated_at'      => $timestamp
                ],
                [
                    'skill'           => 'Offensive',
                    'player_type'     => 'player',
                    'player_sub_type' => '',
                    'created_at'      => $timestamp,
                    'updated_at'      => $timestamp
                ],
                [
                    'skill'           => 'Leadership',
                    'player_type'     => 'player',
                    'player_sub_type' => '',
                    'created_at'      => $timestamp,
                    'updated_at'      => $timestamp
                ],
                [
                    'skill'           => 'Aggression',
                    'player_type'     => 'player',
                    'player_sub_type' => '',
                    'created_at'      => $timestamp,
                    'updated_at'      => $timestamp
                ],
                [
                    'skill'           => 'Shooting Accuracy',
                    'player_type'     => 'player',
                    'player_sub_type' => '',
                    'created_at'      => $timestamp,
                    'updated_at'      => $timestamp
                ],
                [
                    'skill'           => 'Acceleration',
                    'player_type'     => 'player',
                    'player_sub_type' => '',
                    'created_at'      => $timestamp,
                    'updated_at'      => $timestamp
                ],
                [
                    'skill'           => 'Defensive',
                    'player_type'     => 'player',
                    'player_sub_type' => '',
                    'created_at'      => $timestamp,
                    'updated_at'      => $timestamp
                ],
                [
                    'skill'           => 'Hitting',
                    'player_type'     => 'player',
                    'player_sub_type' => '',
                    'created_at'      => $timestamp,
                    'updated_at'      => $timestamp
                ],
                [
                    'skill'           => 'Shot Blocking',
                    'player_type'     => 'player',
                    'player_sub_type' => '',
                    'created_at'      => $timestamp,
                    'updated_at'      => $timestamp
                ],
            ]);
        }
    }
}
