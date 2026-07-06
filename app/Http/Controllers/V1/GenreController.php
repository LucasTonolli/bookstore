<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\Genre;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class GenreController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        return response()->json(status: Response::HTTP_OK, data: [
            'message' => 'List of genres',
            'data' => Genre::all(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        return response()->json(status: Response::HTTP_CREATED, data: [
            'message' => 'Genre created successfully',
            'data' => Genre::create($request->all()),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Genre $genre): JsonResponse
    {
        return response()->json(status: Response::HTTP_OK, data: [
            'message' => 'Genre found',
            'data' => $genre,
        ]);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Genre $genre): JsonResponse
    {
        $genre->update($request->all());
        return response()->json(status: Response::HTTP_OK, data: [
            'message' => 'Genre updated successfully',
            'data' => $genre,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Genre $genre): JsonResponse
    {
        $genre->delete();
        return response()->json(status: Response::HTTP_NO_CONTENT);
    }
}
