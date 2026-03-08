<?php

namespace App\Jobs;

use App\Ai\Agents\DocumentProcessor;
use App\Enums\ConversationStatus;
use App\Models\DocumentConversation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use OpenSpout\Reader\CSV\Reader as CsvReader;
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

        $supplier = $this->conversation->supplier;

        $csvContent = $this->parseDocumentToCsv();

        $prompt = $this->buildPrompt($csvContent);

        $agent = new DocumentProcessor($supplier);

        $response = $agent->prompt(
            $prompt,
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

    private function parseDocumentToCsv(): string
    {
        $filePath = Storage::path($this->conversation->stored_path);
        $extension = strtolower(pathinfo($this->conversation->original_filename, PATHINFO_EXTENSION));

        $reader = match ($extension) {
            'xlsx', 'xls' => new XlsxReader,
            default => new CsvReader,
        };

        $reader->open($filePath);

        $rows = [];
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $cells = array_map(fn ($cell) => (string) $cell->getValue(), $row->getCells());
                $rows[] = $cells;
            }

            break; // Only process the first sheet
        }

        $reader->close();

        $output = fopen('php://memory', 'r+');
        foreach ($rows as $row) {
            fputcsv($output, $row);
        }
        rewind($output);
        $csvString = stream_get_contents($output);
        fclose($output);

        return $csvString;
    }

    private function buildPrompt(string $csvContent): string
    {
        $prompt = "Process the following product data and transform it into the standardized output CSV format.\n";
        $prompt .= "The source file is: {$this->conversation->original_filename}\n";

        if ($this->conversation->user_context) {
            $prompt .= "\nAdditional context provided by the user:\n{$this->conversation->user_context}\n";
        }

        $prompt .= "\n## Source Document Data (CSV)\n```csv\n{$csvContent}```\n";

        return $prompt;
    }
}
