<?php

namespace App\Filament\Resources\Invitations\Tables;

use App\Mail\InvitationMail;
use App\Models\Invitation;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class InvitationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('role')
                    ->badge(),
                TextColumn::make('status')
                    ->badge()
                    ->getStateUsing(function (Invitation $record): string {
                        if ($record->isAccepted()) {
                            return 'Accepted';
                        }

                        if ($record->isExpired()) {
                            return 'Expired';
                        }

                        return 'Pending';
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Accepted' => 'success',
                        'Expired' => 'danger',
                        default => 'warning',
                    }),
                TextColumn::make('inviter.name')
                    ->label('Invited by')
                    ->placeholder('—'),
                TextColumn::make('expires_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No invitations found')
            ->emptyStateDescription('Invite your first teammate to this organization.')
            ->recordActions([
                Action::make('resend')
                    ->label('Resend')
                    ->icon('heroicon-o-arrow-path')
                    ->requiresConfirmation()
                    ->action(function (Invitation $record): void {
                        $record->update([
                            'token' => Str::random(64),
                            'expires_at' => now()->addHours(48),
                        ]);

                        Mail::to($record->email)->send(new InvitationMail($record));

                        Notification::make()
                            ->success()
                            ->title('Invitation resent')
                            ->send();
                    })
                    ->visible(fn (Invitation $record): bool => $record->isPending() || $record->isExpired()),
                DeleteAction::make()
                    ->label('Revoke'),
            ]);
    }
}
