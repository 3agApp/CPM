<?php

namespace App\Filament\Resources\DocumentConversations\Tables;

use App\Enums\ConversationStatus;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DocumentConversationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('original_filename')
                    ->label('Filename')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->limit(40),
                TextColumn::make('brand.name')
                    ->label('Brand')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Uploaded by')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('ai_question')
                    ->label('AI Question')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Uploaded')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(ConversationStatus::class),
            ])
            ->emptyStateHeading('No document conversations')
            ->emptyStateDescription('Upload a document using the button above to start processing.')
            ->recordActions([
                Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => DocumentConversationsTable::getViewUrl($record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->accessSelectedRecords(),
                ]),
            ]);
    }

    private static function getViewUrl($record): string
    {
        return \App\Filament\Resources\DocumentConversations\DocumentConversationResource::getUrl('view', ['record' => $record]);
    }
}
