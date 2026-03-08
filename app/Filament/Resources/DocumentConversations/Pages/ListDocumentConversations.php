<?php

namespace App\Filament\Resources\DocumentConversations\Pages;

use App\Enums\ConversationStatus;
use App\Filament\Resources\DocumentConversations\DocumentConversationResource;
use App\Jobs\ProcessDocumentJob;
use App\Models\DocumentConversation;
use App\Models\Supplier;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ListDocumentConversations extends ListRecords
{
    protected static string $resource = DocumentConversationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('upload')
                ->label('Upload Document')
                ->icon('heroicon-o-arrow-up-tray')
                ->form([
                    Select::make('supplier_id')
                        ->label('Supplier')
                        ->options(fn () => Supplier::query()
                            ->where('organization_id', Filament::getTenant()->id)
                            ->pluck('name', 'id'))
                        ->required()
                        ->native(false)
                        ->searchable(),
                    FileUpload::make('document')
                        ->label('Document')
                        ->required()
                        ->acceptedFileTypes([
                            'text/csv',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                            'text/plain',
                        ])
                        ->maxSize(10240)
                        ->storeFiles(false),
                ])
                ->action(function (array $data): void {
                    /** @var TemporaryUploadedFile $file */
                    $file = $data['document'];
                    $storedPath = $file->store('documents', 'local');

                    $conversation = DocumentConversation::create([
                        'supplier_id' => $data['supplier_id'],
                        'user_id' => auth()->id(),
                        'status' => ConversationStatus::Pending,
                        'original_filename' => $file->getClientOriginalName(),
                        'stored_path' => $storedPath,
                    ]);

                    ProcessDocumentJob::dispatch($conversation);

                    Notification::make()
                        ->success()
                        ->title('Document uploaded')
                        ->body('Processing has started. You will be notified when it completes.')
                        ->send();
                }),
        ];
    }
}
