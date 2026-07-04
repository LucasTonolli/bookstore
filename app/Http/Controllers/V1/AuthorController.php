<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListAuthorsRequest;
use App\Http\Requests\SaveAuthorRequest;
use App\Http\Resources\AuthorCollection;
use App\Http\Resources\AuthorResource;
use App\Models\Author;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class AuthorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(ListAuthorsRequest $request): JsonResponse
    {
        $filters = $request->validated();

        $results = Author::query()
            ->when(isset($filters['name']), fn($query) => $query->where('name', 'like', "%{$filters['name']}%"))
            ->when(isset($filters['last_name']), fn($query) => $query->where('last_name', 'like', "%{$filters['last_name']}%"))
            ->when(isset($filters['nacionality']), fn($query) => $query->where('nacionality', 'like', "%{$filters['nacionality']}%"))
            ->when(isset($filters['birth_date']), fn($query) => $query->whereDate('birth_date', $filters['birth_date']))
            ->when(isset($filters['sort']), fn($query) => $query->orderBy($filters['sort']))
            ->paginate($filters['per_page'] ?? 15, ['*'], 'page', $filters['page'] ?? 1);

        return response()->json(status: Response::HTTP_OK, data: AuthorCollection::make($results));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SaveAuthorRequest $request): JsonResponse
    {
        $result = Author::create($request->validated());
        return response()->json(status: Response::HTTP_CREATED, data: [
            'message' => 'Author created successfully',
            'data' => AuthorResource::make($result),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Author $author): JsonResponse
    {
        return response()->json(status: Response::HTTP_OK, data: [
            'message' => 'Author details',
            'data' => AuthorResource::make($author),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(SaveAuthorRequest $request, Author $author): JsonResponse
    {
        $author->update($request->validated());

        return response()->json(status: Response::HTTP_OK, data: [
            'message' => 'Author updated successfully',
            'data' => AuthorResource::make($author),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Author $author): JsonResponse
    {
        $author->delete();

        return response()->json(status: Response::HTTP_NO_CONTENT);
    }
}
