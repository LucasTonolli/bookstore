<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListGenresRequest;
use App\Http\Requests\SaveGenreRequest;
use App\Http\Resources\GenreCollection;
use App\Http\Resources\GenreResource;
use App\Models\Genre;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class GenreController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(ListGenresRequest $request): JsonResponse
    {
        $filters = $request->validated();

        $results = Genre::query()
            ->when(isset($filters['name']), fn($query) => $query->where('name', 'like', "%{$filters['name']}%"))
            ->when(isset($filters['sort']), fn($query) => $query->orderBy($filters['sort'], $filters['direction'] ?? 'asc'))
            ->paginate($filters['per_page'] ?? 15, ['*'], 'page', $filters['page'] ?? 1);

        return response()->json(status: Response::HTTP_OK, data: GenreCollection::make($results));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SaveGenreRequest $request)
    {
        $genre = Genre::create($request->validated());

        return response()->json(status: Response::HTTP_CREATED, data: [
            'message' => 'Genre created successfully',
            'data' => GenreResource::make($genre),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Genre $genre): JsonResponse
    {
        return response()->json(status: Response::HTTP_OK, data: [
            'message' => 'Genre found',
            'data' => GenreResource::make($genre),
        ]);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(SaveGenreRequest $request, Genre $genre): JsonResponse
    {
        $genre->update($request->validated());
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
