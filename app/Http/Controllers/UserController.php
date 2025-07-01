<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRegisterRequest;
use App\Http\Requests\UserLoginRequest;
use App\Http\Resources\UserCollection;
use App\Http\Resources\UserResource;
use App\Http\Resources\UserLoginResource;
use App\Models\Location;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use App\Models\Relationship;
use Illuminate\Support\Facades\Log; // Added Log facade

/**
 * @group User Management
 * 
 * Endpoints for managing users, authentication, and relationships
 */
class UserController extends Controller
{
    /**
     * Register a new user
     * 
     * Creates a new user account with provided credentials
     * 
     * @param UserRegisterRequest $request Validated registration data
     * @return JsonResponse User resource with 201 status
     * 
     * @response 201 {"data": {"username": "john_doe", ...}}
     * @response 400 {"error": {"username": ["username already registered"]}}
     */
    public function register(UserRegisterRequest $request): JsonResponse
    {
        Log::info('User registration attempt', ['username' => $request->username]);
        $data = $request->validated();

        if (User::where('username', $data['username'])->count() == 1) {
            Log::warning('Registration failed: username already exists', ['username' => $data['username']]);
            throw new HttpResponseException(response([
                "error" => [
                    "username" => [
                        "username already registered"
                    ]
                ]
            ], 400));
        }
        try {
            $user = new User($data);
            $user->password = Hash::make($data['password']);
            $user->save();
            $user->message = "Success register User Data";
            $response = new UserResource($user);

            Log::info('User registered successfully', ['user_id' => $user->id]);
            return ($response)->response()->setStatusCode(201);
        } catch (Exception $e) {
            Log::error('User registration failed', ['error' => $e->getMessage()]);
            throw new HttpResponseException(response([
                "error" => [
                    "message" => [
                        "failed to register user " . $e->getMessage()
                    ]
                ]
            ], 400));
        }
    }

    /**
     * Authenticate user
     * 
     * Logs in a user and generates an access token
     * 
     * @param UserLoginRequest $request Validated login credentials
     * @return UserLoginResource User resource with authentication token
     * 
     * @response {"data": {"username": "john_doe", "token": "abcd1234...", ...}}
     * @response 401 {"errors": {"message": ["username or password wrong"]}}
     */
    public function login(UserLoginRequest $request): UserLoginResource
    {
        Log::info('User login attempt', ['username' => $request->username]);
        $data = $request->validated();

        $user = User::where('username', $data['username'])->first();
        if (!$user || !Hash::check($data['password'], $user->password)) {
            Log::warning('Login failed: invalid credentials', ['username' => $data['username']]);
            throw new HttpResponseException(response([
                "errors" => [
                    "message" => [
                        "username or password wrong"
                    ]
                ]
            ], 401));
        }

        $user->token = Str::uuid()->toString();
        $user->save();

        Log::info('User logged in successfully', ['user_id' => $user->id]);
        return new UserLoginResource($user);
    }

    /**
     * Get paginated users
     * 
     * Returns a paginated list of all users
     * 
     * @queryParam page required Page number for pagination
     * @return UserCollection Paginated collection of users
     * 
     * @response {"data": [{"id": 1, "username": "john_doe", ...}], "links": {...}, "meta": {...}}
     * @response 400 {"errors": {"message": ["page number is not valid or missing from request"]}}
     */
    public function getAllUser(Request $request): UserCollection
    {
        Log::debug('Fetching paginated users', ['page' => $request->page ?? 'N/A']);
        if ($request->has('page')) {
            $users = User::orderBy('username', 'desc')->paginate(5);
            Log::info('Paginated users retrieved', ['page' => $request->page, 'count' => count($users)]);
            return new UserCollection($users);
        } else {
            Log::warning('Get all users failed: missing page parameter');
            throw new HttpResponseException(response([
                "errors" => [
                    "message" => [
                        "page number is not valid or missing from request"
                    ]
                ]
            ], 400));
        }
    }

