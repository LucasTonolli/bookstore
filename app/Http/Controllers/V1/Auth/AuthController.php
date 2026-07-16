<?php

namespace App\Http\Controllers\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $validated = $request->validated();

        if (! Auth::attempt($validated)) {
            return response()->json(status: Response::HTTP_UNAUTHORIZED, data: [
                'message' => 'The provided credentials are incorrect.',
            ]);
        }

        $user = Auth::user();

        return response()->json(status: Response::HTTP_OK, data: [
            'token' => $user->createToken('token', $user->permissions())->plainTextToken,
            'data' => UserResource::make($user),
        ]);
    }

    public function logout(Request $request): Response
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }
}
