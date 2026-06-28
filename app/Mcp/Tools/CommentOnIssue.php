<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\ResolvesAccessibleProjects;
use App\Models\Issue;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Add a comment to an issue. The comment is recorded in the issue activity timeline, attributed to the current user.')]
class CommentOnIssue extends Tool
{
    use ResolvesAccessibleProjects;

    public function handle(Request $request): Response
    {
        $request->validate([
            'issue' => ['required', 'integer'],
            'body' => ['required', 'string', 'max:5000'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $projectIds = $this->accessibleProjects($user)->pluck('id');

        $issue = Issue::whereIn('project_id', $projectIds)
            ->where('id', $request->get('issue'))
            ->first();

        if (! $issue instanceof Issue) {
            return Response::error('Issue not found or not accessible.');
        }

        $activity = $issue->activities()->create([
            'user_id' => $user->id,
            'type' => 'comment',
            'content' => $request->get('body'),
        ]);

        return Response::json(['id' => $activity->id, 'issue_id' => $issue->id]);
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'issue' => $schema->integer()->description('The issue id.')->required(),
            'body' => $schema->string()->description('Comment text.')->required(),
        ];
    }
}
