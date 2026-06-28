<?php

use App\Mcp\Servers\LaraowlServer;
use App\Mcp\Tools\GetIssue;
use App\Models\Issue;
use App\Models\Project;
use App\Models\Record;
use App\Models\User;

it('returns issue detail with sanitized record payloads', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user->currentTeam)->create();
    $issue = Issue::create(['project_id' => $project->id, 'hash' => 'h', 'type' => 'exception', 'title' => 'Boom', 'message' => '', 'status' => 'open', 'last_seen_at' => now()]);

    Record::factory()->for($project)->create([
        'issue_id' => $issue->id,
        'type' => 'exception',
        'payload' => ['message' => 'Boom', 'authorization' => 'Bearer leak-me'],
    ]);

    LaraowlServer::actingAs($user)
        ->tool(GetIssue::class, ['issue' => $issue->id])
        ->assertOk()
        ->assertSee('Boom')
        ->assertSee('[REDACTED]')
        ->assertDontSee('leak-me');
});

it('refuses an issue outside the user\'s teams', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $project = Project::factory()->for($other->currentTeam)->create();
    $issue = Issue::create(['project_id' => $project->id, 'hash' => 'h', 'type' => 'exception', 'title' => 'Secret', 'message' => '', 'status' => 'open', 'last_seen_at' => now()]);

    LaraowlServer::actingAs($user)
        ->tool(GetIssue::class, ['issue' => $issue->id])
        ->assertHasErrors();
});
