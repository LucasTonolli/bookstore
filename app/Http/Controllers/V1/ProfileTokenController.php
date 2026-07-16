<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class ProfileTokenController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(status: JsonResponse::HTTP_OK, data: [
            'message' => 'Tokens found',
            'data' => $request->user('sanctum')->tokens()->get(),
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse|Response
    {
        $user  = $request->user();
        $token = $user->tokens()->find($id);
        if (!$token) {
            return response()->json(status: JsonResponse::HTTP_NOT_FOUND, data: [
                'message' => 'Token not found',
            ]);
        }

        if ($token->id === $user->currentAccessToken()->id) {
            return response()->json(status: JsonResponse::HTTP_BAD_REQUEST, data: [
                'message' => 'Cannot delete current token',
            ]);
        }

        $token->delete();

        return response()->noContent();
    }

    public function destroyAll(Request $request): Response
    {
        $user = $request->user();

        $tokens = $user->tokens()->get();

        foreach ($tokens as $token) {

            if ($user->currentAccessToken()->id !== $token->id) {
                $token->delete();
            }
        }

        return response()->noContent();
    }
}
