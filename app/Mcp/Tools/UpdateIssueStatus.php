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

#[Description('Update an issue\'s status (open, resolved, ignored). The change is logged to the issue activity timeline, attributed to the current user.')]
class UpdateIssueStatus extends Tool
{
    use ResolvesAccessibleProjects;

    public function handle(Request $request): Response
    {
        $request->validate([
            'issue' => ['required', 'integer'],
            'status' => ['required', 'in:open,resolved,ignored'],
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

        $from = $issue->status;
        $to = $request->get('status');

        if ($from !== $to) {
            $issue->update(['status' => $to]);

            $issue->activities()->create([
                'user_id' => $user->id,
                'type' => 'status_change',
                'content' => "Status changed from {$from} to {$to}.",
                'metadata' => ['from' => $from, 'to' => $to],
            ]);
        }

        return Response::json(['id' => $issue->id, 'status' => $to]);
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'issue' => $schema->integer()->description('The issue id.')->required(),
            'status' => $schema->string()->description('New status.')->enum(['open', 'resolved', 'ignored'])->required(),
        ];
    }
}
