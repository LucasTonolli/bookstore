<?php

use App\Models\Author;
use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('returns a paginated list of books', function () {
    Book::factory()->count(3)->create();

    $response = $this->getJson('/api/v1/books');

    $response->assertSuccessful()
        ->assertJsonPath('message', 'Books listed successfully')
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('pagination.total', 3);
});

it('filters books by title', function () {
    Book::factory()->create(['title' => 'The Great Adventure']);
    Book::factory()->create(['title' => 'A Boring Tale']);

    $response = $this->getJson('/api/v1/books?title=Great');

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'The Great Adventure');
});

it('filters books by isbn', function () {
    Book::factory()->create(['isbn' => '1111111111111']);
    Book::factory()->create(['isbn' => '2222222222222']);

    $response = $this->getJson('/api/v1/books?isbn=1111111111111');

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.isbn', '1111111111111');
});

it('filters books by published_year', function () {
    Book::factory()->create(['published_year' => 2001]);
    Book::factory()->create(['published_year' => 1999]);

    $response = $this->getJson('/api/v1/books?published_year=2001');

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.published_year', 2001);
});

it('sorts books by created_at', function () {
    $older = Book::factory()->create(['created_at' => now()->subDay()]);
    $newer = Book::factory()->create(['created_at' => now()]);

    $response = $this->getJson('/api/v1/books?sort=created_at&direction=desc');

    $response->assertSuccessful()
        ->assertJsonPath('data.0.id', $newer->id)
        ->assertJsonPath('data.1.id', $older->id);
});

it('paginates books', function () {
    Book::factory()->count(5)->create();

    $response = $this->getJson('/api/v1/books?per_page=2&page=2');

    $response->assertSuccessful()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('pagination.current_page', 2)
        ->assertJsonPath('pagination.per_page', 2)
        ->assertJsonPath('pagination.total', 5);
});

it('rejects invalid filters', function () {
    $response = $this->getJson('/api/v1/books?sort=invalid_column&direction=sideways&per_page=0');

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['sort', 'direction', 'per_page']);
});

it('loads authors and genres relationships when listing books', function () {
    $book = Book::factory()->create();
    $author = Author::factory()->create();
    $genre = Genre::factory()->create();
    $book->authors()->attach($author);
    $book->genres()->attach($genre);

    $response = $this->getJson('/api/v1/books');

    $response->assertSuccessful()
        ->assertJsonPath('data.0.authors.0.id', $author->id)
        ->assertJsonPath('data.0.genres.0.id', $genre->id);
});

it('creates a new book with authors and genres', function () {
    Sanctum::actingAs(User::factory()->create(), ['book:create']);

    $author = Author::factory()->create();
    $genre = Genre::factory()->create();

    $payload = [
        'title' => 'Dom Casmurro',
        'subtitle' => 'A Classic',
        'published_year' => 1899,
        'isbn' => '1234567890123',
        'pages' => 256,
        'edition' => '1st',
        'publisher' => 'Livraria Garnier',
        'language' => 'pt',
        'description' => 'A Brazilian classic.',
        'authors' => [$author->id],
        'genres' => [$genre->id],
    ];

    $response = $this->postJson('/api/v1/books', $payload);

    $response->assertCreated()
        ->assertJsonPath('message', 'Book created successfully')
        ->assertJsonPath('data.title', 'Dom Casmurro')
        ->assertJsonPath('data.isbn', '1234567890123')
        ->assertJsonPath('data.authors.0.id', $author->id)
        ->assertJsonPath('data.genres.0.id', $genre->id);

    $this->assertDatabaseHas('books', ['title' => 'Dom Casmurro', 'isbn' => '1234567890123']);
    $this->assertDatabaseHas('author_book', ['author_id' => $author->id]);
    $this->assertDatabaseHas('book_genre', ['genre_id' => $genre->id]);
});

it('requires required fields when creating a book', function () {
    Sanctum::actingAs(User::factory()->create(), ['book:create']);

    $response = $this->postJson('/api/v1/books', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['title', 'isbn', 'pages', 'authors', 'genres']);
});

