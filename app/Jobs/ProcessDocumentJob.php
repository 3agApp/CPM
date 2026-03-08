<?php

namespace App\Jobs;

use App\Ai\Agents\DocumentProcessor;
use App\Enums\ConversationStatus;
use App\Models\DocumentConversation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use OpenSpout\Common\Entity\Cell\FormulaCell;
use OpenSpout\Reader\CSV\Reader as CsvReader;
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

        $supplier = $this->conversation->supplier;

        $documentData = $this->parseDocument();

        $prompt = $this->buildPrompt($documentData);

        $agent = new DocumentProcessor($supplier);

        $response = $agent->prompt(
            $prompt,
            timeout: 600,
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

    private function parseDocument(): string
    {
        $filePath = Storage::path($this->conversation->stored_path);
        $extension = strtolower(pathinfo($this->conversation->original_filename, PATHINFO_EXTENSION));

        $reader = match ($extension) {
            'xlsx', 'xls' => $this->createXlsxReader(),
            default => new CsvReader,
        };

        $reader->open($filePath);

        $rows = [];

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $cells = array_map(function ($cell) {
                    if ($cell instanceof FormulaCell) {
                        $computed = $cell->getComputedValue();

                        return $computed !== null ? (string) $computed : '';
                    }

                    return (string) $cell->getValue();
                }, $row->getCells());

                // Skip completely empty rows
                if (array_filter($cells, fn ($v) => $v !== '') === []) {
                    continue;
                }

                $rows[] = $cells;
            }

            break; // Only process the first sheet
        }

        $reader->close();

        return json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function createXlsxReader(): XlsxReader
    {
        $options = new XlsxOptions;
        $options->SHOULD_FORMAT_DATES = true;
        $options->SHOULD_PRESERVE_EMPTY_ROWS = false;

        return new XlsxReader($options);
    }

    private function buildPrompt(string $documentData): string
    {
        $prompt = "Process the following product data and transform it into the standardized output CSV format.\n";
        $prompt .= "The source file is: {$this->conversation->original_filename}\n";

        if ($this->conversation->user_context) {
            $prompt .= "\nAdditional context provided by the user:\n{$this->conversation->user_context}\n";
        }

        $prompt .= "\n## Source Document Data (JSON)\nThe data is a 2D array where each inner array is a row from the spreadsheet. The first non-empty row is typically the header row, but you must determine the actual structure by examining the content. Some files may have metadata rows before the actual data table.\n```json\n{$documentData}\n```\n";

        return $prompt;
    }
}