    /**
     * Get user details
     * 
     * Returns detailed information about a specific user
     * 
     * @urlParam id required User ID
     * @return UserResource User resource with details
     * 
     * @response {"data": {"id": 1, "username": "john_doe", ...}}
     * @response 400 {"errors": {"message": ["id cant be empty"]}}
     * @response 422 {"errors": {"message": ["cant found any user for this id"]}}
     */
    public function getUserDetail($id): UserResource
    {
        Log::debug('Fetching user details', ['user_id' => $id]);
        if ($id) {
            $users = User::where('id', $id)->get();
        } else {
            Log::warning('Get user details failed: empty ID');
            throw new HttpResponseException(response([
                "errors" => [
                    "message" => [
                        "id cant be empty"
                    ]
                ]
            ], 400));
        }
        

        if(!$users || count($users) < 1) {
            Log::warning('User details not found', ['user_id' => $id]);
            throw new HttpResponseException(response([
                "errors" => [
                    "message" => [
                        "cant found any user for this id"
                    ]
                ]
            ], 422));
        }

        $users[0]->message = "Success Update User Data";
        Log::info('User details retrieved', ['user_id' => $id]);
        return new UserResource($users[0]);
    }

    /**
     * Get user's followers
     * 
     * Returns a list of users who follow the authenticated user
     * 
     * @authenticated
     * @return UserCollection Collection of followers
     * 
     * @response {"data": [{"id": 2, "username": "follower1", ...}]}
     */
    public function getFollowers(Request $request): UserCollection
    {
        $user = Auth::user();
        Log::debug('Fetching followers', ['user_id' => $user->id]);
        
        $followers = User::whereHas('following', function ($query) use ($user) {
                $query->where('followee_id', $user->id);
            })
            ->orderBy('username', 'desc')->get();

        Log::info('Followers retrieved', ['user_id' => $user->id, 'count' => count($followers)]);
        return new UserCollection($followers);
    }

    /**
     * Get current user
     * 
     * Returns authenticated user's details
     * 
     * @authenticated
     * @return UserResource Authenticated user's details
     * 
     * @response {"data": {"id": 1, "username": "current_user", ...}}
     */
    public function getCurrentUser(Request $request): UserResource
    {
        $user = Auth::user();
        $user->message = "Success get User Data";
        Log::debug('Current user details retrieved', ['user_id' => $user->id]);
        return new UserResource($user);
    }

    /**
     * Find users by username
     * 
     * Search users by username keyword
     * 
     * @queryParam username-keyword required Search keyword
     * @return UserCollection Collection of matching users
     * 
     * @response {"data": [{"id": 1, "username": "john_doe", ...}]}
     * @response 400 {"error": {"message": "Validation error", ...}}
     */
    public function findUserByUsername(Request $request): UserCollection
    {
        $validator = Validator::make($request->all(), [
            'username-keyword' => 'required|string|max:100',
        ]);

        if ($validator->fails()) {
            Log::warning('User search failed: validation error', $validator->errors()->toArray());
            throw new HttpResponseException(response([
                "error" => [
                   'message' => 'Validation error',
                'errors' => $validator->errors()
                ]
            ], 400));
        }

        $username = $request->query('username-keyword');
        Log::debug('Searching users by username', ['keyword' => $username]);

        $user = User::where('username', 'LIKE', "%{$username}%")
            ->orderBy('username', 'desc')
            ->get();

        Log::info('User search completed', ['keyword' => $username, 'results' => count($user)]);
        return new UserCollection($user);
    }

