<?php

namespace App\Filament\Resources\Invitations\Schemas;

use App\Enums\Role;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InvitationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Invitation information')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('email')
                            ->label('Email address')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->placeholder('user@example.com'),
                        Select::make('role')
                            ->options(fn () => collect([Role::Admin, Role::Member])
                                ->mapWithKeys(fn (Role $role) => [$role->value => $role->getLabel()]))
                            ->default(Role::Member->value)
                            ->native(false)
                            ->required(),
                    ]),
            ]);
    }
}
