<?php

use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

it('issues a token for a user by email', function () {
    $user = User::factory()->create(['email' => 'dev@example.com']);

    $this->artisan('laraowl:mcp-token', ['user' => 'dev@example.com'])
        ->assertExitCode(0);

    expect(PersonalAccessToken::where('tokenable_id', $user->id)->count())->toBe(1);
});

it('fails when the user does not exist', function () {
    $this->artisan('laraowl:mcp-token', ['user' => 'nobody@example.com'])
        ->assertExitCode(1);
});
