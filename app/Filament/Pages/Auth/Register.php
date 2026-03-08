<?php

namespace App\Filament\Pages\Auth;

use App\Models\Invitation;
use Filament\Auth\Pages\Register as FilamentRegister;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

class Register extends FilamentRegister
{
    protected function afterRegister(): void
    {
        $token = session('pending_invitation_token');

        if (! $token) {
            return;
        }

        $invitation = Invitation::with('organization')
            ->where('token', $token)
            ->whereNull('accepted_at')
            ->first();

        /** @var Invitation|null $invitation */
        if (! $invitation) {
            session()->forget('pending_invitation_token');

            return;
        }

        if ($invitation->isExpired()) {
            session()->forget('pending_invitation_token');

            Notification::make()
                ->danger()
                ->title('This invitation has expired. Please request a new one.')
                ->send();

            return;
        }

        /** @var Model $user */
        $user = $this->getUser();

        if ($user->email !== $invitation->email) {
            Notification::make()
                ->warning()
                ->title('This invitation is for a different email address.')
                ->body('Sign in with the invited email address to join the organization.')
                ->send();

            return;
        }

        if (! $user->organizations()->whereKey($invitation->organization_id)->exists()) {
            $user->organizations()->attach($invitation->organization_id, [
                'role' => $invitation->role->value,
            ]);
        }

        $invitation->update(['accepted_at' => now()]);

        session()->forget('pending_invitation_token');
        session()->put('url.intended', route('filament.dashboard.pages.dashboard', [
            'tenant' => $invitation->organization->slug,
        ]));

        Notification::make()
            ->success()
            ->title('Invitation accepted.')
            ->body('Your account has been added to the organization.')
            ->send();
    }

    protected function getUser(): Model
    {
        return $this->form->model;
    }
}
