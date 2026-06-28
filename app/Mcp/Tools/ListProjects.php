<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\ResolvesAccessibleProjects;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('List the projects (monitored applications) the current user can access, across all their teams.')]
class ListProjects extends Tool
{
    use ResolvesAccessibleProjects;

    public function handle(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $projects = $this->accessibleProjects($user)
            ->get(['team_id', 'name', 'slug'])
            ->map(fn ($project) => [
                'team_id' => $project->team_id,
                'name' => $project->name,
                'slug' => $project->slug,
            ]);

        return Response::json($projects);
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
