<?php

use App\Mcp\Servers\LaraowlServer;
use App\Mcp\Tools\QueryTelemetry;
use App\Models\Project;
use App\Models\Record;
use App\Models\User;

it('returns recent records of a type for an accessible project, sanitized', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user->currentTeam)->create(['slug' => 'api']);

    Record::factory()->for($project)->create([
        'type' => 'request',
        'payload' => ['method' => 'POST', 'path' => '/login', 'password' => 'hunter2'],
        'created_at' => now(),
    ]);
    Record::factory()->for($project)->create(['type' => 'query', 'payload' => ['sql' => 'select 1'], 'created_at' => now()]);

    LaraowlServer::actingAs($user)
        ->tool(QueryTelemetry::class, ['project' => 'api', 'type' => 'request'])
        ->assertOk()
        ->assertSee('/login')
        ->assertSee('[REDACTED]')
        ->assertDontSee('hunter2')
        ->assertDontSee('select 1');
});

it('refuses telemetry for an inaccessible project', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    Project::factory()->for($other->currentTeam)->create(['slug' => 'private']);

    LaraowlServer::actingAs($user)
        ->tool(QueryTelemetry::class, ['project' => 'private', 'type' => 'request'])
        ->assertHasErrors();
});
