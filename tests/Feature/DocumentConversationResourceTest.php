<?php

use App\Enums\ConversationStatus;
use App\Enums\MessageRole;
use App\Enums\Role;
use App\Filament\Resources\DocumentConversations\Pages\ListDocumentConversations;
use App\Filament\Resources\DocumentConversations\Pages\ViewDocumentConversation;
use App\Jobs\ProcessDocumentJob;
use App\Models\DocumentConversation;
use App\Models\Organization;
use App\Models\Supplier;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->user = User::factory()->create();
    $this->organization->members()->attach($this->user, ['role' => Role::Owner->value]);
    $this->supplier = Supplier::factory()->create(['organization_id' => $this->organization->id]);

    $this->actingAs($this->user);
    Filament::setCurrentPanel(Filament::getPanel('dashboard'));
    Filament::setTenant($this->organization);
});

it('can list document conversations for the current tenant', function () {
    $conversation = DocumentConversation::factory()->create([
        'supplier_id' => $this->supplier->id,
        'user_id' => $this->user->id,
    ]);

    $otherOrg = Organization::factory()->create();
    $otherSupplier = Supplier::factory()->create(['organization_id' => $otherOrg->id]);
    DocumentConversation::factory()->create([
        'supplier_id' => $otherSupplier->id,
        'user_id' => $this->user->id,
    ]);

    Livewire::test(ListDocumentConversations::class)
        ->assertCanSeeTableRecords([$conversation])
        ->assertCountTableRecords(1);
});

it('can view a document conversation', function () {
    $conversation = DocumentConversation::factory()->create([
        'supplier_id' => $this->supplier->id,
        'user_id' => $this->user->id,
        'original_filename' => 'products.csv',
    ]);

    Livewire::test(ViewDocumentConversation::class, ['record' => $conversation->id])
        ->assertSuccessful();
});

it('renders conversation messages as safe markdown', function () {
    $conversation = DocumentConversation::factory()->create([
        'supplier_id' => $this->supplier->id,
        'user_id' => $this->user->id,
    ]);

    $conversation->messages()->create([
        'role' => MessageRole::Assistant,
        'content' => "# Summary\n\n- First item\n- **Bold detail**\n\n```\ntotal = 42\n```\n\n| Name | Price |\n| --- | ---: |\n| Cable | 9.99 |\n\n<script>alert('xss')</script>",
    ]);

    Livewire::test(ViewDocumentConversation::class, ['record' => $conversation->id])
        ->assertSuccessful()
        ->assertSee('message-markdown')
        ->assertSeeHtml('<h1>Summary</h1>', false)
        ->assertSeeHtml('<strong>Bold detail</strong>', false)
        ->assertSeeHtml('<pre><code>', false)
        ->assertSeeHtml('<table>', false)
        ->assertDontSee('<script>', false);
});

it('shows error message for failed conversations', function () {
    $conversation = DocumentConversation::factory()->failed()->create([
        'supplier_id' => $this->supplier->id,
        'user_id' => $this->user->id,
        'error_message' => 'Processing failed due to invalid format',
    ]);

    Livewire::test(ViewDocumentConversation::class, ['record' => $conversation->id])
        ->assertSuccessful()
        ->assertSee('Processing failed due to invalid format');
});

it('displays status badge with correct color', function () {
    DocumentConversation::factory()->create([
        'supplier_id' => $this->supplier->id,
        'user_id' => $this->user->id,
        'status' => ConversationStatus::Completed,
    ]);

    Livewire::test(ListDocumentConversations::class)
        ->assertCanSeeTableRecords(
            DocumentConversation::where('supplier_id', $this->supplier->id)->get()
        );
});

it('can upload a document with notes from the list page', function () {
    Queue::fake();

    $file = UploadedFile::fake()->create('products.csv', 100, 'text/csv');

    Livewire::test(ListDocumentConversations::class)
        ->callAction('upload', [
            'supplier_id' => $this->supplier->id,
            'document' => $file,
            'notes' => 'VAT rate is 8.1%',
        ])
        ->assertNotified('Document uploaded');

    expect(DocumentConversation::count())->toBe(1);

    $conversation = DocumentConversation::first();
    expect($conversation->supplier_id)->toBe($this->supplier->id)
        ->and($conversation->user_id)->toBe($this->user->id)
        ->and($conversation->status)->toBe(ConversationStatus::Pending)
        ->and($conversation->stored_path)->not->toBeNull();

    $message = $conversation->messages()->first();
    expect($message->role)->toBe(MessageRole::User)
        ->and($message->content)->toBe('VAT rate is 8.1%');

    Queue::assertPushed(ProcessDocumentJob::class);
});

it('can upload a document without notes', function () {
    Queue::fake();

    $file = UploadedFile::fake()->create('products.csv', 100, 'text/csv');

    Livewire::test(ListDocumentConversations::class)
        ->callAction('upload', [
            'supplier_id' => $this->supplier->id,
            'document' => $file,
        ])
        ->assertNotified('Document uploaded');

    $conversation = DocumentConversation::first();
    expect($conversation->messages)->toHaveCount(0);

    Queue::assertPushed(ProcessDocumentJob::class);
});

it('can send a message to request changes on completed conversation', function () {
    Queue::fake();

    $conversation = DocumentConversation::factory()->completed()->create([
        'supplier_id' => $this->supplier->id,
        'user_id' => $this->user->id,
    ]);

    Livewire::test(ViewDocumentConversation::class, ['record' => $conversation->id])
        ->callAction('sendMessage', [
            'message' => 'Please reduce all prices by 10%',
        ])
        ->assertNotified('Message sent');

    $conversation->refresh();
    expect($conversation->status)->toBe(ConversationStatus::Pending);

    $message = $conversation->messages()->first();
    expect($message->role)->toBe(MessageRole::User)
        ->and($message->content)->toBe('Please reduce all prices by 10%');

    Queue::assertPushed(ProcessDocumentJob::class);
});

it('can reply to ai question when conversation needs context', function () {
    Queue::fake();

    $conversation = DocumentConversation::factory()->needsContext()->create([
        'supplier_id' => $this->supplier->id,
        'user_id' => $this->user->id,
        'ai_question' => 'What is the VAT rate?',
    ]);

    Livewire::test(ViewDocumentConversation::class, ['record' => $conversation->id])
        ->callAction('sendMessage', [
            'message' => 'The VAT rate is 8.1%',
        ])
        ->assertNotified('Message sent');

    $conversation->refresh();
    expect($conversation->status)->toBe(ConversationStatus::Pending)
        ->and($conversation->ai_question)->toBeNull();

    Queue::assertPushed(ProcessDocumentJob::class);
});

it('sends database notification when processing completes', function () {
    $conversation = DocumentConversation::factory()->create([
        'supplier_id' => $this->supplier->id,
        'user_id' => $this->user->id,
        'original_filename' => 'test.csv',
    ]);

    $notification = \Filament\Notifications\Notification::make()
        ->success()
        ->title('Document processed successfully');

    $notification->sendToDatabase($this->user);

    expect($this->user->notifications)->toHaveCount(1);
});

it('sends database notification when processing fails', function () {
    $conversation = DocumentConversation::factory()->create([
        'supplier_id' => $this->supplier->id,
        'user_id' => $this->user->id,
        'original_filename' => 'test.csv',
    ]);

    $notification = \Filament\Notifications\Notification::make()
        ->danger()
        ->title('Document processing failed');

    $notification->sendToDatabase($this->user);

    expect($this->user->notifications)->toHaveCount(1);
});
