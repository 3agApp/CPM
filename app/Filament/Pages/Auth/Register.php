<?php

namespace App\Filament\Pages\Auth;

use App\Models\Invitation;
use Filament\Auth\Pages\Register as FilamentRegister;
use Illuminate\Database\Eloquent\Model;

class Register extends FilamentRegister
{
    protected function afterRegister(): void
    {
        $token = session()->pull('pending_invitation_token');

        if (! $token) {
            return;
        }

        $invitation = Invitation::with('organization')
            ->where('token', $token)
            ->whereNull('accepted_at')
            ->first();

        if (! $invitation || $invitation->isExpired()) {
            return;
        }

        /** @var Model $user */
        $user = $this->getUser();

        if ($user->email !== $invitation->email) {
            return;
        }

        $user->organizations()->attach($invitation->organization_id, [
            'role' => $invitation->role->value,
        ]);

        $invitation->update(['accepted_at' => now()]);
    }

    protected function getUser(): Model
    {
        return $this->form->model;
    }
}
