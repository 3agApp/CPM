<?php

namespace App\Filament\Resources\DocumentConversations;

use App\Filament\Resources\DocumentConversations\Pages\ListDocumentConversations;
use App\Filament\Resources\DocumentConversations\Pages\ViewDocumentConversation;
use App\Filament\Resources\DocumentConversations\RelationManagers\ProductsRelationManager;
use App\Filament\Resources\DocumentConversations\Tables\DocumentConversationsTable;
use App\Models\DocumentConversation;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DocumentConversationResource extends Resource
{
    protected static ?string $model = DocumentConversation::class;

    protected static ?string $recordTitleAttribute = 'original_filename';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Document Conversations';

    protected static ?string $modelLabel = 'Conversation';

    protected static ?string $pluralModelLabel = 'Document Conversations';

    protected static bool $isScopedToTenant = false;

    public static function table(Table $table): Table
    {
        return DocumentConversationsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $tenant = Filament::getTenant();

        return parent::getEloquentQuery()
            ->whereHas('brand', fn (Builder $query) => $query
                ->where('organization_id', $tenant?->getKey()));
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getRelations(): array
    {
        return [
            ProductsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDocumentConversations::route('/'),
            'view' => ViewDocumentConversation::route('/{record}'),
        ];
    }
}
