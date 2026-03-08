<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InvitationAcceptController extends Controller
{
    public function __invoke(Request $request, string $token): RedirectResponse
    {
        $invitation = Invitation::with('organization')
            ->where('token', $token)
            ->firstOrFail();

        if ($invitation->isAccepted()) {
            Notification::make()
                ->warning()
                ->title('This invitation has already been accepted.')
                ->send();

            return redirect()->route('filament.dashboard.auth.login');
        }

        if ($invitation->isExpired()) {
            Notification::make()
                ->danger()
                ->title('This invitation has expired. Please request a new one.')
                ->send();

            return redirect()->route('filament.dashboard.auth.login');
        }

        $existingUser = User::where('email', $invitation->email)->first();

        if ($existingUser) {
            if (Auth::check() && Auth::id() !== $existingUser->id) {
                Notification::make()
                    ->warning()
                    ->title('Sign in with the invited account to accept this invitation.')
                    ->send();

                return redirect()->route('filament.dashboard.auth.login');
            }

            if (! $existingUser->organizations()->whereKey($invitation->organization_id)->exists()) {
                $existingUser->organizations()->attach($invitation->organization_id, [
                    'role' => $invitation->role->value,
                ]);
            }

            $invitation->update(['accepted_at' => now()]);

            Notification::make()
                ->success()
                ->title('Invitation accepted.')
                ->send();

            if (Auth::check()) {
                return redirect()->route('filament.dashboard.pages.dashboard', [
                    'tenant' => $invitation->organization->slug,
                ]);
            }

            return redirect()->route('filament.dashboard.auth.login');
        }

        session(['pending_invitation_token' => $token]);

        Notification::make()
            ->info()
            ->title('Please create an account to join the organization.')
            ->send();

        return redirect()->route('filament.dashboard.auth.register');
    }
}
