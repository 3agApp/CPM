<?php

use App\Ai\Agents\DocumentProcessor;
use App\Enums\ConversationStatus;
use App\Jobs\ProcessDocumentJob;
use App\Models\DocumentConversation;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake();
});

it('processes document and completes when ai returns csv output', function () {
    Storage::put('documents/test.csv', 'name,price');

    $conversation = DocumentConversation::factory()->create([
        'stored_path' => 'documents/test.csv',
        'original_filename' => 'test.csv',
    ]);

    DocumentProcessor::fake(function () {
        return [
            'needs_clarification' => false,
            'question' => null,
            'csv_output' => 'Artnr,Wg1,Wg2',
        ];
    });

    (new ProcessDocumentJob($conversation))->handle();

    $conversation->refresh();
    expect($conversation->status)->toBe(ConversationStatus::Completed)
        ->and($conversation->output_path)->not->toBeNull();

    Storage::assertExists($conversation->output_path);
});

it('sets status to needs_context when ai asks a question', function () {
    Storage::put('documents/test.csv', 'name,price');

    $conversation = DocumentConversation::factory()->create([
        'stored_path' => 'documents/test.csv',
        'original_filename' => 'test.csv',
    ]);

    DocumentProcessor::fake(function () {
        return [
            'needs_clarification' => true,
            'question' => 'What VAT rate should I use?',
            'csv_output' => null,
        ];
    });

    (new ProcessDocumentJob($conversation))->handle();

    $conversation->refresh();
    expect($conversation->status)->toBe(ConversationStatus::NeedsContext)
        ->and($conversation->ai_question)->toBe('What VAT rate should I use?');
});

it('includes user context in the prompt when provided', function () {
    Storage::put('documents/test.csv', 'name,price');

    $conversation = DocumentConversation::factory()->create([
        'stored_path' => 'documents/test.csv',
        'original_filename' => 'test.csv',
        'user_context' => 'The VAT rate is 8.1%',
    ]);

    DocumentProcessor::fake(function () {
        return [
            'needs_clarification' => false,
            'question' => null,
            'csv_output' => 'Artnr,Wg1,Wg2',
        ];
    });

    (new ProcessDocumentJob($conversation))->handle();

    DocumentProcessor::assertPrompted(function ($prompt) {
        return str_contains($prompt->prompt, 'The VAT rate is 8.1%')
            && str_contains($prompt->prompt, 'name,price');
    });
});

it('marks conversation as failed on exception', function () {
    Storage::put('documents/test.csv', 'name,price');

    $conversation = DocumentConversation::factory()->create([
        'stored_path' => 'documents/test.csv',
        'original_filename' => 'test.csv',
    ]);

    DocumentProcessor::fake(function () {
        throw new RuntimeException('AI provider error');
    });

    $job = new ProcessDocumentJob($conversation);

    try {
        $job->handle();
    } catch (RuntimeException) {
        $job->failed(new RuntimeException('AI provider error'));
    }

    $conversation->refresh();
    expect($conversation->status)->toBe(ConversationStatus::Failed)
        ->and($conversation->error_message)->toBe('AI provider error');
});

it('sets status to processing during execution', function () {
    Storage::put('documents/test.csv', 'name,price');

    $conversation = DocumentConversation::factory()->create([
        'stored_path' => 'documents/test.csv',
        'original_filename' => 'test.csv',
    ]);

    DocumentProcessor::fake(function () use ($conversation) {
        // During processing, the status should be 'processing'
        expect($conversation->fresh()->status)->toBe(ConversationStatus::Processing);

        return [
            'needs_clarification' => false,
            'question' => null,
            'csv_output' => 'Artnr,Wg1,Wg2',
        ];
    });

    (new ProcessDocumentJob($conversation))->handle();
});
