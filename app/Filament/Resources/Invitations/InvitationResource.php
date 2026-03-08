<?php

namespace App\Filament\Resources\Invitations;

use App\Filament\Resources\Invitations\Pages\CreateInvitation;
use App\Filament\Resources\Invitations\Pages\ListInvitations;
use App\Filament\Resources\Invitations\Schemas\InvitationForm;
use App\Filament\Resources\Invitations\Tables\InvitationsTable;
use App\Models\Invitation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class InvitationResource extends Resource
{
    protected static ?string $model = Invitation::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $navigationLabel = 'Invitations';

    public static function form(Schema $schema): Schema
    {
        return InvitationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InvitationsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInvitations::route('/'),
            'create' => CreateInvitation::route('/create'),
        ];
    }
}
