<?php

namespace App\Http\Controllers\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::where('email', '=', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json(status: 401, data: [
                'message' => 'The provided credentials are incorrect.'
            ]);
        }

        return response()->json(status: Response::HTTP_OK, data: [
            'token' => $user->createToken('token')->plainTextToken,
            'data' => UserResource::make($user)
        ]);
    }
}
