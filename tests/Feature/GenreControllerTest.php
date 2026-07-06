<?php

use App\Models\Genre;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns a paginated list of genres', function () {
    Genre::factory()->count(3)->create();

    $response = $this->getJson('/api/v1/genres');

    $response->assertSuccessful()
        ->assertJsonPath('message', 'Genres listed successfully')
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('pagination.total', 3);
});

it('filters genres by name', function () {
    Genre::factory()->create(['name' => 'Fantasy']);
    Genre::factory()->create(['name' => 'Horror']);

    $response = $this->getJson('/api/v1/genres?name=Fant');

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Fantasy');
});

it('sorts genres', function () {
    Genre::factory()->create(['name' => 'Zeta']);
    Genre::factory()->create(['name' => 'Alpha']);

    $response = $this->getJson('/api/v1/genres?sort=name&direction=desc');

    $response->assertSuccessful()
        ->assertJsonPath('data.0.name', 'Zeta')
        ->assertJsonPath('data.1.name', 'Alpha');
});

it('paginates genres', function () {
    Genre::factory()->count(5)->create();

    $response = $this->getJson('/api/v1/genres?per_page=2&page=2');

    $response->assertSuccessful()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('pagination.current_page', 2)
        ->assertJsonPath('pagination.per_page', 2)
        ->assertJsonPath('pagination.total', 5);
});

it('rejects invalid filters', function () {
    $response = $this->getJson('/api/v1/genres?sort=invalid_column&direction=sideways&per_page=0');

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['sort', 'direction', 'per_page']);
});

it('creates a new genre and generates a slug', function () {
    $response = $this->postJson('/api/v1/genres', ['name' => 'Fantasy']);

    $response->assertCreated()
        ->assertJsonPath('message', 'Genre created successfully')
        ->assertJsonPath('data.name', 'Fantasy')
        ->assertJsonPath('data.slug', 'fantasy');

    $this->assertDatabaseHas('genres', [
        'name' => 'Fantasy',
        'slug' => 'fantasy',
    ]);
});

it('generates a unique slug when the name is already used', function () {
    Genre::factory()->create(['name' => 'Fantasy', 'slug' => 'fantasy']);

    $response = $this->postJson('/api/v1/genres', ['name' => 'Fantasy']);

    $response->assertCreated()
        ->assertJsonPath('data.slug', 'fantasy-1');
});

it('requires a name when creating a genre', function () {
    $response = $this->postJson('/api/v1/genres', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});

it('returns the specified genre', function () {
    $genre = Genre::factory()->create();

    $response = $this->getJson("/api/v1/genres/{$genre->id}");

    $response->assertSuccessful()
        ->assertJsonPath('message', 'Genre found')
        ->assertJsonPath('data.id', $genre->id)
        ->assertJsonPath('data.name', $genre->name);
});

it('returns not found when showing a missing genre', function () {
    $response = $this->getJson('/api/v1/genres/999');

    $response->assertNotFound();
});

it('modifies the specified genre and regenerates the slug', function () {
    $genre = Genre::factory()->create(['name' => 'Fantasy', 'slug' => 'fantasy']);

    $response = $this->putJson("/api/v1/genres/{$genre->id}", ['name' => 'Horror']);

    $response->assertSuccessful()
        ->assertJsonPath('message', 'Genre updated successfully')
        ->assertJsonPath('data.name', 'Horror')
        ->assertJsonPath('data.slug', 'horror');

    $this->assertDatabaseHas('genres', [
        'id' => $genre->id,
        'name' => 'Horror',
        'slug' => 'horror',
    ]);
});

it('requires a name when updating a genre', function () {
    $genre = Genre::factory()->create();

    $response = $this->putJson("/api/v1/genres/{$genre->id}", []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});

it('returns not found when updating a missing genre', function () {
    $response = $this->putJson('/api/v1/genres/999', ['name' => 'Fantasy']);

    $response->assertNotFound();
});

it('deletes the specified genre', function () {
    $genre = Genre::factory()->create();

    $response = $this->deleteJson("/api/v1/genres/{$genre->id}");

    $response->assertNoContent();

    $this->assertDatabaseMissing('genres', ['id' => $genre->id]);
});

it('returns not found when deleting a missing genre', function () {
    $response = $this->deleteJson('/api/v1/genres/999');

    $response->assertNotFound();
});