    /**
     * Update user profile
     * 
     * Updates authenticated user's profile information
     * 
     * @authenticated
     * @urlParam id required User ID
     * @bodyParam name string required User's full name
     * @bodyParam email string required User's email
     * @bodyParam password string required New password
     * @bodyParam dob string required Date of birth (YYYY-MM-DD)
     * @bodyParam address string required Physical address
     * @bodyParam description string required Profile description
     * @bodyParam latitude number required Location latitude
     * @bodyParam longitude number required Location longitude
     * @return UserResource Updated user resource
     * 
     * @response {"data": {"id": 1, "username": "updated_user", ...}}
     * @response 422 {"message": "Validation error", ...}
     */
    public function updateUser(Request $request, $id)
    {
        Log::info('User update initiated', ['user_id' => $id]);
        $user = User::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required',
            'password' => 'required',
            'dob' => 'required',
            'address' => 'required',
            'description' => 'required',
            'latitude' => 'required',
            'longitude' => 'required'
        ]);

        if ($validator->fails()) {
            Log::warning('User update validation failed', ['user_id' => $id, 'errors' => $validator->errors()]);
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->all();

        if ($request->has('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);
        $user->message = "Success Update User Data";

        Log::info('User updated successfully', ['user_id' => $id]);
        return new UserResource($user);
    }

    /**
     * Delete user account
     * 
     * Permanently deletes a user account
     * 
     * @authenticated
     * @urlParam id required User ID
     * @return JsonResponse Success message
     * 
     * @response 200 {"message": "User account deleted successfully"}
     */
    public function deleteUser($id)
    {
        Log::info('User deletion initiated', ['user_id' => $id]);
        $user = User::findOrFail($id);
        $user->delete();

        Log::warning('User account deleted', ['user_id' => $id]);
        return response()->json([
            'message' => 'User account deleted successfully'
        ], 200);
    }

    /**
     * Follow another user
     * 
     * Creates a mutual following relationship between users
     * 
     * @authenticated
     * @urlParam id required User ID to follow
     * @return JsonResponse Success message
     * 
     * @response 201 {"message": "Successfully followed user"}
     * @response 400 {"errors": {"message": ["id cant be empty"]}}
     * @response 422 {"message": "You cannot follow yourself"}
     * @response 409 {"message": "You are already following this user"}
     */
    public function follow($id)
    {
        Log::debug('Follow attempt', ['target_user_id' => $id]);
        if ($id) {
            $followeeUsers = User::where('id', $id)->get();
        } else {
            Log::warning('Follow failed: empty ID');
            throw new HttpResponseException(response([
                "errors" => [
                    "message" => [
                        "id cant be empty"
                    ]
                ]
            ], 400));
        }

        if(!$followeeUsers || count($followeeUsers) < 1) {
            Log::warning('Follow failed: user not found', ['target_user_id' => $id]);
            throw new HttpResponseException(response([
                "errors" => [
                    "message" => [
                        "cant found any user for this id"
                    ]
                ]
            ], 422));
        }
        $user = Auth::user();
        $followerId = (int)$user->id;
        $followeeId = (int)$id;

        // Prevent self-follow
        if ($followerId == $followeeId) {
            Log::warning('Follow failed: self-follow attempt', ['user_id' => $followerId]);
            return response()->json([
                'message' => 'You cannot follow yourself'
            ], 422);
        }

        // Check if already following
        $exists = Relationship::where('follower_id', $followerId)
            ->where('followee_id', $followeeId)
            ->exists();

        if ($exists) {
            Log::info('Follow failed: already following', [
                'follower_id' => $followerId,
                'followee_id' => $followeeId
            ]);
            return response()->json([
                'message' => 'You are already following this user'
            ], 409);
        }

        // Create 2 way relationship
        Relationship::create([
            'follower_id' => $followerId,
            'followee_id' => $followeeId
        ]);
        Relationship::create([
            'follower_id' => $followeeId,
            'followee_id' => $followerId
        ]);

        Log::info('Follow relationship created', [
            'follower_id' => $followerId,
            'followee_id' => $followeeId
        ]);
        return response()->json([
            'message' => 'Successfully followed user'
        ], 201);
    }

    /**
     * Unfollow another user
     * 
     * Removes mutual following relationship between users
     * 
     * @authenticated
     * @urlParam id required User ID to unfollow
     * @return JsonResponse Success message
     * 
     * @response 200 {"message": "Successfully unfollowed user"}
     * @response 400 {"errors": {"message": ["id cant be empty"]}}
     * @response 404 {"message": "You were not following this user"}
     */
    public function unfollow($id)
    {
        Log::debug('Unfollow attempt', ['target_user_id' => $id]);
        if ($id) {
            $followeeUsers = User::where('id', $id)->get();
        } else {
            Log::warning('Unfollow failed: empty ID');
            throw new HttpResponseException(response([
                "errors" => [
                    "message" => [
                        "id cant be empty"
                    ]
                ]
            ], 400));
        }

        if(!$followeeUsers || count($followeeUsers) < 1) {
            Log::warning('Unfollow failed: user not found', ['target_user_id' => $id]);
            throw new HttpResponseException(response([
                "errors" => [
                    "message" => [
                        "cant found any user for this id"
                    ]
                ]
            ], 422));
        }

        $user = Auth::user();
        $followerId = (int)$user->id;
        $followeeId = (int)$id;

        // Delete 2 way relationship
        $deleted1 = Relationship::where('follower_id', $followerId)
            ->where('followee_id', $followeeId)
            ->delete();

        $deleted2 = Relationship::where('follower_id', $followeeId)
            ->where('followee_id', $followerId)
            ->delete();

        if ($deleted1 && $deleted2) {
            Log::info('Unfollow successful', [
                'follower_id' => $followerId,
                'followee_id' => $followeeId
            ]);
            return response()->json([
                'message' => 'Successfully unfollowed user'
            ], 200);
        }

        Log::info('Unfollow failed: no relationship found', [
            'follower_id' => $followerId,
            'followee_id' => $followeeId
        ]);
        return response()->json([
            'message' => 'You were not following this user'
        ], 404);
    }

    /**
     * Check following status
     * 
     * Checks if current user is following another user
     * 
     * @authenticated
     * @urlParam userId required Target user ID
     * @return JsonResponse Following status
     * 
     * @response 200 {"is_following": true}
     */
    public function isFollowing(Request $request, $userId)
    {
        $user = Auth::user();
        $followerId = (int)$user->id;
        
        $isFollowing = Relationship::where('follower_id', $followerId)
            ->where('followee_id', $userId)
            ->exists();

        Log::debug('Following status checked', [
            'follower_id' => $followerId,
            'followee_id' => $userId,
            'is_following' => $isFollowing
        ]);
        return response()->json([
            'is_following' => $isFollowing
        ], 200);
    }

    /**
     * Find nearby friends
     * 
     * Finds friends within a specified radius (in km)
     * 
     * @authenticated
     * @queryParam radius required Search radius in kilometers
     * @return JsonResponse List of nearby friends
     * 
     * @response [{"id": 2, "username": "nearby_friend", ...}]
     * @response 400 {"error": "Validation error"}
     * @response 400 {"error": "User location not set"}
     */
    public function findNearbyFriends(Request $request)
    {
        Log::debug('Nearby friends search initiated');
        $validator = Validator::make($request->all(), [
            'radius' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            Log::warning('Nearby friends validation failed', $validator->errors()->toArray());
            return response()->json(['error' => $validator->errors()], 400);
        }

        $currentUser = Auth::user();

        if (is_null($currentUser->latitude) || is_null($currentUser->longitude)) {
            Log::warning('Nearby friends failed: user location not set', ['user_id' => $currentUser->id]);
            return response()->json(['error' => 'User location not set'], 400);
        }

        $radiusKm = $request->query('radius');
        Log::info('Searching nearby friends', [
            'user_id' => $currentUser->id,
            'radius_km' => $radiusKm
        ]);

        $friends = User::select('users.*')
            ->join('relationships', function ($join) use ($currentUser) {
                $join->on('users.id', '=', 'relationships.follower_id')
                    ->where('relationships.followee_id', $currentUser->id);
            })
            ->selectRaw(
                '(6371 * ACOS(LEAST(GREATEST(
                    COS(RADIANS(?)) * 
                    COS(RADIANS(users.latitude)) * 
                    COS(RADIANS(users.longitude) - RADIANS(?)) + 
                    SIN(RADIANS(?)) * 
                    SIN(RADIANS(users.latitude)),
                -1), 1)
                )) AS distance_km',
                [
                    $currentUser->latitude,
                    $currentUser->longitude,
                    $currentUser->latitude
                ]
            )
            ->where('users.id', '!=', $currentUser->id)
            ->having('distance_km', '<', $radiusKm)
            ->orderBy('distance_km')
            ->get();

        Log::info('Nearby friends search completed', [
            'user_id' => $currentUser->id,
            'results' => count($friends)
        ]);
        return response()->json($friends);
    }
}