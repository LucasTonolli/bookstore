<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| forgot-password
|--------------------------------------------------------------------------
*/

it('sends a password reset link to a registered email', function () {
    Notification::fake();
    $user = User::factory()->create();

    $response = $this->postJson('/api/v1/auth/forgot-password', [
        'email' => $user->email,
    ]);

    $response->assertSuccessful();
    Notification::assertSentTo($user, ResetPassword::class);
});

it('rejects a forgot-password request for a nonexistent email', function () {
    Notification::fake();

    $response = $this->postJson('/api/v1/auth/forgot-password', [
        'email' => 'nobody@example.com',
    ]);

    $response->assertSuccessful();
    Notification::assertNothingSent();
});

it('requires an email to request a password reset link', function () {
    $response = $this->postJson('/api/v1/auth/forgot-password', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('rejects an invalid email format on forgot-password', function () {
    $response = $this->postJson('/api/v1/auth/forgot-password', [
        'email' => 'not-an-email',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('throttles a second reset link request for the same email', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->postJson('/api/v1/auth/forgot-password', ['email' => $user->email])
        ->assertSuccessful();

    $this->postJson('/api/v1/auth/forgot-password', ['email' => $user->email])
        ->assertTooManyRequests();

    Notification::assertSentTo($user, ResetPassword::class);
});

it('not throttles after a delay', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->postJson('/api/v1/auth/forgot-password', ['email' => $user->email])
        ->assertSuccessful();

    $this->travel(60)->seconds();

    $this->postJson('/api/v1/auth/forgot-password', ['email' => $user->email])
        ->assertSuccessful();
});

/*
|--------------------------------------------------------------------------
| reset-password
|--------------------------------------------------------------------------
*/

it('resets the password with a valid token', function () {
    $user = User::factory()->create(['password' => 'old-password']);
    $token = Password::createToken($user);

    $response = $this->postJson('/api/v1/auth/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $response->assertSuccessful();
    expect(Hash::check('new-password', $user->fresh()->password))->toBeTrue();
});

it('revokes all existing tokens after a successful password reset', function () {
    $user = User::factory()->create(['password' => 'old-password']);
    $user->createToken('device-1');
    $user->createToken('device-2');
    $token = Password::createToken($user);

    $this->postJson('/api/v1/auth/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])->assertSuccessful();

    expect($user->tokens()->count())->toBe(0);
});

it('rejects resetting the password with an invalid token', function () {
    $user = User::factory()->create(['password' => 'old-password']);
    Password::createToken($user);

    $response = $this->postJson('/api/v1/auth/reset-password', [
        'token' => 'invalid-token',
        'email' => $user->email,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $response->assertUnauthorized();
    expect(Hash::check('old-password', $user->fresh()->password))->toBeTrue();
});

it('rejects resetting the password for a nonexistent email', function () {
    $response = $this->postJson('/api/v1/auth/reset-password', [
        'token' => 'some-token',
        'email' => 'nobody@example.com',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('requires the new password confirmation to match on reset', function () {
    $user = User::factory()->create();
    $token = Password::createToken($user);

    $response = $this->postJson('/api/v1/auth/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'new-password',
        'password_confirmation' => 'different-password',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});

it('requires a new password with a minimum length on reset', function () {
    $user = User::factory()->create();
    $token = Password::createToken($user);

    $response = $this->postJson('/api/v1/auth/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'ab',
        'password_confirmation' => 'ab',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});

it('requires all fields to reset the password', function () {
    $response = $this->postJson('/api/v1/auth/reset-password', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['token', 'email', 'password']);
});
