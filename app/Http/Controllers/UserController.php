<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

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
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->authorize('viewAny', User::class);
        $users = User::withCount('assessments')->get();

        return UserResource::collection($users);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        return new UserResource($user);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {


    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $user_id
     * @return \Illuminate\Http\Response
     */
    public function update(\App\Http\Requests\UpdateUserRequest $request, User $user)
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
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
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
     * @param Request $request
     * @return boolean
     */
    public function changePassword(\App\Http\Requests\ChangePasswordRequest $request)
    {
        $user = User::find($request['user_id']);
        if (! $user) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $this->authorize('update', $user);

        // allow self-service or admin override
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
     * @param Request $request
     * @return boolean
     */
    public function adminChangePassword(\App\Http\Requests\AdminChangePasswordRequest $request)
    {
        $admin = auth('web')->user();
        if (! $admin || ! $admin->isAdmin()) {
            abort(403, 'Forbidden');
        }

        $user = User::find($request['user_id']);
        if (! $user) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $user->password = Hash::make($request['new_password']);
        $user->save();
        return response()->json(['status' => 'ok']);
    }


    /**
     * admin registers user
     *
     * @param Request $request
     * @return boolean
     */
    public function registerUser(\App\Http\Requests\RegisterUserRequest $request)
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
