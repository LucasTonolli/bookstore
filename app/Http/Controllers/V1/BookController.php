<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;

class BookController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        return response()->json(status: Response::HTTP_OK, data: [
            'message' => 'Books retrieved successfully',
            'data' => Book::all(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        return response()->json(status: Response::HTTP_CREATED, data: [
            'message' => 'Book created successfully',
            'data' => Book::create($request->all()),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Book $book): JsonResponse
    {
        return response()->json(status: Response::HTTP_OK, data: [
            'message' => 'Book found',
            'data' => $book,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Book $book): JsonResponse
    {
        $book->update($request->all());
        return response()->json(status: Response::HTTP_OK, data: [
            'message' => 'Book updated successfully',
            'data' => $book,
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
