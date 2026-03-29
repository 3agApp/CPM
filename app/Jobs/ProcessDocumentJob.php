<?php

namespace App\Jobs;

use App\Ai\Agents\DocumentProcessor;
use App\Enums\ConversationStatus;
use App\Enums\MessageRole;
use App\Filament\Resources\DocumentConversations\DocumentConversationResource;
use App\Models\DocumentConversation;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Files\Document;
use Laravel\Ai\Responses\StructuredAgentResponse;
use OpenSpout\Common\Entity\Cell\FormulaCell;
use OpenSpout\Reader\XLSX\Options as XlsxOptions;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use Throwable;

class ProcessDocumentJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 600;

    public function __construct(public DocumentConversation $conversation) {}

    public function handle(): void
    {
        $this->conversation->update(['status' => ConversationStatus::Processing]);

        $brand = $this->conversation->brand;

        $documentPath = $this->getDocumentPath();

        $prompt = $this->buildPrompt();

        $agent = new DocumentProcessor($brand);

        $response = $agent->prompt(
            $prompt,
            attachments: [
                Document::fromPath($documentPath),
            ],
            timeout: 600,
        );

        if (! $response instanceof StructuredAgentResponse) {
            throw new \RuntimeException('Document processor must return a structured response.');
        }

        $response = $response->toArray();

        if ($response['needs_clarification']) {
            $this->conversation->messages()->create([
                'role' => MessageRole::Assistant,
                'content' => $response['question'],
            ]);

            $this->conversation->update([
                'status' => ConversationStatus::NeedsContext,
                'ai_question' => $response['question'],
            ]);

            $this->notifyUser(
                'AI needs more information',
                "The AI has a question about \"{$this->conversation->original_filename}\".",
                'warning',
            );

            return;
        }

        $this->conversation->products()->delete();

        foreach ($response['products'] as $product) {
            $this->conversation->products()->create(
                array_map(fn ($value) => $value === '' ? null : $value, $product)
            );
        }

        $this->conversation->messages()->create([
            'role' => MessageRole::Assistant,
            'content' => 'Processing completed. '.count($response['products']).' products have been saved.',
        ]);

        $this->conversation->update([
            'status' => ConversationStatus::Completed,
        ]);

        $this->notifyUser(
            'Document processed successfully',
            "The file \"{$this->conversation->original_filename}\" has been processed. ".count($response['products']).' products saved.',
            'success',
        );
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Document processing failed', [
            'conversation_id' => $this->conversation->id,
            'error' => $exception->getMessage(),
        ]);

        $this->conversation->update([
            'status' => ConversationStatus::Failed,
            'error_message' => $exception->getMessage(),
        ]);

        $this->notifyUser(
            'Document processing failed',
            "Failed to process \"{$this->conversation->original_filename}\": {$exception->getMessage()}",
            'danger',
        );
    }

    private function notifyUser(string $title, string $body, string $color): void
    {
        $user = $this->conversation->user;

        if (! $user) {
            return;
        }

        $notification = Notification::make()
            ->title($title)
            ->body($body)
            ->{$color}()
            ->actions([
                \Filament\Actions\Action::make('view')
                    ->label('View')
                    ->url(DocumentConversationResource::getUrl('view', [
                        'record' => $this->conversation,
                        'tenant' => $this->conversation->brand->organization,
                    ])),
            ]);

        $notification->sendToDatabase($user);
    }

    private function getDocumentPath(): string
    {
        $extension = strtolower(pathinfo($this->conversation->original_filename, PATHINFO_EXTENSION));

        if (in_array($extension, ['xlsx', 'xls'])) {
            return $this->convertToCsv();
        }

        return Storage::path($this->conversation->stored_path);
    }

    private function convertToCsv(): string
    {
        $filePath = Storage::path($this->conversation->stored_path);

        Storage::makeDirectory('temp');
        $csvPath = Storage::path('temp/'.$this->conversation->id.'.csv');

        $reader = $this->createXlsxReader();
        $reader->open($filePath);

        $handle = fopen($csvPath, 'w');

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $cells = array_map(function ($cell) {
                    if ($cell instanceof FormulaCell) {
                        $computed = $cell->getComputedValue();

                        return $computed !== null ? (string) $computed : '';
                    }

                    return (string) $cell->getValue();
                }, $row->getCells());

                if (array_filter($cells, fn ($v) => $v !== '') === []) {
                    continue;
                }

                fputcsv($handle, $cells);
            }

            break; // Only process the first sheet
        }

        fclose($handle);
        $reader->close();

        return $csvPath;
    }

    private function createXlsxReader(): XlsxReader
    {
        $options = new XlsxOptions;
        $options->SHOULD_FORMAT_DATES = true;
        $options->SHOULD_PRESERVE_EMPTY_ROWS = false;

        return new XlsxReader($options);
    }

    private function buildPrompt(): string
    {
        $prompt = "Process the attached product data file and transform it into the standardized output CSV format.\n";
        $prompt .= "The source file is: {$this->conversation->original_filename}\n";

        $messages = $this->conversation->messages()->orderBy('created_at')->get();

        if ($messages->isNotEmpty()) {
            $prompt .= "\n## Conversation History\n";
            $prompt .= "Below is the full history of this conversation. Apply ALL user instructions cumulatively.\n\n";

            foreach ($messages as $message) {
                $roleLabel = $message->role === MessageRole::User ? 'User' : 'Assistant';
                $prompt .= "**{$roleLabel}**: {$message->content}\n\n";
            }
        }

        $prompt .= "\nThe source document is attached as a CSV file. The first non-empty row is typically the header row, but you must determine the actual structure by examining the content. Some files may have metadata rows before the actual data table.\n";

        return $prompt;
    }
}
