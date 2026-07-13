<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('registers a new user', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Machado de Assis',
        'email' => 'machado@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertCreated()
        ->assertJsonPath('message', 'User created successfully')
        ->assertJsonPath('data.name', 'Machado de Assis')
        ->assertJsonPath('data.email', 'machado@example.com')
        ->assertJsonMissingPath('data.password')
        ->assertJsonStructure(['message', 'token', 'data' => ['id', 'name', 'email', 'created_at']]);

    expect($response->json('token'))->toBeString()->not->toBeEmpty();
});

it('creates the user in the database with a hashed password', function () {
    $this->postJson('/api/v1/auth/register', [
        'name' => 'Machado de Assis',
        'email' => 'machado@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $user = User::whereEmail('machado@example.com')->first();

    expect($user)->not->toBeNull();
    expect(Hash::check('password', $user->password))->toBeTrue();
});


it('requires a name', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'email' => 'machado@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});

it('requires a valid, unique email', function () {
    User::factory()->create(['email' => 'machado@example.com']);

    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Machado de Assis',
        'email' => 'machado@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('rejects an invalid email format', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Machado de Assis',
        'email' => 'not-an-email',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('requires a password with a minimum length', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Machado de Assis',
        'email' => 'machado@example.com',
        'password' => 'ab',
        'password_confirmation' => 'ab',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});

it('requires the password confirmation to match', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Machado de Assis',
        'email' => 'machado@example.com',
        'password' => 'password',
        'password_confirmation' => 'different',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});

it('requires a password', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Machado de Assis',
        'email' => 'machado@example.com',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});
