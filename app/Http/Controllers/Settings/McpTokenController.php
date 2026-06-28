<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class McpTokenController extends Controller
{
    /**
     * Show the MCP tokens settings page.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('settings/mcp-tokens', [
            'tokens' => $request->user()->tokens()
                ->latest()
                ->get(['id', 'name', 'last_used_at', 'created_at']),
            'appUrl' => config('app.url'),
        ]);
    }

    /**
     * Create a new personal access token.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $token = $request->user()->createToken($validated['name']);

        return back()->with('mcpToken', $token->plainTextToken);
    }

    /**
     * Revoke a personal access token.
     */
    public function destroy(Request $request, string $token): RedirectResponse
    {
        $deleted = $request->user()->tokens()->where('id', $token)->delete();

        abort_if($deleted === 0, 404);

        return back();
    }
}
