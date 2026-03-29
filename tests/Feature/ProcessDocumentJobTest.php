<?php

use App\Ai\Agents\DocumentProcessor;
use App\Enums\ConversationStatus;
use App\Enums\MessageRole;
use App\Jobs\ProcessDocumentJob;
use App\Models\DocumentConversation;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Files\Document;

beforeEach(function () {
    Storage::fake();
});

it('marks every document processor response field as required', function () {
    $processor = new DocumentProcessor($this->createMock(\App\Models\Brand::class));

    $schema = (new JsonSchemaTypeFactory)
        ->object($processor->schema(new JsonSchemaTypeFactory))
        ->toArray();

    expect($schema['required'])
        ->toBe([
            'needs_clarification',
            'question',
            'csv_output',
        ])
        ->and($schema['properties']['question']['type'])->toBe('string')
        ->and($schema['properties']['csv_output']['type'])->toBe('string');
});

it('processes document and completes when ai returns csv output', function () {
    Storage::put('documents/test.csv', "name,price\nWidget,9.99");

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

it('stores completion message when processing succeeds', function () {
    Storage::put('documents/test.csv', "name,price\nWidget,9.99");

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

    $message = $conversation->messages()->latest()->first();
    expect($message->role)->toBe(MessageRole::Assistant)
        ->and($message->content)->toContain('completed');
});

it('sets status to needs_context when ai asks a question', function () {
    Storage::put('documents/test.csv', "name,price\nWidget,9.99");

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

    $message = $conversation->messages()->latest()->first();
    expect($message->role)->toBe(MessageRole::Assistant)
        ->and($message->content)->toBe('What VAT rate should I use?');
});

it('includes message history in the prompt', function () {
    Storage::put('documents/test.csv', "name,price\nWidget,9.99");

    $conversation = DocumentConversation::factory()->create([
        'stored_path' => 'documents/test.csv',
        'original_filename' => 'test.csv',
    ]);

    // Simulate a prior exchange
    $conversation->messages()->create([
        'role' => MessageRole::User,
        'content' => 'The VAT rate is 8.1%',
    ]);
    $conversation->messages()->create([
        'role' => MessageRole::Assistant,
        'content' => 'What is the default product group?',
    ]);
    $conversation->messages()->create([
        'role' => MessageRole::User,
        'content' => 'Use WG1=Electronics, WG2=Cables',
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
            && str_contains($prompt->prompt, 'What is the default product group?')
            && str_contains($prompt->prompt, 'Use WG1=Electronics, WG2=Cables')
            && str_contains($prompt->prompt, 'Conversation History')
            && $prompt->attachments->count() === 1;
    });
});

it('marks conversation as failed on exception', function () {
    Storage::put('documents/test.csv', "name,price\nWidget,9.99");

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
    Storage::put('documents/test.csv', "name,price\nWidget,9.99");

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

it('sends the document file as an attachment instead of inline data', function () {
    Storage::put('documents/test.csv', "name,price,note\nWidget,9.99,good\n,,");

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

    DocumentProcessor::assertPrompted(function ($prompt) {
        return $prompt->attachments->count() === 1
            && ! str_contains($prompt->prompt, '["name","price","note"]');
    });
});
