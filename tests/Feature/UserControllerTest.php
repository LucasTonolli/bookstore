<?php

use App\Enums\Roles;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| index
|--------------------------------------------------------------------------
*/

it('lists users for an admin', function () {
    Sanctum::actingAs(User::factory()->create(['role' => Roles::Admin]), ['user:read']);
    User::factory()->count(3)->create();

    $response = $this->getJson('/api/v1/users');

    $response->assertSuccessful()
        ->assertJsonPath('message', 'Users listed successfully')
        // +1 for the authenticated admin created above
        ->assertJsonCount(4, 'data')
        ->assertJsonPath('pagination.total', 4);
});

it('filters users by role', function () {
    Sanctum::actingAs(User::factory()->create(['role' => Roles::Admin]), ['user:read']);
    User::factory()->create(['role' => Roles::Staff]);
    User::factory()->create(['role' => Roles::Client]);

    $response = $this->getJson('/api/v1/users?role=staff');

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.role', Roles::Staff->label());
});

it('filters users by name', function () {
    Sanctum::actingAs(User::factory()->create(['role' => Roles::Admin]), ['user:read']);
    User::factory()->create(['name' => 'Machado de Assis']);
    User::factory()->create(['name' => 'Clarice Lispector']);

    $response = $this->getJson('/api/v1/users?name=Machado');

    $response->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Machado de Assis');
});

it('paginates users', function () {
    Sanctum::actingAs(User::factory()->create(['role' => Roles::Admin]), ['user:read']);
    User::factory()->count(5)->create();

    $response = $this->getJson('/api/v1/users?per_page=2&page=2');

    $response->assertSuccessful()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('pagination.current_page', 2)
        ->assertJsonPath('pagination.per_page', 2);
});

it('rejects listing users without authentication', function () {
    $response = $this->getJson('/api/v1/users');

    $response->assertUnauthorized();
});

it('rejects listing users without the user:read ability', function () {
    Sanctum::actingAs(User::factory()->create(['role' => Roles::Client]), ['book:read']);

    $response = $this->getJson('/api/v1/users');

    $response->assertForbidden();
});

it('rejects listing users for a staff or client role user', function () {
    $staff = User::factory()->create(['role' => Roles::Staff]);
    Sanctum::actingAs($staff, $staff->permissions());

    $response = $this->getJson('/api/v1/users');

    $response->assertForbidden();
});

/*
|--------------------------------------------------------------------------
| store
|--------------------------------------------------------------------------
*/

it('creates a new user as an admin', function () {
    Sanctum::actingAs(User::factory()->create(['role' => Roles::Admin]), ['user:create']);

    $response = $this->postJson('/api/v1/users', [
        'name' => 'Machado de Assis',
        'email' => 'machado@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'staff',
    ]);

    $response->assertCreated()
        ->assertJsonPath('message', 'User created successfully')
        ->assertJsonPath('data.name', 'Machado de Assis')
        ->assertJsonPath('data.email', 'machado@example.com')
        ->assertJsonPath('data.role', Roles::Staff->label())
        ->assertJsonMissingPath('data.password');

    $this->assertDatabaseHas('users', [
        'name' => 'Machado de Assis',
        'email' => 'machado@example.com',
        'role' => 'staff',
    ]);
});

it('requires all fields when creating a user', function () {
    Sanctum::actingAs(User::factory()->create(['role' => Roles::Admin]), ['user:create']);

    $response = $this->postJson('/api/v1/users', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'email', 'password', 'role']);
});

