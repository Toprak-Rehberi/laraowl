<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\ResolvesAccessibleProjects;
use App\Mcp\Support\PayloadSanitizer;
use App\Models\Project;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Query raw telemetry records (requests, queries, jobs, commands, mail, cache, etc.) for a project over a time period. Use this to debug non-exception behaviour like slow endpoints or failing jobs.')]
class QueryTelemetry extends Tool
{
    use ResolvesAccessibleProjects;

    public function handle(Request $request): Response
    {
        $request->validate([
            'project' => ['required'],
            'type' => ['required', 'string'],
            'period' => ['nullable', 'in:1h,24h,custom'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $identifier = $request->get('project');

        $project = $this->resolveAccessibleProject($user, $identifier);

        if (! $project instanceof Project) {
            return Response::error("Project [{$identifier}] not found or not accessible.");
        }

        $records = $project->records()
            ->ofType($request->get('type'))
            ->forPeriod($request->get('period', '24h'), $request->get('from'), $request->get('to'))
            ->orderByDesc('created_at')
            ->limit((int) $request->get('limit', 25))
            ->get(['id', 'type', 'payload', 'created_at'])
            ->map(fn ($record) => [
                'id' => $record->id,
                'type' => $record->type,
                'created_at' => $record->created_at,
                'payload' => PayloadSanitizer::sanitize((array) $record->payload),
            ]);

        return Response::json($records);
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'project' => $schema->string()->description('Project slug.')->required(),
            'type' => $schema->string()->description('Record type: request, exception, query, command, job-attempt, queued-job, scheduled-task, mail, cache, notification, outgoing-request.')->required(),
            'period' => $schema->string()->description('Time window: 1h, 24h, or custom (with from/to).')->enum(['1h', '24h', 'custom'])->default('24h'),
            'from' => $schema->string()->description('ISO start time when period=custom.'),
            'to' => $schema->string()->description('ISO end time when period=custom.'),
            'limit' => $schema->integer()->description('Max records (1-100).')->default(25),
        ];
    }
}
