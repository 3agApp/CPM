<?php

namespace App\Filament\Resources\DocumentConversations\Pages;

use App\Enums\ConversationStatus;
use App\Enums\MessageRole;
use App\Filament\Resources\DocumentConversations\DocumentConversationResource;
use App\Jobs\ProcessDocumentJob;
use App\Models\Brand;
use App\Models\DocumentConversation;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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
                    Select::make('brand_id')
                        ->label('Brand')
                        ->options(fn () => Brand::query()
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
                    Textarea::make('notes')
                        ->label('Additional Instructions')
                        ->placeholder('e.g. "VAT rate is 8.1%", "Ignore the first 5 rows", "Prices are in EUR"...')
                        ->rows(3),
                ])
                ->action(function (array $data): void {
                    /** @var TemporaryUploadedFile $file */
                    $file = $data['document'];
                    $storedPath = $file->store('documents', 'local');

                    $conversation = DocumentConversation::create([
                        'brand_id' => $data['brand_id'],
                        'user_id' => auth()->id(),
                        'status' => ConversationStatus::Pending,
                        'original_filename' => $file->getClientOriginalName(),
                        'stored_path' => $storedPath,
                    ]);

                    if (! empty($data['notes'])) {
                        $conversation->messages()->create([
                            'role' => MessageRole::User,
                            'content' => $data['notes'],
                        ]);
                    }

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
