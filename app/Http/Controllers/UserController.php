<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Hash;

/**
 * Admin user management plus self-service password updates.
 */
class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:web');
        $this->authorizeResource(User::class, 'user');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', User::class);
        $users = User::withCount('assessments')->get();

        return UserResource::collection($users);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return void
     */
    public function create(): void
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return void
     */
    public function store(Request $request): void
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Models\User $user
     * @return \App\Http\Resources\UserResource
     */
    public function show(User $user): UserResource
    {
        return new UserResource($user);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return void
     */
    public function edit(int $id): void
    {


    }

    /**
     * Update the specified resource in storage.
     *
     * @param \App\Http\Requests\UpdateUserRequest $request
     * @param \App\Models\User $user
     * @return \App\Http\Resources\UserResource
     */
    public function update(\App\Http\Requests\UpdateUserRequest $request, User $user): UserResource
    {
        $user->role = $request->input('role', $user->role);
        $user->name = $request->input('name', $user->name);
        $user->username = $request->input('username', $user->username);
        $user->email = $request->input('email', $user->email);
        $user->company = $request->input('company', $user->company);
        $user->save();
        return new \App\Http\Resources\UserResource($user);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\User $user
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function destroy(User $user): Response|JsonResponse
    {
        if ($user->assessments()->exists()) {
            return response()->json([
                'message' => 'Cannot delete users with assessments. Reassign or delete their assessments first.',
            ], 409);
        }

        $user->delete();
        return response()->noContent();

    }

    /**
     * change password by user
     *
     * @param \App\Http\Requests\ChangePasswordRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changePassword(\App\Http\Requests\ChangePasswordRequest $request): JsonResponse
    {
        $userId = (int) $request['user_id'];
        $user = User::find($userId);
        if (! $user) {
            return $this->errorResponse('not_found', null, 404);
        }

        $this->authorize('update', $user);

        // Allow self-service or admin override.
        $isAdmin = auth('web')->user()?->isAdmin();
        if ($isAdmin || Hash::check($request->input('old_password'), $user->password)) {
            $user->password = Hash::make($request->input('new_password'));
            $user->save();
            return response()->json(['status' => 'ok']);
        }

        return response()->json(['status' => 'invalid_old_password'], 422);
    }

    /**
     * change password by admin
     *
     * @param \App\Http\Requests\AdminChangePasswordRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminChangePassword(\App\Http\Requests\AdminChangePasswordRequest $request): JsonResponse
    {
        $admin = auth('web')->user();
        if (! $admin || ! $admin->isAdmin()) {
            abort(403, 'Forbidden');
        }

        $userId = (int) $request['user_id'];
        $user = User::find($userId);
        if (! $user) {
            return $this->errorResponse('not_found', null, 404);
        }

        $user->password = Hash::make($request['new_password']);
        $user->save();
        return response()->json(['status' => 'ok']);
    }


    /**
     * admin registers user
     *
     * @param \App\Http\Requests\RegisterUserRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function registerUser(\App\Http\Requests\RegisterUserRequest $request): JsonResponse
    {
        $this->authorize('create', User::class);
        $user = new User();
        // Accept either legacy displayName or new name field; fall back to username
        $user->name = $request['displayName'] ?? $request['name'] ?? $request['username'];
        $user->username = $request['username'];
        $user->email = $request['email'];
        $user->password = Hash::make($request['password']);
        $user->role = $request['role'] ?? 'editor';
        $user->save();
        return response()->json(['status' => 'ok', 'id' => $user->id], 201);
    }

}
