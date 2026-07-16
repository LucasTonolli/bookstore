<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfilePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ProfileController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(middleware: 'auth:sanctum'),
        ];
    }

    public function show(Request $request): JsonResponse
    {
        return response()->json(status: JsonResponse::HTTP_OK, data: [
            'message' => 'User found',
            'data' => UserResource::make($request->user()),
        ]);
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->update($request->validated());
        return response()->json(status: JsonResponse::HTTP_OK, data: [
            'message' => 'User updated successfully',
            'data' => UserResource::make($user->refresh()),
        ]);
    }

    public function updatePassword(UpdateProfilePasswordRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->update(['password' => $request->password]);
        return response()->json(status: JsonResponse::HTTP_OK, data: [
            'message' => 'Password updated successfully',
            'data' => UserResource::make($user->refresh()),
        ]);
    }
}
