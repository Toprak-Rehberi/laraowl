<?php

use App\Mcp\Servers\LaraowlServer;
use App\Mcp\Tools\ListProjects;
use App\Models\Project;
use App\Models\User;

it('lists only projects in the user\'s teams', function () {
    $user = User::factory()->create();
    $mine = Project::factory()->for($user->currentTeam)->create(['name' => 'Mine']);

    $other = User::factory()->create();
    Project::factory()->for($other->currentTeam)->create(['name' => 'Theirs']);

    LaraowlServer::actingAs($user)
        ->tool(ListProjects::class)
        ->assertOk()
        ->assertSee('Mine')
        ->assertDontSee('Theirs');
});
