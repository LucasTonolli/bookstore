<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListAuthorsRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AuthorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(ListAuthorsRequest $request): JsonResponse
    {
        $filters = $request->validated();
        return response()->json(status: Response::HTTP_OK, data: [
            'message' => 'List of authors',
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        return response()->json(status: Response::HTTP_CREATED, data: [
            'message' => 'Author created successfully',
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        return response()->json(status: Response::HTTP_OK, data: [
            'message' => 'Author details',
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        return response(status: Response::HTTP_OK)->json(status: Response::HTTP_OK, data: [
            'message' => 'Author updated successfully',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        return response()->json(status: Response::HTTP_NO_CONTENT);
    }
}
