<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class EmailVerificationController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(middleware: 'auth:sanctum'),
        ];
    }

    public function send(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->hasVerifiedEmail()) {
            return response()->json(status: JsonResponse::HTTP_OK, data: [
                'message' => 'Email already verified',
            ]);
        }
        $request->user()->sendEmailVerificationNotification();

        return response()->json(status: JsonResponse::HTTP_OK, data: [
            'message' => 'Verification link sent successfully',
        ]);
    }

    public function verify(EmailVerificationRequest $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json(status: JsonResponse::HTTP_OK, data: [
                'message' => 'Email already verified',
            ]);
        }

        $request->fulfill();

        return response()->json(status: JsonResponse::HTTP_OK, data: [
            'message' => 'Email verified successfully',
        ]);
    }
}
