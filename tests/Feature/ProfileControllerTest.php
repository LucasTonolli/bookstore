<?php

use App\Enums\Roles;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| show
|--------------------------------------------------------------------------
*/

it('returns the authenticated user\'s profile', function () {
    $user = User::factory()->create(['role' => Roles::Client, 'name' => 'Machado de Assis']);
    Sanctum::actingAs($user, ['*']);

    $response = $this->getJson('/api/v1/profile');

    $response->assertSuccessful()
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.name', 'Machado de Assis')
        ->assertJsonPath('data.email', $user->email)
        ->assertJsonMissingPath('data.password');
});

it('rejects fetching the profile without authentication', function () {
    $response = $this->getJson('/api/v1/profile');

    $response->assertUnauthorized();
});

/*
|--------------------------------------------------------------------------
| update (name/email)
|--------------------------------------------------------------------------
*/

it('updates the authenticated user\'s name and email', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['*']);

    $response = $this->putJson('/api/v1/profile', [
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('data.name', 'Updated Name')
        ->assertJsonPath('data.email', 'updated@example.com');

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
    ]);
});

it('allows keeping the authenticated user\'s own unchanged email', function () {
    $user = User::factory()->create(['email' => 'unchanged@example.com']);
    Sanctum::actingAs($user, ['*']);

    $response = $this->putJson('/api/v1/profile', [
        'email' => 'unchanged@example.com',
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('data.email', 'unchanged@example.com');
});

it('rejects updating the profile email to another user\'s email', function () {
    User::factory()->create(['email' => 'taken@example.com']);
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['*']);

    $response = $this->putJson('/api/v1/profile', [
        'email' => 'taken@example.com',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('rejects an invalid email format when updating the profile', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['*']);

    $response = $this->putJson('/api/v1/profile', [
        'email' => 'not-an-email',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('ignores an attempt to change the role through the profile update', function () {
    $user = User::factory()->create(['role' => Roles::Client]);
    Sanctum::actingAs($user, ['*']);

    $response = $this->putJson('/api/v1/profile', [
        'name' => 'Updated Name',
        'role' => 'admin',
    ]);

    $response->assertSuccessful();

    expect($user->fresh()->role)->toBe(Roles::Client);
});

it('rejects updating the profile without authentication', function () {
    $response = $this->putJson('/api/v1/profile', ['name' => 'Updated Name']);

    $response->assertUnauthorized();
});

/*
|--------------------------------------------------------------------------
| update password
|--------------------------------------------------------------------------
*/

it('updates the authenticated user\'s password', function () {
    $user = User::factory()->create(['password' => 'old-password']);
    Sanctum::actingAs($user, ['*']);

    $response = $this->putJson('/api/v1/profile/password', [
        'current_password' => 'old-password',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $response->assertSuccessful();

    expect(Hash::check('new-password', $user->fresh()->password))->toBeTrue();
});

it('rejects updating the password with an incorrect current password', function () {
    $user = User::factory()->create(['password' => 'old-password']);
    Sanctum::actingAs($user, ['*']);

    $response = $this->putJson('/api/v1/profile/password', [
        'current_password' => 'wrong-password',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['current_password']);

    expect(Hash::check('old-password', $user->fresh()->password))->toBeTrue();
});

it('requires the new password confirmation to match', function () {
    $user = User::factory()->create(['password' => 'old-password']);
    Sanctum::actingAs($user, ['*']);

    $response = $this->putJson('/api/v1/profile/password', [
        'current_password' => 'old-password',
        'password' => 'new-password',
        'password_confirmation' => 'different-password',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});

it('requires a new password with a minimum length', function () {
    $user = User::factory()->create(['password' => 'old-password']);
    Sanctum::actingAs($user, ['*']);

    $response = $this->putJson('/api/v1/profile/password', [
        'current_password' => 'old-password',
        'password' => 'ab',
        'password_confirmation' => 'ab',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});

it('requires the current password field', function () {
    $user = User::factory()->create(['password' => 'old-password']);
    Sanctum::actingAs($user, ['*']);

    $response = $this->putJson('/api/v1/profile/password', [
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['current_password']);
});

it('rejects updating the password without authentication', function () {
    $response = $this->putJson('/api/v1/profile/password', [
        'current_password' => 'old-password',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $response->assertUnauthorized();
});