it('rejects a duplicate isbn when creating a book', function () {
    Sanctum::actingAs(User::factory()->create(), ['book:create']);
    Book::factory()->create(['isbn' => '1234567890123']);
    $author = Author::factory()->create();
    $genre = Genre::factory()->create();

    $response = $this->postJson('/api/v1/books', [
        'title' => 'Another Book',
        'isbn' => '1234567890123',
        'pages' => 100,
        'authors' => [$author->id],
        'genres' => [$genre->id],
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['isbn']);
});

it('rejects nonexistent author or genre ids when creating a book', function () {
    Sanctum::actingAs(User::factory()->create(), ['book:create']);

    $response = $this->postJson('/api/v1/books', [
        'title' => 'Another Book',
        'isbn' => '1234567890123',
        'pages' => 100,
        'authors' => [999],
        'genres' => [999],
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['authors', 'genres']);
});

it('rejects creating a book without authentication', function () {
    $author = Author::factory()->create();
    $genre = Genre::factory()->create();

    $response = $this->postJson('/api/v1/books', [
        'title' => 'Another Book',
        'isbn' => '1234567890123',
        'pages' => 100,
        'authors' => [$author->id],
        'genres' => [$genre->id],
    ]);

    $response->assertUnauthorized();
});

it('rejects creating a book without the book:create ability', function () {
    Sanctum::actingAs(User::factory()->create(), ['book:update']);
    $author = Author::factory()->create();
    $genre = Genre::factory()->create();

    $response = $this->postJson('/api/v1/books', [
        'title' => 'Another Book',
        'isbn' => '1234567890123',
        'pages' => 100,
        'authors' => [$author->id],
        'genres' => [$genre->id],
    ]);

    $response->assertForbidden();
});

it('returns the specified book', function () {
    $book = Book::factory()->create();
    $author = Author::factory()->create();
    $book->authors()->attach($author);

    $response = $this->getJson("/api/v1/books/{$book->id}");

    $response->assertSuccessful()
        ->assertJsonPath('message', 'Book found')
        ->assertJsonPath('data.id', $book->id)
        ->assertJsonPath('data.title', $book->title)
        ->assertJsonPath('data.authors.0.id', $author->id);
});

it('returns not found when showing a missing book', function () {
    $response = $this->getJson('/api/v1/books/999');

    $response->assertNotFound();
});

it('modifies the specified book', function () {
    Sanctum::actingAs(User::factory()->create(), ['book:update']);

    $book = Book::factory()->create();

    $response = $this->putJson("/api/v1/books/{$book->id}", [
        'title' => 'Updated Title',
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('message', 'Book updated successfully')
        ->assertJsonPath('data.title', 'Updated Title');

    $this->assertDatabaseHas('books', ['id' => $book->id, 'title' => 'Updated Title']);
});

it('allows keeping a book\'s own unchanged isbn on update', function () {
    Sanctum::actingAs(User::factory()->create(), ['book:update']);

    $book = Book::factory()->create(['isbn' => '1234567890123']);

    $response = $this->putJson("/api/v1/books/{$book->id}", [
        'isbn' => '1234567890123',
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('data.isbn', '1234567890123');
});

it('rejects updating a book to another book\'s isbn', function () {
    Sanctum::actingAs(User::factory()->create(), ['book:update']);
    Book::factory()->create(['isbn' => '1111111111111']);
    $book = Book::factory()->create(['isbn' => '2222222222222']);

    $response = $this->putJson("/api/v1/books/{$book->id}", [
        'isbn' => '1111111111111',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['isbn']);
});

it('syncs authors and genres when updating a book', function () {
    Sanctum::actingAs(User::factory()->create(), ['book:update']);

    $book = Book::factory()->create();
    $oldAuthor = Author::factory()->create();
    $newAuthor = Author::factory()->create();
    $book->authors()->attach($oldAuthor);

    $response = $this->putJson("/api/v1/books/{$book->id}", [
        'authors' => [$newAuthor->id],
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('data.authors.0.id', $newAuthor->id)
        ->assertJsonCount(1, 'data.authors');

    $this->assertDatabaseMissing('author_book', ['author_id' => $oldAuthor->id, 'book_id' => $book->id]);
    $this->assertDatabaseHas('author_book', ['author_id' => $newAuthor->id, 'book_id' => $book->id]);
});

it('returns not found when updating a missing book', function () {
    Sanctum::actingAs(User::factory()->create(), ['book:update']);

    $response = $this->putJson('/api/v1/books/999', ['title' => 'Updated Title']);

    $response->assertNotFound();
});

it('rejects updating a book without authentication', function () {
    $book = Book::factory()->create();

    $response = $this->putJson("/api/v1/books/{$book->id}", ['title' => 'Updated Title']);

    $response->assertUnauthorized();
});

it('rejects updating a book without the book:update ability', function () {
    Sanctum::actingAs(User::factory()->create(), ['book:create']);
    $book = Book::factory()->create();

    $response = $this->putJson("/api/v1/books/{$book->id}", ['title' => 'Updated Title']);

    $response->assertForbidden();
});

it('deletes the specified book', function () {
    Sanctum::actingAs(User::factory()->create(), ['book:delete']);

    $book = Book::factory()->create();

    $response = $this->deleteJson("/api/v1/books/{$book->id}");

    $response->assertNoContent();

    $this->assertDatabaseMissing('books', ['id' => $book->id]);
});

it('returns not found when deleting a missing book', function () {
    Sanctum::actingAs(User::factory()->create(), ['book:delete']);

    $response = $this->deleteJson('/api/v1/books/999');

    $response->assertNotFound();
});

it('rejects deleting a book without authentication', function () {
    $book = Book::factory()->create();

    $response = $this->deleteJson("/api/v1/books/{$book->id}");

    $response->assertUnauthorized();
});

it('rejects deleting a book without the book:delete ability', function () {
    Sanctum::actingAs(User::factory()->create(), ['book:create']);
    $book = Book::factory()->create();

    $response = $this->deleteJson("/api/v1/books/{$book->id}");

    $response->assertForbidden();
});
