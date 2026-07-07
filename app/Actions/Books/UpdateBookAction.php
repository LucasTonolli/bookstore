<?php

namespace App\Actions\Books;

use App\Models\Book;
use Illuminate\Support\Facades\DB;

class UpdateBookAction
{
    public function handle(Book $book, array $data): Book
    {
        return DB::transaction(function () use ($book, $data) {
            /**
             * Using $data directly in the update method will only update the fields that are present in the $data array.
             */
            $book->update($data);

            if (isset($data['authors'])) {
                $book->authors()->sync($data['authors']);
            }

            if (isset($data['genres'])) {
                $book->genres()->sync($data['genres']);
            }

            $book->load(['authors', 'genres']);

            return $book;
        });
    }
}
