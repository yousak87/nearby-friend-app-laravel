<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RelationshipSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();
        $relationships = [];
        $userIds = range(1, 10); // 10 user
        
        foreach ($userIds as $followerId) {
            $followed = [];
            
            // Generate 3 unique followee
            while (count($followed) < 3) {
                $followeeId = array_rand($userIds) + 1;
                
                // makes sure cant follow its self
                if ($followeeId !== $followerId && !in_array($followeeId, $followed)) {
                    $followed[] = $followeeId;
                    
                    $relationships[] = [
                        'follower_id' => $followerId,
                        'followee_id' => $followeeId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        }

        DB::table('relationships')->insert($relationships); 
    }
}
