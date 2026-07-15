<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListUsersRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserCollection;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class UserController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(middleware: 'auth:sanctum'),
            new Middleware(middleware: ['abilities:user:create'], only: ['store']),
            new Middleware(middleware: ['abilities:user:read'], only: ['index', 'show']),
            new Middleware(middleware: ['abilities:user:update'], only: ['update']),
            new Middleware(middleware: ['abilities:user:delete'], only: ['destroy']),
        ];
    }
    /**
     * Display a listing of the resource.
     */
    public function index(ListUsersRequest $request): JsonResponse
    {
        $filters = $request->validated();

        $results = User::query()
            ->when(isset($filters['name']), fn($query) => $query->where('name', 'like', "%{$filters['name']}%"))
            ->when(isset($filters['email']), fn($query) => $query->where('email', 'like', "%{$filters['email']}%"))
            ->when(isset($filters['role']), fn($query) => $query->where('role', 'like', "%{$filters['role']}%"))
            ->when(isset($filters['sort']), fn($query) => $query->orderBy($filters['sort'], $filters['direction'] ?? 'asc'))
            ->paginate($filters['per_page'] ?? 15, ['*'], 'page', $filters['page'] ?? 1);
        return response()->json(status: JsonResponse::HTTP_OK, data: UserCollection::make($results));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = User::create($request->validated());
        return response()->json(status: JsonResponse::HTTP_CREATED, data: [
            'message' => 'User created successfully',
            'data' => UserResource::make($user),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user): JsonResponse
    {
        return response()->json(status: JsonResponse::HTTP_OK, data: [
            'message' => 'User found',
            'data' => UserResource::make($user),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $user->update($request->validated());
        return response()->json(status: JsonResponse::HTTP_OK, data: [
            'message' => 'User updated successfully',
            'data' => UserResource::make($user),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user): Response
    {
        $user->delete();
        return response()->noContent();
    }
}
