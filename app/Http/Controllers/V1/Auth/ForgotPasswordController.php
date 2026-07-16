<?php

namespace App\Http\Controllers\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ResetPasswordRequest;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    public function forgotPassword(Request $request): JsonResponse
    {
        $validated = $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink(
            $validated
        );

        return match ($status) {
            Password::RESET_LINK_SENT, Password::INVALID_USER => response()->json(status: JsonResponse::HTTP_OK, data: [
                'message' => 'If email exists, reset link sent successfully',
            ]),
            default => response()->json(status: JsonResponse::HTTP_INTERNAL_SERVER_ERROR, data: [
                'message' => 'Failed to send reset link',
            ])
        };
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $status = Password::reset(
            $validated,
            function ($user) use ($validated) {
                $user->update(['password' => Hash::make($validated['password'])]);
                event(new PasswordReset($user));
            }
        );

        return match ($status) {
            Password::PASSWORD_RESET => response()->json(status: JsonResponse::HTTP_OK, data: [
                'message' => 'Password reset successfully',
            ]),
            Password::INVALID_TOKEN => response()->json(status: JsonResponse::HTTP_UNAUTHORIZED, data: [
                'message' => 'Invalid token',
            ]),
            default => response()->json(status: JsonResponse::HTTP_INTERNAL_SERVER_ERROR, data: [
                'message' => 'Failed to reset password',
            ])
        };
    }
}
