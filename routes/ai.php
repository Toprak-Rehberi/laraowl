<?php

use App\Mcp\Servers\LaraowlServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp', LaraowlServer::class)->middleware(['auth:sanctum']);
