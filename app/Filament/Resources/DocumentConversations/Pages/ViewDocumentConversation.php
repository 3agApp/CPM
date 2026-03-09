<?php

namespace App\Filament\Resources\DocumentConversations\Pages;

use App\Enums\ConversationStatus;
use App\Enums\MessageRole;
use App\Filament\Resources\DocumentConversations\DocumentConversationResource;
use App\Jobs\ProcessDocumentJob;
use App\Models\DocumentConversation;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
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
                Section::make('Conversation')
                    ->columnSpanFull()
                    ->schema([
                        View::make('filament.schemas.components.message-timeline'),
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
            Action::make('sendMessage')
                ->label(fn (DocumentConversation $record): string => $record->needsContext()
                    ? 'Reply to AI'
                    : 'Request Changes')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color(fn (DocumentConversation $record): string => $record->needsContext()
                    ? 'warning'
                    : 'info')
                ->form([
                    Textarea::make('message')
                        ->label('Message')
                        ->required()
                        ->rows(4)
                        ->placeholder(fn (DocumentConversation $record): string => $record->needsContext()
                            ? 'Answer the AI\'s question...'
                            : 'Describe the changes you want...'),
                ])
                ->action(function (DocumentConversation $record, array $data): void {
                    $record->messages()->create([
                        'role' => MessageRole::User,
                        'content' => $data['message'],
                    ]);

                    $record->update([
                        'ai_question' => null,
                        'status' => ConversationStatus::Pending,
                    ]);

                    ProcessDocumentJob::dispatch($record);

                    Notification::make()
                        ->success()
                        ->title('Message sent')
                        ->body('The document will be reprocessed with your feedback.')
                        ->send();
                })
                ->visible(fn (DocumentConversation $record): bool => $record->needsContext() || $record->isCompleted()),

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
