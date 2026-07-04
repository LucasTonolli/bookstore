<?php

use App\Models\Author;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns a paginated list of authors', function () {
    Author::factory()->count(3)->create();

    $response = $this->getJson('/api/v1/authors');

    $response->assertSuccessful()
        ->assertJsonPath('message', 'Authors listed successfully')
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('pagination.total', 3);
});

it('filters authors by name', function () {
    Author::factory()->create(['name' => 'Machado']);
    Author::factory()->create(['name' => 'Clarice']);

    $response = $this->getJson('/api/v1/authors?name=Mach');

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Machado');
});

it('filters authors by last_name', function () {
    Author::factory()->create(['last_name' => 'Assis']);
    Author::factory()->create(['last_name' => 'Lispector']);

    $response = $this->getJson('/api/v1/authors?last_name=Assis');

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.last_name', 'Assis');
});

it('filters authors by nationality', function () {
    Author::factory()->create(['nationality' => 'Brazil']);
    Author::factory()->create(['nationality' => 'France']);

    $response = $this->getJson('/api/v1/authors?nationality=Brazil');

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.nationality', 'Brazil');
});

it('filters authors by birth_date', function () {
    Author::factory()->create(['birth_date' => '1839-06-21']);
    Author::factory()->create(['birth_date' => '1920-12-10']);

    $response = $this->getJson('/api/v1/authors?birth_date=1839-06-21');

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.birth_date', '1839-06-21');
});

it('sorts authors', function () {
    Author::factory()->create(['name' => 'Zeta']);
    Author::factory()->create(['name' => 'Alpha']);

    $response = $this->getJson('/api/v1/authors?sort=name&direction=desc');

    $response->assertSuccessful()
        ->assertJsonPath('data.0.name', 'Zeta')
        ->assertJsonPath('data.1.name', 'Alpha');
});

it('paginates authors', function () {
    Author::factory()->count(5)->create();

    $response = $this->getJson('/api/v1/authors?per_page=2&page=2');

    $response->assertSuccessful()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('pagination.current_page', 2)
        ->assertJsonPath('pagination.per_page', 2)
        ->assertJsonPath('pagination.total', 5);
});

it('combines multiple filters', function () {
    Author::factory()->create(['name' => 'Machado', 'nationality' => 'Brazil']);
    Author::factory()->create(['name' => 'Machado', 'nationality' => 'Portugal']);
    Author::factory()->create(['name' => 'Clarice', 'nationality' => 'Brazil']);

    $response = $this->getJson('/api/v1/authors?name=Machado&nationality=Brazil');

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data');
});

it('rejects invalid filters', function () {
    $response = $this->getJson('/api/v1/authors?sort=invalid_column&direction=sideways&per_page=0');

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['sort', 'direction', 'per_page']);
});

it('creates a new author', function () {
    $payload = [
        'name' => 'Machado',
        'last_name' => 'de Assis',
        'nationality' => 'Brazil',
        'birth_date' => '1839-06-21',
    ];

    $response = $this->postJson('/api/v1/authors', $payload);

    $response->assertCreated()
        ->assertJsonPath('message', 'Author created successfully')
        ->assertJsonPath('data.name', 'Machado')
        ->assertJsonPath('data.last_name', 'de Assis')
        ->assertJsonPath('data.nationality', 'Brazil')
        ->assertJsonPath('data.birth_date', '1839-06-21');

    $this->assertDatabaseHas('authors', [
        'name' => 'Machado',
        'last_name' => 'de Assis',
        'nationality' => 'Brazil',
    ]);
});

it('requires all fields when creating an author', function () {
    $response = $this->postJson('/api/v1/authors', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'last_name', 'nationality', 'birth_date']);
});

it('rejects a birth_date in the future when creating an author', function () {
    $response = $this->postJson('/api/v1/authors', [
        'name' => 'Machado',
        'last_name' => 'de Assis',
        'nationality' => 'Brazil',
        'birth_date' => now()->addDay()->toDateString(),
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['birth_date']);
});

it('returns the specified author', function () {
    $author = Author::factory()->create();

    $response = $this->getJson("/api/v1/authors/{$author->id}");

    $response->assertSuccessful()
        ->assertJsonPath('message', 'Author details')
        ->assertJsonPath('data.id', $author->id)
        ->assertJsonPath('data.name', $author->name);
});

it('returns not found when showing a missing author', function () {
    $response = $this->getJson('/api/v1/authors/999');

    $response->assertNotFound();
});

it('modifies the specified author', function () {
    $author = Author::factory()->create();

    $payload = [
        'name' => 'Updated Name',
        'last_name' => $author->last_name,
        'nationality' => $author->nationality,
        'birth_date' => $author->birth_date->toDateString(),
    ];

    $response = $this->putJson("/api/v1/authors/{$author->id}", $payload);

    $response->assertSuccessful()
        ->assertJsonPath('message', 'Author updated successfully')
        ->assertJsonPath('data.name', 'Updated Name');

    $this->assertDatabaseHas('authors', [
        'id' => $author->id,
        'name' => 'Updated Name',
    ]);
});

it('requires all fields when updating an author', function () {
    $author = Author::factory()->create();

    $response = $this->putJson("/api/v1/authors/{$author->id}", []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'last_name', 'nationality', 'birth_date']);
});

it('returns not found when updating a missing author', function () {
    $response = $this->putJson('/api/v1/authors/999', [
        'name' => 'Machado',
        'last_name' => 'de Assis',
        'nationality' => 'Brazil',
        'birth_date' => '1839-06-21',
    ]);

    $response->assertNotFound();
});

it('deletes the specified author', function () {
    $author = Author::factory()->create();

    $response = $this->deleteJson("/api/v1/authors/{$author->id}");

    $response->assertNoContent();

    $this->assertDatabaseMissing('authors', ['id' => $author->id]);
});

it('returns not found when deleting a missing author', function () {
    $response = $this->deleteJson('/api/v1/authors/999');

    $response->assertNotFound();
});