it('rejects creating a user with a duplicate email', function () {
    Sanctum::actingAs(User::factory()->create(['role' => Roles::Admin]), ['user:create']);
    User::factory()->create(['email' => 'machado@example.com']);

    $response = $this->postJson('/api/v1/users', [
        'name' => 'Machado de Assis',
        'email' => 'machado@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'staff',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('rejects an invalid role when creating a user', function () {
    Sanctum::actingAs(User::factory()->create(['role' => Roles::Admin]), ['user:create']);

    $response = $this->postJson('/api/v1/users', [
        'name' => 'Machado de Assis',
        'email' => 'machado@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'superadmin',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['role']);
});

it('rejects creating a user without authentication', function () {
    $response = $this->postJson('/api/v1/users', [
        'name' => 'Machado de Assis',
        'email' => 'machado@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'staff',
    ]);

    $response->assertUnauthorized();
});

it('rejects creating a user without the user:create ability', function () {
    Sanctum::actingAs(User::factory()->create(['role' => Roles::Staff]), ['user:read']);

    $response = $this->postJson('/api/v1/users', [
        'name' => 'Machado de Assis',
        'email' => 'machado@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'staff',
    ]);

    $response->assertForbidden();
});

/*
|--------------------------------------------------------------------------
| show
|--------------------------------------------------------------------------
*/

it('returns the specified user for an admin', function () {
    Sanctum::actingAs(User::factory()->create(['role' => Roles::Admin]), ['user:read']);
    $user = User::factory()->create();

    $response = $this->getJson("/api/v1/users/{$user->id}");

    $response->assertSuccessful()
        ->assertJsonPath('message', 'User found')
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.email', $user->email);
});

it('returns not found when showing a missing user', function () {
    Sanctum::actingAs(User::factory()->create(['role' => Roles::Admin]), ['user:read']);

    $response = $this->getJson('/api/v1/users/999');

    $response->assertNotFound();
});

it('rejects showing a user without authentication', function () {
    $user = User::factory()->create();

    $response = $this->getJson("/api/v1/users/{$user->id}");

    $response->assertUnauthorized();
});

it('rejects showing a user without the user:read ability', function () {
    Sanctum::actingAs(User::factory()->create(['role' => Roles::Client]), ['book:read']);
    $user = User::factory()->create();

    $response = $this->getJson("/api/v1/users/{$user->id}");

    $response->assertForbidden();
});

/*
|--------------------------------------------------------------------------
| update
|--------------------------------------------------------------------------
*/

it('updates the specified user as an admin', function () {
    Sanctum::actingAs(User::factory()->create(['role' => Roles::Admin]), ['user:update']);
    $user = User::factory()->create(['role' => Roles::Client]);

    $response = $this->putJson("/api/v1/users/{$user->id}", [
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
        'role' => 'staff',
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('message', 'User updated successfully')
        ->assertJsonPath('data.name', 'Updated Name')
        ->assertJsonPath('data.email', 'updated@example.com')
        ->assertJsonPath('data.role', Roles::Staff->label());

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
        'role' => 'staff',
    ]);
});

it('allows keeping a user\'s own unchanged email on update', function () {
    Sanctum::actingAs(User::factory()->create(['role' => Roles::Admin]), ['user:update']);
    $user = User::factory()->create(['email' => 'unchanged@example.com']);

    $response = $this->putJson("/api/v1/users/{$user->id}", [
        'email' => 'unchanged@example.com',
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('data.email', 'unchanged@example.com');
});

it('rejects updating a user to another user\'s email', function () {
    Sanctum::actingAs(User::factory()->create(['role' => Roles::Admin]), ['user:update']);
    User::factory()->create(['email' => 'taken@example.com']);
    $user = User::factory()->create(['email' => 'user@example.com']);

    $response = $this->putJson("/api/v1/users/{$user->id}", [
        'email' => 'taken@example.com',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('rejects an invalid role when updating a user', function () {
    Sanctum::actingAs(User::factory()->create(['role' => Roles::Admin]), ['user:update']);
    $user = User::factory()->create();

    $response = $this->putJson("/api/v1/users/{$user->id}", [
        'role' => 'superadmin',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['role']);
});

it('returns not found when updating a missing user', function () {
    Sanctum::actingAs(User::factory()->create(['role' => Roles::Admin]), ['user:update']);

    $response = $this->putJson('/api/v1/users/999', ['name' => 'Updated Name']);

    $response->assertNotFound();
});

it('rejects updating a user without authentication', function () {
    $user = User::factory()->create();

    $response = $this->putJson("/api/v1/users/{$user->id}", ['name' => 'Updated Name']);

    $response->assertUnauthorized();
});

it('rejects updating a user without the user:update ability', function () {
    Sanctum::actingAs(User::factory()->create(['role' => Roles::Staff]), ['user:read']);
    $user = User::factory()->create();

    $response = $this->putJson("/api/v1/users/{$user->id}", ['name' => 'Updated Name']);

    $response->assertForbidden();
});

/*
|--------------------------------------------------------------------------
| destroy
|--------------------------------------------------------------------------
*/

it('deletes the specified user as an admin', function () {
    Sanctum::actingAs(User::factory()->create(['role' => Roles::Admin]), ['user:delete']);
    $user = User::factory()->create();

    $response = $this->deleteJson("/api/v1/users/{$user->id}");

    $response->assertNoContent();

    $this->assertDatabaseMissing('users', ['id' => $user->id]);
});

it('returns not found when deleting a missing user', function () {
    Sanctum::actingAs(User::factory()->create(['role' => Roles::Admin]), ['user:delete']);

    $response = $this->deleteJson('/api/v1/users/999');

    $response->assertNotFound();
});

it('rejects deleting a user without authentication', function () {
    $user = User::factory()->create();

    $response = $this->deleteJson("/api/v1/users/{$user->id}");

    $response->assertUnauthorized();
});

it('rejects deleting a user without the user:delete ability', function () {
    Sanctum::actingAs(User::factory()->create(['role' => Roles::Staff]), ['user:read']);
    $user = User::factory()->create();

    $response = $this->deleteJson("/api/v1/users/{$user->id}");

    $response->assertForbidden();
});
