<?php

namespace App\Http\Controllers\V1;

use App\Actions\Books\CreateBookAction;
use App\Actions\Books\UpdateBookAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ListBooksRequest;
use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Http\Resources\BookCollection;
use App\Http\Resources\BookResource;
use App\Models\Book;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;

class BookController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(ListBooksRequest $request): JsonResponse
    {
        $books = Book::query();

        $filters = $request->validated();

        $results = $books
            ->when(isset($filters['title']), fn($query) => $query->where('title', 'like', "%{$filters['title']}%"))
            ->when(isset($filters['subtitle']), fn($query) => $query->where('subtitle', 'like', "%{$filters['subtitle']}%"))
            ->when(isset($filters['published_year']), fn($query) => $query->where('published_year', $filters['published_year']))
            ->when(isset($filters['isbn']), fn($query) => $query->where('isbn', 'like', "%{$filters['isbn']}%"))
            ->when(isset($filters['pages']), fn($query) => $query->where('pages', $filters['pages']))
            ->when(isset($filters['edition']), fn($query) => $query->where('edition', 'like', "%{$filters['edition']}%"))
            ->when(isset($filters['publisher']), fn($query) => $query->where('publisher', 'like', "%{$filters['publisher']}%"))
            ->when(isset($filters['language']), fn($query) => $query->where('language', 'like', "%{$filters['language']}%"))
            ->when(isset($filters['description']), fn($query) => $query->where('description', 'like', "%{$filters['description']}%"))
            ->when(isset($filters['sort']), fn($query) => $query->orderBy($filters['sort'], $filters['direction'] ?? 'asc'))
            ->with(['authors', 'genres'])
            ->paginate($filters['per_page'] ?? 15, ['*'], 'page', $filters['page'] ?? 1);

        return response()->json(status: Response::HTTP_OK, data: BookCollection::make($results));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBookRequest $request, CreateBookAction $action): JsonResponse
    {
        try {
            $book = $action->handle($request->validated());
        } catch (\Exception $e) {
            return response()->json(status: Response::HTTP_INTERNAL_SERVER_ERROR, data: [
                'message' => 'Failed to create book',
                'error' => $e->getMessage(),
            ]);
        }
        return response()->json(status: Response::HTTP_CREATED, data: [
            'message' => 'Book created successfully',
            'data' => BookResource::make($book),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Book $book): JsonResponse
    {
        $book->load(['authors', 'genres']);

        return response()->json(status: Response::HTTP_OK, data: [
            'message' => 'Book found',
            'data' => BookResource::make($book),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBookRequest $request, Book $book, UpdateBookAction $action): JsonResponse
    {
        try {
            $book = $action->handle($book, $request->validated());
        } catch (\Exception $e) {
            return response()->json(status: Response::HTTP_INTERNAL_SERVER_ERROR, data: [
                'message' => 'Failed to update book',
                'error' => $e->getMessage(),
            ]);
        }
        return response()->json(status: Response::HTTP_OK, data: [
            'message' => 'Book updated successfully',
            'data' => BookResource::make($book),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Book $book): JsonResponse
    {
        $book->delete();
        return response()->json(status: Response::HTTP_NO_CONTENT, data: [
            'message' => 'Book deleted successfully',
            'data' => $book,
        ]);
    }
}
