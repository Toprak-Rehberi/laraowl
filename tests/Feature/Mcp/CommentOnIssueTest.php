<?php

use App\Mcp\Servers\LaraowlServer;
use App\Mcp\Tools\CommentOnIssue;
use App\Models\Issue;
use App\Models\IssueActivity;
use App\Models\Project;
use App\Models\User;

it('adds an attributed comment activity', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user->currentTeam)->create();
    $issue = Issue::create(['project_id' => $project->id, 'hash' => 'h', 'type' => 'exception', 'title' => 'Bug', 'status' => 'open', 'last_seen_at' => now(), 'message' => '']);

    LaraowlServer::actingAs($user)
        ->tool(CommentOnIssue::class, ['issue' => $issue->id, 'body' => 'Likely a null guard.'])
        ->assertOk();

    $activity = IssueActivity::where('issue_id', $issue->id)->first();
    expect($activity->type)->toBe('comment')
        ->and($activity->user_id)->toBe($user->id)
        ->and($activity->content)->toBe('Likely a null guard.');
});

it('cannot comment on an issue outside the user\'s teams', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $project = Project::factory()->for($other->currentTeam)->create();
    $issue = Issue::create(['project_id' => $project->id, 'hash' => 'h', 'type' => 'exception', 'title' => 'Bug', 'status' => 'open', 'last_seen_at' => now(), 'message' => '']);

    LaraowlServer::actingAs($user)
        ->tool(CommentOnIssue::class, ['issue' => $issue->id, 'body' => 'hi'])
        ->assertHasErrors();

    expect(IssueActivity::where('issue_id', $issue->id)->count())->toBe(0);
});
