<?php

namespace App\Mcp\Concerns;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait ResolvesAccessibleProjects
{
    /**
     * Projects belonging to any team the user is a member of.
     */
    protected function accessibleProjects(User $user): Builder
    {
        return Project::whereIn('team_id', $user->teams()->pluck('teams.id'));
    }

    /**
     * Resolve an accessible project by its slug.
     */
    protected function resolveAccessibleProject(User $user, string $slug): ?Project
    {
        return $this->accessibleProjects($user)->where('slug', $slug)->first();
    }
}
