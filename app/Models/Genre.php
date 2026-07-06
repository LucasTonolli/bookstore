<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Stringable;

#[Fillable('name', 'slug')]
class Genre extends Model
{
    /** @use HasFactory<\Database\Factories\GenreFactory> */
    use HasFactory;

    protected static function boot(): void
    {
        static::creating(function (Genre $genre) {
            $genre->slug = $genre->generateSlug($genre->name);
        });

        static::updating(function (Genre $genre) {
            if ($genre->isDirty('name')) {
                $genre->slug = $genre->generateSlug($genre->name);
            }
        });
    }

    private function generateSlug(string $name): string
    {
        $slug = Str::slug($name);
        $countSameSlug = Genre::whereLike('slug', "$slug%")->count();
        if ($countSameSlug) {
            $slug .= '-' . $countSameSlug;
        }
        return $slug;
    }
}
