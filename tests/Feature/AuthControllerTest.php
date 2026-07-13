<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('logs in a user with valid credentials', function () {
    $user = User::factory()->create(['password' => 'password']);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.email', $user->email)
        ->assertJsonStructure(['token', 'data' => ['id', 'name', 'email', 'created_at']]);

    expect($response->json('token'))->toBeString()->not->toBeEmpty();

    $this->assertDatabaseHas('personal_access_tokens', [
        'tokenable_id' => $user->id,
        'tokenable_type' => User::class,
    ]);
});

it('rejects login with an incorrect password', function () {
    $user = User::factory()->create(['password' => 'password']);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertUnauthorized()
        ->assertJsonPath('message', 'The provided credentials are incorrect.');
});

it('rejects login for a nonexistent email', function () {
    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'nobody@example.com',
        'password' => 'password',
    ]);

    $response->assertUnauthorized()
        ->assertJsonPath('message', 'The provided credentials are incorrect.');
});

it('requires an email to log in', function () {
    $response = $this->postJson('/api/v1/auth/login', [
        'password' => 'password',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('requires a validly formatted email to log in', function () {
    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'not-an-email',
        'password' => 'password',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('requires a password to log in', function () {
    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'machado@example.com',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});

it('logs out the authenticated user and revokes the current token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->deleteJson('/api/v1/auth/logout');

    $response->assertNoContent();

    $this->assertDatabaseMissing('personal_access_tokens', [
        'tokenable_id' => $user->id,
        'tokenable_type' => User::class,
    ]);
});

it('rejects logout without authentication', function () {
    $response = $this->deleteJson('/api/v1/auth/logout');

    $response->assertUnauthorized();
});

it('does not revoke other tokens when logging out', function () {
    $user = User::factory()->create();
    $tokenToKeep = $user->createToken('keep')->plainTextToken;
    $tokenToRevoke = $user->createToken('revoke')->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$tokenToRevoke}")
        ->deleteJson('/api/v1/auth/logout')
        ->assertNoContent();

    $this->withHeader('Authorization', "Bearer {$tokenToKeep}")
        ->getJson('/api/user')
        ->assertSuccessful();
});
