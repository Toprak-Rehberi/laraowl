<?php

use App\Models\User;

it('creates a token and flashes the plaintext once', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/settings/mcp-tokens', ['name' => 'laptop']);

    $response->assertRedirect();
    expect($user->tokens()->count())->toBe(1);
    $response->assertSessionHas('mcpToken');
});

it('revokes a token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('laptop')->accessToken;

    $this->actingAs($user)->delete("/settings/mcp-tokens/{$token->id}")->assertRedirect();

    expect($user->tokens()->count())->toBe(0);
});

it('cannot revoke another user\'s token', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $token = $other->createToken('laptop')->accessToken;

    $this->actingAs($user)->delete("/settings/mcp-tokens/{$token->id}")->assertNotFound();

    expect($other->tokens()->count())->toBe(1);
});
