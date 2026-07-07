<?php

namespace App\Actions\Books;

use App\Models\Book;
use Illuminate\Support\Facades\DB;

class CreateBookAction
{
    public function handle(array $data): Book
    {
        return DB::transaction(function () use ($data) {
            $book = Book::create([
                'title' => $data['title'],
                'subtitle' => $data['subtitle'] ?? null,
                'published_year' => $data['published_year'] ?? null,
                'isbn' => $data['isbn'] ?? null,
                'pages' => $data['pages'] ?? null,
                'edition' => $data['edition'] ?? null,
                'publisher' => $data['publisher'] ?? null,
                'language' => $data['language'] ?? null,
                'description' => $data['description'] ?? null,
            ]);

            $book->authors()->attach($data['authors'] ?? []);
            $book->genres()->attach($data['genres'] ?? []);

            return $book;
        });
    }
}
