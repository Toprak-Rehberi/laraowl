<?php

use App\Mcp\Servers\LaraowlServer;
use App\Mcp\Tools\UpdateIssueStatus;
use App\Models\Issue;
use App\Models\IssueActivity;
use App\Models\Project;
use App\Models\User;

it('updates status and logs an attributed activity', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user->currentTeam)->create();
    $issue = Issue::create(['project_id' => $project->id, 'hash' => 'h', 'type' => 'exception', 'title' => 'Bug', 'status' => 'open', 'last_seen_at' => now(), 'message' => '']);

    LaraowlServer::actingAs($user)
        ->tool(UpdateIssueStatus::class, ['issue' => $issue->id, 'status' => 'resolved'])
        ->assertOk();

    expect($issue->fresh()->status)->toBe('resolved');

    $activity = IssueActivity::where('issue_id', $issue->id)->first();
    expect($activity->type)->toBe('status_change')
        ->and($activity->user_id)->toBe($user->id)
        ->and($activity->metadata['from'])->toBe('open')
        ->and($activity->metadata['to'])->toBe('resolved');
});

it('cannot update an issue outside the user\'s teams', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $project = Project::factory()->for($other->currentTeam)->create();
    $issue = Issue::create(['project_id' => $project->id, 'hash' => 'h', 'type' => 'exception', 'title' => 'Bug', 'status' => 'open', 'last_seen_at' => now(), 'message' => '']);

    LaraowlServer::actingAs($user)
        ->tool(UpdateIssueStatus::class, ['issue' => $issue->id, 'status' => 'resolved'])
        ->assertHasErrors();

    expect($issue->fresh()->status)->toBe('open');
});
