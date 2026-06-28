<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Record;
use Illuminate\Database\Eloquent\Factories\Factory;

class RecordFactory extends Factory
{
    protected $model = Record::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'issue_id' => null,
            'type' => 'request',
            'payload' => ['t' => 'request', 'method' => 'GET', 'path' => '/'],
            'fingerprint' => $this->faker->sha1(),
            'created_at' => now(),
        ];
    }
}
