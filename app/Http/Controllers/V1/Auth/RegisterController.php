<?php

namespace App\Http\Controllers\V1\Auth;

use App\Enums\Roles;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RegisterController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();
        [$user, $token] = DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'role' => Roles::Client
            ]);

            return [
                $user,
                $user->createToken('token', $user->permissions())
            ];
        });

        return response()->json(status: Response::HTTP_CREATED, data: [
            'message' => 'User created successfully',
            'token'  => $token->plainTextToken,
            'data'  => UserResource::make($user)
        ]);
    }
}
