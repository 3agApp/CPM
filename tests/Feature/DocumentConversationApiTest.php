<?php

use App\Enums\ConversationStatus;
use App\Jobs\ProcessDocumentJob;
use App\Models\DocumentConversation;
use App\Models\Organization;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Storage::fake();
    Queue::fake();

    $this->user = User::factory()->create();
    $this->organization = Organization::factory()->create();
    $this->organization->members()->attach($this->user, ['role' => 'owner']);
    $this->supplier = Supplier::factory()->create(['organization_id' => $this->organization->id]);

    Sanctum::actingAs($this->user);
});

it('uploads a document and returns a conversation id', function () {
    $file = UploadedFile::fake()->create('products.csv', 100, 'text/csv');

    $response = $this->postJson('/api/v1/conversations', [
        'supplier_id' => $this->supplier->id,
        'document' => $file,
    ]);

    $response->assertCreated()
        ->assertJsonStructure([
            'data' => ['id', 'supplier_id', 'status', 'status_label', 'original_filename', 'created_at'],
        ]);

    expect($response->json('data.status'))->toBe('pending')
        ->and($response->json('data.original_filename'))->toBe('products.csv');

    Queue::assertPushed(ProcessDocumentJob::class);
});

it('rejects invalid file types', function () {
    $file = UploadedFile::fake()->create('products.pdf', 100, 'application/pdf');

    $response = $this->postJson('/api/v1/conversations', [
        'supplier_id' => $this->supplier->id,
        'document' => $file,
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['document']);
});

it('requires authentication', function () {
    Sanctum::actingAs(User::factory()->create());

    // Reset auth to test unauthenticated
    $this->app['auth']->forgetGuards();

    $response = $this->withHeaders(['Authorization' => ''])->postJson('/api/v1/conversations', [
        'supplier_id' => $this->supplier->id,
    ]);

    $response->assertUnauthorized();
});

it('shows a conversation by id', function () {
    $conversation = DocumentConversation::factory()->create([
        'supplier_id' => $this->supplier->id,
        'user_id' => $this->user->id,
    ]);

    $response = $this->getJson("/api/v1/conversations/{$conversation->id}");

    $response->assertSuccessful()
        ->assertJsonPath('data.id', $conversation->id)
        ->assertJsonPath('data.status', 'pending');
});

it('shows ai question when conversation needs context', function () {
    $conversation = DocumentConversation::factory()->needsContext()->create([
        'supplier_id' => $this->supplier->id,
        'user_id' => $this->user->id,
        'ai_question' => 'What is the VAT rate for these products?',
    ]);

    $response = $this->getJson("/api/v1/conversations/{$conversation->id}");

    $response->assertSuccessful()
        ->assertJsonPath('data.status', 'needs_context')
        ->assertJsonPath('data.ai_question', 'What is the VAT rate for these products?');
});

it('provides context and re-dispatches processing', function () {
    $conversation = DocumentConversation::factory()->needsContext()->create([
        'supplier_id' => $this->supplier->id,
        'user_id' => $this->user->id,
    ]);

    $response = $this->postJson("/api/v1/conversations/{$conversation->id}/context", [
        'context' => 'The VAT rate is 8.1%',
    ]);

    $response->assertSuccessful();

    $conversation->refresh();
    expect($conversation->status)->toBe(ConversationStatus::Pending)
        ->and($conversation->user_context)->toBe('The VAT rate is 8.1%')
        ->and($conversation->ai_question)->toBeNull();

    Queue::assertPushed(ProcessDocumentJob::class);
});

it('rejects context when conversation is not awaiting it', function () {
    $conversation = DocumentConversation::factory()->processing()->create([
        'supplier_id' => $this->supplier->id,
        'user_id' => $this->user->id,
    ]);

    $response = $this->postJson("/api/v1/conversations/{$conversation->id}/context", [
        'context' => 'Some context',
    ]);

    $response->assertUnprocessable();
});

it('downloads output when completed', function () {
    Storage::put('outputs/test-output.csv', 'Artnr,Wg1,Wg2');

    $conversation = DocumentConversation::factory()->completed()->create([
        'supplier_id' => $this->supplier->id,
        'user_id' => $this->user->id,
        'output_path' => 'outputs/test-output.csv',
    ]);

    $response = $this->get("/api/v1/conversations/{$conversation->id}/download");

    $response->assertSuccessful();
});

it('returns 404 when downloading incomplete conversation', function () {
    $conversation = DocumentConversation::factory()->processing()->create([
        'supplier_id' => $this->supplier->id,
        'user_id' => $this->user->id,
    ]);

    $response = $this->getJson("/api/v1/conversations/{$conversation->id}/download");

    $response->assertNotFound();
});

it('requires a valid supplier_id', function () {
    $file = UploadedFile::fake()->create('products.csv', 100, 'text/csv');

    $response = $this->postJson('/api/v1/conversations', [
        'supplier_id' => 99999,
        'document' => $file,
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['supplier_id']);
});

it('requires context field when providing context', function () {
    $conversation = DocumentConversation::factory()->needsContext()->create([
        'supplier_id' => $this->supplier->id,
        'user_id' => $this->user->id,
    ]);

    $response = $this->postJson("/api/v1/conversations/{$conversation->id}/context", []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['context']);
});
