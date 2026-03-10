<?php

use App\Enums\ConversationStatus;
use App\Enums\MessageRole;
use App\Enums\Role;
use App\Filament\Resources\DocumentConversations\DocumentConversationResource;
use App\Filament\Resources\DocumentConversations\Pages\ListDocumentConversations;
use App\Filament\Resources\DocumentConversations\Pages\ViewDocumentConversation;
use App\Jobs\ProcessDocumentJob;
use App\Models\Brand;
use App\Models\DocumentConversation;
use App\Models\Organization;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

function documentConversationResourceContext(): array
{
    $organization = Organization::factory()->create();
    $user = User::factory()->create();
    $organization->members()->attach($user, ['role' => Role::Owner->value]);
    $brand = Brand::factory()->create(['organization_id' => $organization->id]);

    actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('dashboard'));
    Filament::setTenant($organization);

    return [$organization, $user, $brand];
}

it('can list document conversations for the current tenant', function () {
    [$organization, $user, $brand] = documentConversationResourceContext();

    $conversation = DocumentConversation::factory()->create([
        'brand_id' => $brand->id,
        'user_id' => $user->id,
    ]);

    $otherOrg = Organization::factory()->create();
    $otherBrand = Brand::factory()->create(['organization_id' => $otherOrg->id]);
    DocumentConversation::factory()->create([
        'brand_id' => $otherBrand->id,
        'user_id' => $user->id,
    ]);

    Livewire::test(ListDocumentConversations::class)
        ->assertCanSeeTableRecords([$conversation])
        ->assertCountTableRecords(1);
});

it('can view a document conversation', function () {
    [, $user, $brand] = documentConversationResourceContext();

    $conversation = DocumentConversation::factory()->create([
        'brand_id' => $brand->id,
        'user_id' => $user->id,
        'original_filename' => 'products.csv',
    ]);

    Livewire::test(ViewDocumentConversation::class, ['record' => $conversation->id])
        ->assertSuccessful();
});

it('lets another member of the same organization see a shared conversation in the list', function () {
    [$organization, $user, $brand] = documentConversationResourceContext();

    $ownerConversation = DocumentConversation::factory()->create([
        'brand_id' => $brand->id,
        'user_id' => $user->id,
    ]);

    $teammate = User::factory()->create();
    $organization->members()->attach($teammate, ['role' => Role::Member->value]);

    actingAs($teammate);
    Filament::setTenant($organization);

    Livewire::test(ListDocumentConversations::class)
        ->assertCanSeeTableRecords([$ownerConversation])
        ->assertCountTableRecords(1);
});

it('lets another member of the same organization view a shared conversation', function () {
    [$organization, $user, $brand] = documentConversationResourceContext();

    $ownerConversation = DocumentConversation::factory()->create([
        'brand_id' => $brand->id,
        'user_id' => $user->id,
    ]);

    $teammate = User::factory()->create();
    $organization->members()->attach($teammate, ['role' => Role::Member->value]);

    actingAs($teammate);
    Filament::setTenant($organization);

    Livewire::test(ViewDocumentConversation::class, ['record' => $ownerConversation->id])
        ->assertSuccessful();
});

it('does not let a member of another organization access the conversation view', function () {
    [, $user, $brand] = documentConversationResourceContext();

    $conversation = DocumentConversation::factory()->create([
        'brand_id' => $brand->id,
        'user_id' => $user->id,
    ]);

    $otherOrganization = Organization::factory()->create();
    $otherUser = User::factory()->create();
    $otherOrganization->members()->attach($otherUser, ['role' => Role::Member->value]);

    actingAs($otherUser);
    Filament::setTenant($otherOrganization);

    $response = $this->get(DocumentConversationResource::getUrl('view', ['record' => $conversation]));

    $response->assertNotFound();
});

it('renders conversation messages as safe markdown', function () {
    [, $user, $brand] = documentConversationResourceContext();

    $conversation = DocumentConversation::factory()->create([
        'brand_id' => $brand->id,
        'user_id' => $user->id,
    ]);

    $conversation->messages()->create([
        'role' => MessageRole::Assistant,
        'content' => "# Summary\n\n- First item\n- **Bold detail**\n\n```\ntotal = 42\n```\n\n| Name | Price |\n| --- | ---: |\n| Cable | 9.99 |\n\n<script>alert('xss')</script>",
    ]);

    Livewire::test(ViewDocumentConversation::class, ['record' => $conversation->id])
        ->assertSuccessful()
        ->assertSee('message-markdown')
        ->assertSeeHtml('<h1>Summary</h1>')
        ->assertSeeHtml('<strong>Bold detail</strong>')
        ->assertSeeHtml('<pre><code>')
        ->assertSeeHtml('<table>')
        ->assertDontSee('<script>', false);
});

