<?php

namespace App\Filament\Resources\DocumentConversations\Pages;

use App\Enums\ConversationStatus;
use App\Filament\Resources\DocumentConversations\DocumentConversationResource;
use App\Jobs\ProcessDocumentJob;
use App\Models\DocumentConversation;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;

class ViewDocumentConversation extends ViewRecord
{
    protected static string $resource = DocumentConversationResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Document Details')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextEntry::make('original_filename')
                            ->label('Filename'),
                        TextEntry::make('status')
                            ->badge(),
                        TextEntry::make('supplier.name')
                            ->label('Supplier'),
                        TextEntry::make('user.name')
                            ->label('Uploaded by'),
                        TextEntry::make('created_at')
                            ->label('Uploaded at')
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->label('Last updated')
                            ->dateTime(),
                    ]),
                Section::make('AI Question')
                    ->columnSpanFull()
                    ->visible(fn (DocumentConversation $record): bool => $record->needsContext())
                    ->schema([
                        TextEntry::make('ai_question')
                            ->label('The AI needs more information')
                            ->columnSpanFull(),
                    ]),
                Section::make('User Context')
                    ->columnSpanFull()
                    ->visible(fn (DocumentConversation $record): bool => $record->user_context !== null)
                    ->schema([
                        TextEntry::make('user_context')
                            ->label('Context provided')
                            ->columnSpanFull(),
                    ]),
                Section::make('Error')
                    ->columnSpanFull()
                    ->visible(fn (DocumentConversation $record): bool => $record->isFailed())
                    ->schema([
                        TextEntry::make('error_message')
                            ->label('Error details')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('provideContext')
                ->label('Provide Context')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color('warning')
                ->form([
                    Textarea::make('context')
                        ->label('Additional context')
                        ->required()
                        ->rows(4)
                        ->placeholder('Provide the information the AI is asking for...'),
                ])
                ->action(function (DocumentConversation $record, array $data): void {
                    $record->update([
                        'user_context' => $data['context'],
                        'ai_question' => null,
                        'status' => ConversationStatus::Pending,
                    ]);

                    ProcessDocumentJob::dispatch($record);

                    Notification::make()
                        ->success()
                        ->title('Context provided')
                        ->body('The document will be reprocessed with the additional context.')
                        ->send();
                })
                ->visible(fn (DocumentConversation $record): bool => $record->needsContext()),

            Action::make('retry')
                ->label('Retry')
                ->icon('heroicon-o-arrow-path')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function (DocumentConversation $record): void {
                    $record->update([
                        'status' => ConversationStatus::Pending,
                        'error_message' => null,
                    ]);

                    ProcessDocumentJob::dispatch($record);

                    Notification::make()
                        ->success()
                        ->title('Retrying')
                        ->body('The document processing has been requeued.')
                        ->send();
                })
                ->visible(fn (DocumentConversation $record): bool => $record->isFailed()),

            Action::make('download')
                ->label('Download Output')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function (DocumentConversation $record) {
                    return Storage::download($record->output_path, 'output.csv');
                })
                ->visible(fn (DocumentConversation $record): bool => $record->isCompleted() && $record->output_path !== null),
        ];
    }
}
