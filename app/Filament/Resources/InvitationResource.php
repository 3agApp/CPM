<?php

namespace App\Filament\Resources;

use App\Enums\Role;
use App\Filament\Resources\InvitationResource\Pages;
use App\Mail\InvitationMail;
use App\Models\Invitation;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class InvitationResource extends Resource
{
    protected static ?string $model = Invitation::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $navigationLabel = 'Invitations';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                Select::make('role')
                    ->options(fn () => collect([Role::Admin, Role::Member])
                        ->mapWithKeys(fn (Role $role) => [$role->value => $role->getLabel()]))
                    ->default(Role::Member->value)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('email')
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
                    ->label('Invited By')
                    ->placeholder('—'),
                TextColumn::make('expires_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                \Filament\Actions\Action::make('resend')
                    ->label('Resend')
                    ->icon('heroicon-o-arrow-path')
                    ->requiresConfirmation()
                    ->action(function (Invitation $record): void {
                        $record->update([
                            'token' => Str::random(64),
                            'expires_at' => now()->addHours(48),
                        ]);

                        Mail::to($record->email)->send(new InvitationMail($record));

                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Invitation resent')
                            ->send();
                    })
                    ->visible(fn (Invitation $record): bool => $record->isPending() || $record->isExpired()),
                \Filament\Actions\DeleteAction::make()
                    ->label('Revoke'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvitations::route('/'),
            'create' => Pages\CreateInvitation::route('/create'),
        ];
    }
}
