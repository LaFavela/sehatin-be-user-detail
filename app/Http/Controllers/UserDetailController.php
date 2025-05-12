<?php

namespace App\Http\Controllers;

use app\Http\Requests\UserDetailGetRequest;
use App\Http\Requests\UserDetailRequest;
use App\Http\Resources\MessageResource;
use App\Http\Resources\UserDetailResource;
use App\Models\UserDetail;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;


class UserDetailController
{

    /**
     * Display a listing of the resource.
     */
    public function index(UserDetailGetRequest $request): JsonResponse
    {
        try {
            $validatedData = $request->validated();

            $role = $request->header('X-User-Role');
            $userId = $request->header('X-User-ID');

            if ($role != 'admin') {
                $users = UserDetail::where('user_id', $userId)->get();
                return (new MessageResource(UserDetailResource::collection($users), true, 'UserDetail data found'))->response();
            }

            $query = UserDetail::query();

            if (isset($validatedData['id'])) {
                $query->where('id', 'like', '%' . $validatedData['id'] . '%');
            }

            if (isset($validatedData['user_id'])) {
                $query->where('user_id', 'like', '%' . $validatedData['email'] . '%');
            }

            $sortBy = $validatedData['sort_by'] ?? 'created_at';
            $sortDirection = $validatedData['sort_direction'] ?? 'desc';

            $query->orderBy($sortBy, $sortDirection);

            if (isset($validatedData['per_page'])) {
                $users = $query->paginate($validatedData['per_page']);
                $users->appends($validatedData);
            } else {
                $users = $query->get();
            }
            if ($users->isEmpty()) {
                return (new MessageResource(null, false, 'Data not found'))->response()->setStatusCode(404);
            }

            if ($users->isEmpty()) {
                return (new MessageResource(null, false, 'Data not found'))->response()->setStatusCode(404);
            }
        } catch (\Exception $e) {
            return (new MessageResource(null, false, 'Failed to get users', $e->getMessage()))->response()->setStatusCode(500);
        }
        return (new MessageResource(UserDetailResource::collection($users), true, 'UserDetail data found'))->response();
    }

    /**
     * Display the specified resource.
     */
    public function show(UserDetailGetRequest $request, $id): JsonResponse
    {
        try {
            $role = $request->header('X-User-Role');
            $userId = $request->header('X-User-ID');

            if ($role == 'admin') {
                $user = UserDetail::find($id);
            } else {
                $user = UserDetail::where('user_id', $userId);
            }

            if (!$user) {
                return (new MessageResource(null, false, 'Data not found'))->response()->setStatusCode(404);
            }
        } catch (\Exception $e) {
            return (new MessageResource(null, false, 'Failed to get user', $e->getMessage()))->response()->setStatusCode(500);
        }
        return (new MessageResource(new UserDetailResource($user), true, 'UserDetail data found'))->response();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(UserDetailRequest $request): JsonResponse
    {
        if (isset($request->validator) && $request->validator->fails()) {
            return (new MessageResource(null, false, 'Validation failed', $request->validator->messages()))->response()->setStatusCode(400);
        }

        try {
            $role = $request->header('X-User-Role');
            $userId = $request->header('X-User-ID');
            $validated = $request->validated();

            if ($role != 'admin' && $userId != $validated['user_id']) {
                return (new MessageResource(null, false, 'Validation failed', 'field: [user_id] doesn\'t match session User ID'))->response()->setStatusCode(400);
            }

            $response = Http::withHeaders([
                'X-User-Role' => $role,
                'X-User-ID' => $userId,
            ])->get("http://user-service.default.svc.cluster.local:8000/api/users/" . $userId);

            if ($response->notFound()) {
                return (new MessageResource(null, false, 'User not found'))->response()->setStatusCode(404);
            }

            if ($response->serverError()) {
                return (new MessageResource(null, false, 'Failed to reset password'))->response()->setStatusCode(500);
            }

            $array = json_decode($response->body(), true);
            $data = $array['data'];

            if(!isset($data['email_verified_at'])) {
                return (new MessageResource(null, false, 'User email not verified'))->response()->setStatusCode(400);
            }

            $user = UserDetail::create($validated);
        } catch (\Exception $e) {
            return (new MessageResource(null, false, 'Failed to create user', $e->getMessage()))->response()->setStatusCode(500);
        }
        return (new MessageResource(new UserDetailResource($user), true, 'UserDetail created successfully'))->response();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UserDetailRequest $request, $id): JsonResponse
    {
        if (isset($request->validator) && $request->validator->fails()) {
            return (new MessageResource(null, false, 'Validation failed', $request->validator->messages()))->response()->setStatusCode(400);
        }


        try {
            $user = UserDetail::find($id);

            $role = $request->header('X-User-Role');
            $userId = $request->header('X-User-ID');
            if ($role != 'admin' && $userId != $user->user_id) {
                return (new MessageResource(null, false, 'Data not found'))->response()->setStatusCode(404);
            }

            if (!$user) {
                return (new MessageResource(null, false, 'Data not found'))->response()->setStatusCode(404);
            }
            $validated = $request->validated();
            $user->update($validated);
        } catch (\Exception $e) {
            return (new MessageResource(null, false, 'Failed to update user', $e->getMessage()))->response()->setStatusCode(500);
        }
        return (new MessageResource(new UserDetailResource($user), true, 'UserDetail updated successfully'))->response();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UserDetailRequest $request, $id): JsonResponse
    {
        $user = UserDetail::find($id);

        $role = $request->header('X-User-Role');
        $userId = $request->header('X-User-ID');

        if ($role != 'admin' && $userId != $user->user_id) {
            return (new MessageResource(null, false, 'Data not found'))->response()->setStatusCode(404);
        }

        if (!$user) {
            return (new MessageResource(null, false, 'Data not found'))->response()->setStatusCode(404);
        }

        try {
            $user->delete();
        } catch (\Exception $e) {
            return (new MessageResource(null, false, 'Failed to delete user', $e->getMessage()))->response()->setStatusCode(500);
        }
        return (new MessageResource(new UserDetailResource($user), true, 'UserDetail deleted successfully'))->response();
    }
}
