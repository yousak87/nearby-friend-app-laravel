<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Relationship;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    private $authToken;
    private $testUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user
        $this->testUser = User::create([
            'name' => 'Test User',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'dob' => '1990-01-01',
            'address' => 'Test Address',
            'description' => 'Test user description',
            'longitude' => 106.845599,
            'latitude' => -6.208763,
        ]);

        // Login to get token
        $response = $this->postJson('api/users/login', [
            'username' => 'testuser',
            'password' => 'password123'
        ]);
        
        $this->authToken = $response['data']['token'];
    }

    public function testRegisterSuccess()
    {
        $response = $this->postJson('api/users', [
            'name' => 'New User',
            'username' => 'newuser',
            'email' => 'new@test.com',
            'password' => 'password123',
            'dob' => '1990-01-01',
            'address' => 'New Address',
            'description' => 'New user description',
            'longitude' => 106.845599,
            'latitude' => -6.208763,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id', 'name', 'username', 'email', 'dob', 'address', 
                    'description', 'longitude', 'latitude', 'messge'
                ]
            ]);
    }

    public function testRegisterFailedDuplicateUsername()
    {
        $response = $this->postJson('api/users', [
            'name' => 'Test User',
            'username' => 'testuser', // Duplicate username
            'email' => 'duplicate@test.com',
            'password' => 'password123',
            'dob' => '1990-01-01',
            'address' => 'Test Address',
            'description' => 'Test user description',
            'longitude' => 106.845599,
            'latitude' => -6.208763,
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'error' => [
                    'username' => ['username already registered']
                ]
            ]);
    }

    public function testLoginSuccess()
    {
        $response = $this->postJson('api/users/login', [
            'username' => 'testuser',
            'password' => 'password123'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id','username', 'name', 'token'
                ]
            ]);
    }

    public function testLoginFailedInvalidCredentials()
    {
        $response = $this->postJson('api/users/login', [
            'username' => 'testuser',
            'password' => 'wrongpassword'
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'errors' => [
                    'message' => ['username or password wrong']
                ]
            ]);
    }

    public function testGetAllUsers()
    {
        // Create additional users
        User::factory(10)->create();

        $response = $this->withHeaders([
            'Authorization' => $this->authToken
        ])->getJson('api/users?page=1');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id', 'name', 'username', 'email', 'dob', 'address', 
                        'description', 'longitude', 'latitude'
                    ]
                ],
                'links',
                'meta'
            ]);
    }

    public function testGetUserDetailSuccess()
    {
        $response = $this->withHeaders([
            'Authorization' => $this->authToken
        ])->getJson("api/users/detail/{$this->testUser->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $this->testUser->id,
                    'username' => 'testuser'
                ]
            ]);
    }

    public function testGetCurrentUser()
    {
        $response = $this->withHeaders([
            'Authorization' => $this->authToken
        ])->getJson('api/users/current');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'username' => 'testuser'
                ]
            ]);
    }

    public function testFindUserByUsername()
    {
        // Create a user to search for
        User::create([
            'name' => 'Searchable User',
            'username' => 'searchme',
            'email' => 'search@test.com',
            'password' => Hash::make('password123'),
            'dob' => '1990-01-01',
            'address' => 'Search Address',
            'description' => 'Searchable user',
            'longitude' => 106.845599,
            'latitude' => -6.208763,
        ]);

        $response = $this->withHeaders([
            'Authorization' => $this->authToken
        ])->getJson('api/users/find-by-username?username-keyword=search');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    ['username' => 'searchme']
                ]
            ]);
    }

    public function testFollowUser()
    {
        // Create a user to follow
        $userToFollow = User::create([
            'name' => 'Follow Me',
            'username' => 'followme',
            'email' => 'follow@test.com',
            'password' => Hash::make('password123'),
            'dob' => '1990-01-01',
            'address' => 'Follow Address',
            'description' => 'User to follow',
            'longitude' => 106.845599,
            'latitude' => -6.208763,
        ]);

        $response = $this->withHeaders([
            'Authorization' => $this->authToken
        ])->postJson("api/users/follow/{$userToFollow->id}");

        $response->assertStatus(201)
            ->assertJson([
                'message' => 'Successfully followed user'
            ]);
    }

    public function testUnfollowUser()
    {
        // Create a user to follow
        $userToFollow = User::create([
            'name' => 'Follow Me',
            'username' => 'followme',
            'email' => 'follow@test.com',
            'password' => Hash::make('password123'),
            'dob' => '1990-01-01',
            'address' => 'Follow Address',
            'description' => 'User to follow',
            'longitude' => 106.845599,
            'latitude' => -6.208763,
        ]);

        // Create follow relationship
        Relationship::create([
            'follower_id' => $this->testUser->id,
            'followee_id' => $userToFollow->id
        ]);
        Relationship::create([
            'follower_id' => $userToFollow->id,
            'followee_id' => $this->testUser->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => $this->authToken
        ])->deleteJson("api/users/unfollow/{$userToFollow->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Successfully unfollowed user'
            ]);
    }

    public function testUpdateUser()
    {
        $response = $this->withHeaders([
            'Authorization' => $this->authToken
        ])->putJson("api/users/{$this->testUser->id}", [
            'name' => 'Updated Name',
            'email' => 'updated@test.com',
            'password' => 'newpassword',
            'dob' => '1995-01-01',
            'address' => 'Updated Address',
            'description' => 'Updated description',
            'latitude' => -6.208700,
            'longitude' => 106.845600
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'name' => 'Updated Name',
                    'email' => 'updated@test.com'
                ]
            ]);
    }

    public function testDeleteUser()
    {
        $response = $this->withHeaders([
            'Authorization' => $this->authToken
        ])->deleteJson("api/users/{$this->testUser->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'User account deleted successfully'
            ]);
    }

    public function testGetFollowers()
    {
        // Create a follower
        $follower = User::create([
            'name' => 'Follower User',
            'username' => 'follower',
            'email' => 'follower@test.com',
            'password' => Hash::make('password123'),
            'dob' => '1990-01-01',
            'address' => 'Follower Address',
            'description' => 'Follower user',
            'longitude' => 106.845599,
            'latitude' => -6.208763,
        ]);

        // Create follow relationship
        Relationship::create([
            'follower_id' => $follower->id,
            'followee_id' => $this->testUser->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => $this->authToken
        ])->getJson('api/users/followers');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    ['username' => 'follower']
                ]
            ]);
    }

    public function testFindNearbyFriends()
    {
        // Create a nearby friend
        $nearbyUser = User::create([
            'name' => 'Nearby Friend',
            'username' => 'nearby',
            'email' => 'nearby@test.com',
            'password' => Hash::make('password123'),
            'dob' => '1990-01-01',
            'address' => 'Nearby Address',
            'description' => 'Nearby friend',
            'longitude' => 106.845600, // ~100m from base
            'latitude' => -6.208764,
        ]);

        // Create follow relationship
        Relationship::create([
            'follower_id' => $nearbyUser->id,
            'followee_id' => $this->testUser->id
        ]);

        $response = $this->withHeaders([
            'Authorization' => $this->authToken
        ])->getJson('api/users/find-nearby-friends?radius=1'); // 1km radius

        $response->assertStatus(200)
            ->assertJson([
                [
                    'username' => 'nearby'
                ]
            ]);
    }
}