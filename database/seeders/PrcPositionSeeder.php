<?php

namespace Database\Seeders;

use App\Models\PrcPosition;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class PrcPositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $position = PrcPosition::first();

        if (empty($position)) {
            $timestamp = Carbon::now();

            PrcPosition::insert([
                [
                    'position_name' => 'Right Wing',
                    'short_name'    => 'RW',
                    'status'        => 1,
                    'created_at'    => $timestamp,
                    'updated_at'    => $timestamp,
                ],
                [
                    'position_name' => 'Left Wing',
                    'short_name'    => 'LW',
                    'status'        => 1,
                    'created_at'    => $timestamp,
                    'updated_at'    => $timestamp,
                ],
                [
                    'position_name' => 'Forward',
                    'short_name'    => 'F',
                    'status'        => 1,
                    'created_at'    => $timestamp,
                    'updated_at'    => $timestamp,
                ],
                [
                    'position_name' => 'Defense',
                    'short_name'    => 'D',
                    'status'        => 1,
                    'created_at'    => $timestamp,
                    'updated_at'    => $timestamp,
                ],
                [
                    'position_name' => 'Goalie',
                    'short_name'    => 'G',
                    'status'        => 1,
                    'created_at'    => $timestamp,
                    'updated_at'    => $timestamp,
                ],
                [
                    'position_name' => 'Centre',
                    'short_name'    => 'C',
                    'status'        => 1,
                    'created_at'    => $timestamp,
                    'updated_at'    => $timestamp,
                ],
                [
                    'position_name' => 'Generic',
                    'short_name'    => 'Gn',
                    'status'        => 0,
                    'created_at'    => $timestamp,
                    'updated_at'    => $timestamp,
                ],
            ]);
        }
    }
}
