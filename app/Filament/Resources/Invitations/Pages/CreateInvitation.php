<?php

namespace App\Filament\Resources\Invitations\Pages;

use App\Filament\Resources\Invitations\InvitationResource;
use App\Mail\InvitationMail;
use App\Models\Invitation;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CreateInvitation extends CreateRecord
{
    protected static string $resource = InvitationResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $tenant = Filament::getTenant();

        $existingMember = $tenant->members()
            ->where('email', $data['email'])
            ->exists();

        if ($existingMember) {
            \Filament\Notifications\Notification::make()
                ->danger()
                ->title('User is already a member of this organization.')
                ->send();

            $this->halt();
        }

        $existingInvitation = Invitation::query()
            ->where('organization_id', $tenant->id)
            ->where('email', $data['email'])
            ->whereNull('accepted_at')
            ->first();

        /** @var Invitation|null $existingInvitation */
        if ($existingInvitation) {
            $existingInvitation->update([
                'role' => $data['role'],
                'token' => Str::random(64),
                'expires_at' => now()->addHours(48),
                'invited_by' => Filament::auth()->id(),
            ]);

            Mail::to($existingInvitation->email)->send(new InvitationMail($existingInvitation->fresh()));

            \Filament\Notifications\Notification::make()
                ->success()
                ->title('Invitation updated and resent')
                ->send();

            return $existingInvitation;
        }

        $invitation = Invitation::create([
            'organization_id' => $tenant->id,
            'email' => $data['email'],
            'role' => $data['role'],
            'token' => Str::random(64),
            'expires_at' => now()->addHours(48),
            'invited_by' => Filament::auth()->id(),
        ]);

        Mail::to($invitation->email)->send(new InvitationMail($invitation));

        return $invitation;
    }
}
