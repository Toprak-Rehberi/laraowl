<?php

namespace App\Http\Controllers\Teams;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Teams\AcceptTeamInvitation;
use App\Enums\TeamRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teams\CreateTeamInvitationRequest;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Notifications\Teams\TeamInvitation as TeamInvitationNotification;
use App\Rules\ValidTeamInvitation;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Inertia\Response;

class TeamInvitationController extends Controller
{
    /**
     * Store a newly created invitation.
     */
    public function store(CreateTeamInvitationRequest $request, Team $team): RedirectResponse
    {
        Gate::authorize('inviteMember', $team);

        $invitation = $team->invitations()->create([
            'email' => $request->validated('email'),
            'role' => TeamRole::from($request->validated('role')),
            'invited_by' => $request->user()->id,
            'expires_at' => now()->addDays(3),
        ]);

        Notification::route('mail', $invitation->email)
            ->notify(new TeamInvitationNotification($invitation));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invitation sent.')]);

        return to_route('teams.edit', ['team' => $team->slug]);
    }

    /**
     * Cancel the specified invitation.
     */
    public function destroy(Team $team, TeamInvitation $invitation): RedirectResponse
    {
        abort_unless($invitation->team_id === $team->id, 404);

        Gate::authorize('cancelInvitation', $team);

        $invitation->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invitation cancelled.')]);

        return to_route('teams.edit', ['team' => $team->slug]);
    }

    /**
     * Accept the invitation.
     *
     * Guests are routed to registration (or login when an account already
     * exists for the invited email) with the invitation kept in the session,
     * so the acceptance completes right after they authenticate.
     */
    public function accept(Request $request, TeamInvitation $invitation, AcceptTeamInvitation $acceptTeamInvitation): RedirectResponse
    {
        $user = $request->user();

        if (! $user) {
            $request->session()->put('pending_invitation_code', $invitation->code);

            if (User::where('email', $invitation->email)->exists()) {
                return redirect()->guest(route('login'))
                    ->with('status', __('Log in to accept your team invitation.'));
            }

            // Fortify's /register may be disabled (ALLOW_REGISTRATION=false),
            // so invitees get their own invitation-scoped registration route.
            return redirect()->route('invitations.register', $invitation);
        }

        validator(
            ['invitation' => $invitation],
            ['invitation' => ['required', new ValidTeamInvitation($user)]],
        )->validate();

        $request->session()->forget('pending_invitation_code');

        $team = $acceptTeamInvitation->handle($user, $invitation);

        return redirect("/{$team->slug}/dashboard");
    }

    /**
     * Show the invitation-scoped registration form. Available even when
     * public registration is disabled: the invitation itself is the gate.
     */
    public function register(Request $request, TeamInvitation $invitation): Response|RedirectResponse
    {
        if ($request->user()) {
            return redirect()->route('invitations.accept', $invitation);
        }

        if (! $invitation->isPending() || User::where('email', $invitation->email)->exists()) {
            return redirect()->route('login')
                ->with('status', __('Log in to accept your team invitation.'));
        }

        return Inertia::render('auth/register', [
            'invitationEmail' => $invitation->email,
            'invitationTeam' => $invitation->team->name,
            'registerAction' => route('invitations.register.store', $invitation),
        ]);
    }

    /**
     * Register the invited user and accept the invitation in one step. The
     * email is taken from the invitation, never from the request, and counts
     * as verified: the invitation link was delivered to that mailbox.
     */
    public function storeUser(
        Request $request,
        TeamInvitation $invitation,
        CreateNewUser $createNewUser,
        AcceptTeamInvitation $acceptTeamInvitation,
    ): RedirectResponse {
        abort_unless($invitation->isPending(), 403);

        try {
            $user = $createNewUser->create([
                'name' => (string) $request->input('name'),
                'email' => $invitation->email,
                'password' => (string) $request->input('password'),
                'password_confirmation' => (string) $request->input('password_confirmation'),
            ]);
        } catch (UniqueConstraintViolationException) {
            // Parallel submits for the same invitation: the account now
            // exists, so route the invitee through the login flow instead.
            return redirect()->guest(route('login'))
                ->with('status', __('Log in to accept your team invitation.'));
        }

        event(new Registered($user));

        $user->markEmailAsVerified();

        event(new Verified($user));

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->forget('pending_invitation_code');

        $team = $acceptTeamInvitation->handle($user, $invitation);

        return redirect("/{$team->slug}/dashboard");
    }
}
