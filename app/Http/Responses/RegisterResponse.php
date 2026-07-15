<?php

namespace App\Http\Responses;

use App\Actions\Teams\AcceptTeamInvitation;
use App\Models\Team;
use App\Models\TeamInvitation;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\URL;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Laravel\Fortify\Fortify;
use Symfony\Component\HttpFoundation\Response;

class RegisterResponse implements RegisterResponseContract
{
    public function __construct(private AcceptTeamInvitation $acceptTeamInvitation)
    {
        //
    }

    public function toResponse($request): Response
    {
        $user = $request->user();

        $invitedTeam = $this->acceptPendingInvitation($request);

        $team = $invitedTeam ?? $user?->currentTeam ?? $user?->personalTeam();

        if (! $team) {
            return redirect()->route('teams.create');
        }

        URL::defaults(['current_team' => $team->slug]);

        return $request->wantsJson()
            ? new JsonResponse(['two_factor' => false], 201)
            : redirect()->intended("/{$team->slug}".Fortify::redirects('register'));
    }

    /**
     * Accept the invitation stashed in the session by the accept route, when
     * the freshly registered user's email matches the invited address. The
     * invitation link was delivered to that mailbox, so a matching email also
     * proves ownership and lets us mark it as verified.
     */
    private function acceptPendingInvitation($request): ?Team
    {
        $user = $request->user();
        $code = $request->session()->pull('pending_invitation_code');

        if (! $code || ! $user) {
            return null;
        }

        $invitation = TeamInvitation::where('code', $code)->first();

        if (! $invitation || ! $invitation->isPending()) {
            return null;
        }

        if (strtolower($invitation->email) !== strtolower($user->email)) {
            return null;
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();

            event(new Verified($user));
        }

        return $this->acceptTeamInvitation->handle($user, $invitation);
    }
}
