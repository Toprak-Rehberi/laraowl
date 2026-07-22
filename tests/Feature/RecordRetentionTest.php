<?php

use App\Models\Project;
use App\Models\Record;

test('records past the project retention window are pruned', function () {
    $project = Project::factory()->create(['retention_days' => 7]);

    $stale = Record::factory()->create([
        'project_id' => $project->id,
        'created_at' => now()->subDays(30),
    ]);

    $fresh = Record::factory()->create([
        'project_id' => $project->id,
        'created_at' => now()->subDay(),
    ]);

    $this->artisan('model:prune', ['--model' => [Record::class]])->assertExitCode(0);

    expect(Record::find($stale->id))->toBeNull()
        ->and(Record::find($fresh->id))->not->toBeNull();
});

test('a project with retention disabled keeps every record', function () {
    $project = Project::factory()->create(['retention_days' => 0]);

    $ancient = Record::factory()->create([
        'project_id' => $project->id,
        'created_at' => now()->subYears(2),
    ]);

    $this->artisan('model:prune', ['--model' => [Record::class]])->assertExitCode(0);

    expect(Record::find($ancient->id))->not->toBeNull();
});
