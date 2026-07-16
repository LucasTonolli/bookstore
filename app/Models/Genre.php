<?php

namespace App\Models;

use Database\Factories\GenreFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

#[Fillable('name', 'slug')]
class Genre extends Model
{
    /** @use HasFactory<GenreFactory> */
    use HasFactory;

    public function books(): BelongsToMany
    {
        return $this->belongsToMany(Book::class);
    }

    protected static function booted(): void
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
        $countSameSlug = Genre::whereLike('slug', "$slug%")->where('id', '!=', $this->id)->count();
        if ($countSameSlug) {
            $slug .= '-'.$countSameSlug;
        }

        return $slug;
    }
}
