<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InvitationAcceptController extends Controller
{
    public function __invoke(Request $request, string $token)
    {
        $invitation = Invitation::with('organization')
            ->where('token', $token)
            ->firstOrFail();

        if ($invitation->isAccepted()) {
            return redirect()->route('filament.dashboard.auth.login')
                ->with('warning', 'This invitation has already been accepted.');
        }

        if ($invitation->isExpired()) {
            return redirect()->route('filament.dashboard.auth.login')
                ->with('error', 'This invitation has expired. Please request a new one.');
        }

        $existingUser = User::where('email', $invitation->email)->first();

        if ($existingUser) {
            if (! $existingUser->organizations()->whereKey($invitation->organization_id)->exists()) {
                $existingUser->organizations()->attach($invitation->organization_id, [
                    'role' => $invitation->role->value,
                ]);
            }

            $invitation->update(['accepted_at' => now()]);

            if (Auth::check()) {
                return redirect()->route('filament.dashboard.pages.dashboard', [
                    'tenant' => $invitation->organization->slug,
                ]);
            }

            return redirect()->route('filament.dashboard.auth.login')
                ->with('success', 'Invitation accepted! Please log in to access the organization.');
        }

        session(['pending_invitation_token' => $token]);

        return redirect()->route('filament.dashboard.auth.register')
            ->with('info', 'Please create an account to join the organization.');
    }
}
