<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class IssueMcpToken extends Command
{
    protected $signature = 'laraowl:mcp-token {user : User id or email} {--name=mcp : Token name}';

    protected $description = 'Issue a Sanctum personal access token for MCP access';

    public function handle(): int
    {
        $identifier = (string) $this->argument('user');

        $user = User::where('id', $identifier)
            ->orWhere('email', $identifier)
            ->first();

        if (! $user) {
            $this->error("User [{$identifier}] not found.");

            return self::FAILURE;
        }

        $token = $user->createToken((string) $this->option('name'));

        $this->info("Token for {$user->email}:");
        $this->line($token->plainTextToken);
        $this->newLine();
        $this->comment('Store it now — it will not be shown again.');

        return self::SUCCESS;
    }
}
