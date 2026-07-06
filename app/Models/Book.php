<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

#[Fillable([
    'title',
    'subtitle',
    'published_year',
    'isbn',
    'pages',
    'edition',
    'publisher',
    'language',
    'description'
])]
class Book extends Model
{
    /** @use HasFactory<\Database\Factories\BookFactory> */
    use HasFactory;

    public function authors(): BelongsToMany
    {
        return $this->belongsToMany(Author::class);
    }

    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class);
    }

    protected static function booted(): void
    {
        static::creating(function (Book $book) {
            $book->slug = $this->generateSlug($book);
        });

        static::updating(function (Book $book) {
            $book->slug = $this->generateSlug($book);
        });
    }

    private function generateSlug(Book $book): string
    {
        $baseSlug = Str::slug("{$book->title} {$book->subtitle} {$book->published_year}");

        $sameSlugCount = Book::whereLike('slug', "$baseSlug%")->count();

        if ($sameSlugCount) {
            $baseSlug .= '-' . $sameSlugCount;
        }


        return $baseSlug;
    }
}
