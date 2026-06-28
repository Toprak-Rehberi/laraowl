<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\ResolvesAccessibleProjects;
use App\Mcp\Support\PayloadSanitizer;
use App\Models\Issue;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Get full detail for one issue: the issue, its recent records (stack traces / payloads), and its activity timeline.')]
class GetIssue extends Tool
{
    use ResolvesAccessibleProjects;

    public function handle(Request $request): Response
    {
        $request->validate(['issue' => ['required', 'integer']]);

        /** @var User $user */
        $user = $request->user();
        $projectIds = $this->accessibleProjects($user)->pluck('id');

        $issue = Issue::whereIn('project_id', $projectIds)
            ->where('id', $request->get('issue'))
            ->first();

        if (! $issue instanceof Issue) {
            return Response::error('Issue not found or not accessible.');
        }

        $records = $issue->records()
            ->orderByDesc('created_at')
            ->limit(20)
            ->get(['id', 'type', 'payload', 'created_at'])
            ->map(fn ($record) => [
                'id' => $record->id,
                'type' => $record->type,
                'created_at' => $record->created_at,
                'payload' => PayloadSanitizer::sanitize((array) $record->payload),
            ]);

        $activities = $issue->activities()
            ->orderBy('created_at')
            ->get(['id', 'user_id', 'type', 'content', 'created_at']);

        return Response::json([
            'issue' => $issue->only(['id', 'project_id', 'type', 'title', 'message', 'status', 'priority', 'occurrences_count', 'users_count', 'first_seen_at', 'last_seen_at']),
            'records' => $records,
            'activities' => $activities,
        ]);
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'issue' => $schema->integer()->description('The issue id.')->required(),
        ];
    }
}
