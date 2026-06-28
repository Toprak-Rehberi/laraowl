<?php

use App\Mcp\Servers\LaraowlServer;
use App\Mcp\Tools\ListIssues;
use App\Models\Issue;
use App\Models\Project;
use App\Models\User;

it('lists issues for an accessible project and filters by status', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user->currentTeam)->create(['slug' => 'shop']);

    Issue::create(['project_id' => $project->id, 'hash' => 'a', 'type' => 'exception', 'title' => 'Open Bug', 'message' => '', 'status' => 'open', 'last_seen_at' => now()]);
    Issue::create(['project_id' => $project->id, 'hash' => 'b', 'type' => 'exception', 'title' => 'Fixed Bug', 'message' => '', 'status' => 'resolved', 'last_seen_at' => now()]);

    LaraowlServer::actingAs($user)
        ->tool(ListIssues::class, ['project' => 'shop', 'status' => 'open'])
        ->assertOk()
        ->assertSee('Open Bug')
        ->assertDontSee('Fixed Bug');
});

it('rejects a project the user cannot access', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    Project::factory()->for($other->currentTeam)->create(['slug' => 'secret']);

    LaraowlServer::actingAs($user)
        ->tool(ListIssues::class, ['project' => 'secret'])
        ->assertHasErrors();
});
