<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\ResolvesAccessibleProjects;
use App\Models\Project;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('List issues for a project, most recently seen first. Optionally filter by status.')]
class ListIssues extends Tool
{
    use ResolvesAccessibleProjects;

    public function handle(Request $request): Response
    {
        $request->validate([
            'project' => ['required'],
            'status' => ['nullable', 'in:open,resolved,ignored'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $identifier = $request->get('project');

        $project = $this->resolveAccessibleProject($user, $identifier);

        if (! $project instanceof Project) {
            return Response::error("Project [{$identifier}] not found or not accessible.");
        }

        $issues = $project->issues()
            ->when($request->get('status'), fn ($q, $status) => $q->where('status', $status))
            ->orderByDesc('last_seen_at')
            ->limit((int) $request->get('limit', 25))
            ->get(['id', 'type', 'title', 'status', 'priority', 'occurrences_count', 'users_count', 'first_seen_at', 'last_seen_at']);

        return Response::json($issues);
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'project' => $schema->string()->description('Project slug.')->required(),
            'status' => $schema->string()->description('Filter: open, resolved, or ignored.')->enum(['open', 'resolved', 'ignored']),
            'limit' => $schema->integer()->description('Max issues to return (1-100).')->default(25),
        ];
    }
}