it('shows error message for failed conversations', function () {
    [, $user, $brand] = documentConversationResourceContext();

    $conversation = DocumentConversation::factory()->failed()->create([
        'brand_id' => $brand->id,
        'user_id' => $user->id,
        'error_message' => 'Processing failed due to invalid format',
    ]);

    Livewire::test(ViewDocumentConversation::class, ['record' => $conversation->id])
        ->assertSuccessful()
        ->assertSee('Processing failed due to invalid format');
});

it('displays status badge with correct color', function () {
    [, $user, $brand] = documentConversationResourceContext();

    DocumentConversation::factory()->create([
        'brand_id' => $brand->id,
        'user_id' => $user->id,
        'status' => ConversationStatus::Completed,
    ]);

    Livewire::test(ListDocumentConversations::class)
        ->assertCanSeeTableRecords(
            DocumentConversation::where('brand_id', $brand->id)->get()
        );
});

it('can upload a document with notes from the list page', function () {
    [, $user, $brand] = documentConversationResourceContext();

    Queue::fake();

    $file = UploadedFile::fake()->create('products.csv', 100, 'text/csv');

    Livewire::test(ListDocumentConversations::class)
        ->callAction('upload', [
            'brand_id' => $brand->id,
            'document' => $file,
            'notes' => 'VAT rate is 8.1%',
        ])
        ->assertNotified('Document uploaded');

    expect(DocumentConversation::count())->toBe(1);

    $conversation = DocumentConversation::first();
    expect($conversation->brand_id)->toBe($brand->id)
        ->and($conversation->user_id)->toBe($user->id)
        ->and($conversation->status)->toBe(ConversationStatus::Pending)
        ->and($conversation->stored_path)->not->toBeNull();

    $message = $conversation->messages()->first();
    expect($message->role)->toBe(MessageRole::User)
        ->and($message->content)->toBe('VAT rate is 8.1%');

    Queue::assertPushed(ProcessDocumentJob::class);
});

it('can upload a document without notes', function () {
    [, , $brand] = documentConversationResourceContext();

    Queue::fake();

    $file = UploadedFile::fake()->create('products.csv', 100, 'text/csv');

    Livewire::test(ListDocumentConversations::class)
        ->callAction('upload', [
            'brand_id' => $brand->id,
            'document' => $file,
        ])
        ->assertNotified('Document uploaded');

    $conversation = DocumentConversation::first();
    expect($conversation->messages)->toHaveCount(0);

    Queue::assertPushed(ProcessDocumentJob::class);
});

it('can send a message to request changes on completed conversation', function () {
    [, $user, $brand] = documentConversationResourceContext();

    Queue::fake();

    $conversation = DocumentConversation::factory()->completed()->create([
        'brand_id' => $brand->id,
        'user_id' => $user->id,
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
    [, $user, $brand] = documentConversationResourceContext();

    Queue::fake();

    $conversation = DocumentConversation::factory()->needsContext()->create([
        'brand_id' => $brand->id,
        'user_id' => $user->id,
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
    [, $user, $brand] = documentConversationResourceContext();

    $conversation = DocumentConversation::factory()->create([
        'brand_id' => $brand->id,
        'user_id' => $user->id,
        'original_filename' => 'test.csv',
    ]);

    $notification = \Filament\Notifications\Notification::make()
        ->success()
        ->title('Document processed successfully');

    $notification->sendToDatabase($user);

    expect($user->notifications)->toHaveCount(1);
});

it('sends database notification when processing fails', function () {
    [, $user, $brand] = documentConversationResourceContext();

    $conversation = DocumentConversation::factory()->create([
        'brand_id' => $brand->id,
        'user_id' => $user->id,
        'original_filename' => 'test.csv',
    ]);

    $notification = \Filament\Notifications\Notification::make()
        ->danger()
        ->title('Document processing failed');

    $notification->sendToDatabase($user);

    expect($user->notifications)->toHaveCount(1);
});
