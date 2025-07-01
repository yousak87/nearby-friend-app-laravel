<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    // Center point (Jakarta)
    const BASE_LAT = -6.2087634;
    const BASE_LNG = 106.845599;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Temporarily disable foreign key constraints
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('users')->truncate();
        DB::table('relationships')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $users = [];
        $now = now();

        // ================================================
        // User 1: Center (main)
        // ================================================
        $users[] = [
            'username'    => 'user_center',
            'name'        => 'Center User',
            'email'       => 'user1@example.com',
            'password'    => Hash::make('password1'),
            'dob'         => now()->subYears(rand(25, 40))->subDays(rand(1, 365)),
            'address'     => 'Central Business District',
            'description' => 'Primary user at center location',
            'latitude'    => self::BASE_LAT,
            'longitude'   => self::BASE_LNG,
            'token'       => null,
            'created_at'  => $now,
            'updated_at'  => $now,
        ];

        // ================================================
        // Distance-specific data (2 users each)
        // ================================================
        
        // Convert distance (km) to degrees (approx: 1° = 111km)
        $distances = [
            10 => 0.09009,
            20 => 0.18018,
            30 => 0.27027,
            50 => 0.45045
        ];

        $counter = 2; // Start from user id 2
        
        foreach ($distances as $km => $deg) {
            for ($i = 1; $i <= 2; $i++) {
                // Random direction (in radians)
                $angle = deg2rad(mt_rand(0, 359));
                
                // Calculate offset
                $latOffset = $deg * cos($angle);
                $lngOffset = $deg * sin($angle);
                
                $users[] = [
                    'username'    => 'user_' . $km . 'km_' . $i,
                    'name'        => 'User ' . $counter,
                    'email'       => 'user' . $counter . '@example.com',
                    'password'    => Hash::make('password' . $counter),
                    'dob'         => now()->subYears(rand(25, 40))->subDays(rand(1, 365)),
                    'address'     => $km . 'km Radius Area ' . $i,
                    'description' => 'Located ' . $km . 'km from center',
                    'latitude'    => self::BASE_LAT + $latOffset,
                    'longitude'   => self::BASE_LNG + $lngOffset,
                    'token'       => null,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ];
                $counter++;
            }
        }

        // ================================================
        // Additional center user (total 2 center users)
        // ================================================
        $users[] = [
            'username'    => 'user_center2',
            'name'        => 'Center User 2',
            'email'       => 'user' . $counter . '@example.com',
            'password'    => Hash::make('password' . $counter),
            'dob'         => now()->subYears(rand(25, 40))->subDays(rand(1, 365)),
            'address'     => 'Central Plaza',
            'description' => 'Additional user at center location',
            'latitude'    => self::BASE_LAT,
            'longitude'   => self::BASE_LNG,
            'token'       => Str::random(60),
            'created_at'  => $now,
            'updated_at'  => $now,
        ];

        DB::table('users')->insert($users);
    }
}