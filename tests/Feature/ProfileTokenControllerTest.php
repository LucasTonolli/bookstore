<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function authenticateWithRealToken(User $user, string $name = 'current-device'): string
{
    return $user->createToken($name, ['*'])->plainTextToken;
}

/*
|--------------------------------------------------------------------------
| index
|--------------------------------------------------------------------------
*/

it('lists only the authenticated user\'s own tokens', function () {
    $user = User::factory()->create();
    $currentToken = authenticateWithRealToken($user, 'current-device');
    $user->createToken('device-2');

    $otherUser = User::factory()->create();
    $otherUser->createToken('other-device');

    $response = $this->withHeader('Authorization', "Bearer {$currentToken}")
        ->getJson('/api/v1/profile/tokens');

    $response->assertSuccessful()
        ->assertJsonCount(2, 'data')
        ->assertJsonStructure([
            'message',
            'data' => [
                '*' => ['id', 'name', 'abilities', 'last_used_at', 'created_at'],
            ],
        ]);

    $names = collect($response->json('data'))->pluck('name');
    expect($names)->toContain('current-device', 'device-2');
    expect($names)->not->toContain('other-device');
});

it('rejects listing tokens without authentication', function () {
    $response = $this->getJson('/api/v1/profile/tokens');

    $response->assertUnauthorized();
});

/*
|--------------------------------------------------------------------------
| destroy (one)
|--------------------------------------------------------------------------
*/

it('revokes a specific token belonging to the authenticated user', function () {
    $user = User::factory()->create();
    $currentToken = authenticateWithRealToken($user, 'current-device');
    $deviceToken = $user->createToken('device-2');

    $response = $this->withHeader('Authorization', "Bearer {$currentToken}")
        ->deleteJson("/api/v1/profile/tokens/{$deviceToken->accessToken->id}");

    $response->assertNoContent();

    $this->assertDatabaseMissing('personal_access_tokens', ['id' => $deviceToken->accessToken->id]);
});

it('rejects revoking another user\'s token', function () {
    $user = User::factory()->create();
    $currentToken = authenticateWithRealToken($user, 'current-device');

    $otherUser = User::factory()->create();
    $otherToken = $otherUser->createToken('other-device');

    $response = $this->withHeader('Authorization', "Bearer {$currentToken}")
        ->deleteJson("/api/v1/profile/tokens/{$otherToken->accessToken->id}");

    $response->assertNotFound();

    $this->assertDatabaseHas('personal_access_tokens', ['id' => $otherToken->accessToken->id]);
});

it('rejects revoking the current token via this endpoint', function () {
    $user = User::factory()->create();
    $currentToken = authenticateWithRealToken($user, 'current-device');
    $currentTokenId = $user->tokens()->first()->id;

    $response = $this->withHeader('Authorization', "Bearer {$currentToken}")
        ->deleteJson("/api/v1/profile/tokens/{$currentTokenId}");

    $response->assertBadRequest();

    $this->assertDatabaseHas('personal_access_tokens', ['id' => $currentTokenId]);
});

it('returns not found when revoking a nonexistent token', function () {
    $user = User::factory()->create();
    $currentToken = authenticateWithRealToken($user, 'current-device');

    $response = $this->withHeader('Authorization', "Bearer {$currentToken}")
        ->deleteJson('/api/v1/profile/tokens/999999');

    $response->assertNotFound();
});

it('rejects revoking a token without authentication', function () {
    $user = User::factory()->create();
    $token = $user->createToken('device-1');

    $response = $this->deleteJson("/api/v1/profile/tokens/{$token->accessToken->id}");

    $response->assertUnauthorized();
});

/*
|--------------------------------------------------------------------------
| destroy (all)
|--------------------------------------------------------------------------
*/

it('revokes all tokens except the current one', function () {
    $user = User::factory()->create();
    $currentToken = authenticateWithRealToken($user, 'current-device');
    $currentTokenId = $user->tokens()->first()->id;
    $user->createToken('device-2');
    $user->createToken('device-3');

    $response = $this->withHeader('Authorization', "Bearer {$currentToken}")
        ->deleteJson('/api/v1/profile/tokens');

    $response->assertSuccessful();

    expect($user->tokens()->count())->toBe(1);
    $this->assertDatabaseHas('personal_access_tokens', ['id' => $currentTokenId]);
});

it('does not remove other users\' tokens when revoking all', function () {
    $user = User::factory()->create();
    $currentToken = authenticateWithRealToken($user, 'current-device');
    $user->createToken('device-2');

    $otherUser = User::factory()->create();
    $otherToken = $otherUser->createToken('other-device');

    $this->withHeader('Authorization', "Bearer {$currentToken}")
        ->deleteJson('/api/v1/profile/tokens')
        ->assertSuccessful();

    $this->assertDatabaseHas('personal_access_tokens', ['id' => $otherToken->accessToken->id]);
});

it('succeeds when revoking all tokens and there are no other tokens to remove', function () {
    $user = User::factory()->create();
    $currentToken = authenticateWithRealToken($user, 'current-device');

    $response = $this->withHeader('Authorization', "Bearer {$currentToken}")
        ->deleteJson('/api/v1/profile/tokens');

    $response->assertSuccessful();
    expect($user->tokens()->count())->toBe(1);
});

it('rejects revoking all tokens without authentication', function () {
    $response = $this->deleteJson('/api/v1/profile/tokens');

    $response->assertUnauthorized();
});
