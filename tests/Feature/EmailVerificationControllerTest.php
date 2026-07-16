<?php

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function verificationUrl(User $user, ?string $email = null, ?DateTimeInterface $expiresAt = null): string
{
    return URL::temporarySignedRoute(
        'verification.verify',
        $expiresAt ?? now()->addMinutes(60),
        [
            'id' => $user->id,
            'hash' => sha1($email ?? $user->email),
        ]
    );
}

/*
|--------------------------------------------------------------------------
| verification-notification (resend)
|--------------------------------------------------------------------------
*/

it('sends a verification email to an unverified authenticated user', function () {
    Notification::fake();
    $user = User::factory()->create(['email_verified_at' => null]);
    Sanctum::actingAs($user, ['*']);

    $response = $this->postJson('/api/v1/email/verification-notification');

    $response->assertSuccessful();
    Notification::assertSentTo($user, VerifyEmail::class);
});

it('does not send a new notification when the email is already verified', function () {
    Notification::fake();
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['*']);

    $response = $this->postJson('/api/v1/email/verification-notification');

    $response->assertSuccessful()
        ->assertJsonPath('message', 'Email already verified');
    Notification::assertNothingSent();
});

it('rejects sending a verification email without authentication', function () {
    $response = $this->postJson('/api/v1/email/verification-notification');

    $response->assertUnauthorized();
});

it('throttles repeated verification email requests', function () {
    Notification::fake();
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['*']);

    for ($i = 0; $i < 6; $i++) {
        $this->postJson('/api/v1/email/verification-notification')->assertSuccessful();
    }

    $this->postJson('/api/v1/email/verification-notification')
        ->assertStatus(429);
});

/*
|--------------------------------------------------------------------------
| verify
|--------------------------------------------------------------------------
*/

it('verifies the authenticated user\'s email via a valid signed link', function () {
    Event::fake();
    $user = User::factory()->create(['email_verified_at' => null]);
    Sanctum::actingAs($user, ['*']);

    $response = $this->getJson(verificationUrl($user));

    $response->assertSuccessful();
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    Event::assertDispatched(Verified::class);
});

it('does not re-fire the Verified event when the email is already verified', function () {
    Event::fake();
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['*']);

    $response = $this->getJson(verificationUrl($user));

    $response->assertSuccessful()
        ->assertJsonPath('message', 'Email already verified');
    Event::assertNotDispatched(Verified::class);
});

it('rejects a link with an invalid hash', function () {
    $user = User::factory()->create(['email_verified_at' => null]);
    Sanctum::actingAs($user, ['*']);

    $response = $this->getJson(verificationUrl($user, email: 'someone-else@example.com'));

    $response->assertForbidden();
    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

it('rejects an unsigned link', function () {
    $user = User::factory()->create(['email_verified_at' => null]);
    Sanctum::actingAs($user, ['*']);

    $response = $this->getJson("/api/v1/email/verify/{$user->id}/" . sha1($user->email));

    $response->assertForbidden();
    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

it('rejects an expired link', function () {
    $user = User::factory()->create(['email_verified_at' => null]);
    Sanctum::actingAs($user, ['*']);

    $response = $this->getJson(verificationUrl($user, expiresAt: now()->subMinute()));

    $response->assertForbidden();
    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

it('rejects verifying when the link id does not match the authenticated user', function () {
    $owner = User::factory()->create(['email_verified_at' => null]);
    $intruder = User::factory()->create(['email_verified_at' => null]);
    Sanctum::actingAs($intruder, ['*']);

    $response = $this->getJson(verificationUrl($owner));

    $response->assertForbidden();
    expect($owner->fresh()->hasVerifiedEmail())->toBeFalse();
});

it('rejects verifying without authentication', function () {
    $user = User::factory()->create();

    $response = $this->getJson(verificationUrl($user));

    $response->assertUnauthorized();
});
