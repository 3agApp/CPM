<?php

namespace App\Jobs;

use App\Ai\Agents\DocumentProcessor;
use App\Enums\ConversationStatus;
use App\Models\DocumentConversation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Files\Document;
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

        $supplier = $this->conversation->supplier;

        $prompt = $this->buildPrompt();

        $agent = new DocumentProcessor($supplier);

        $response = $agent->prompt(
            $prompt,
            attachments: [
                Document::fromStorage($this->conversation->stored_path),
            ],
            timeout: 300,
        );

        if ($response['needs_clarification']) {
            $this->conversation->update([
                'status' => ConversationStatus::NeedsContext,
                'ai_question' => $response['question'],
            ]);

            return;
        }

        $outputPath = 'outputs/'.$this->conversation->id.'.csv';
        Storage::put($outputPath, $response['csv_output']);

        $this->conversation->update([
            'status' => ConversationStatus::Completed,
            'output_path' => $outputPath,
        ]);
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
    }

    private function buildPrompt(): string
    {
        $prompt = "Process the attached product document and transform it into the standardized output CSV format.\n";
        $prompt .= "The source file is: {$this->conversation->original_filename}\n";

        if ($this->conversation->user_context) {
            $prompt .= "\nAdditional context provided by the user:\n{$this->conversation->user_context}\n";
        }

        return $prompt;
    }
}
