<?php

use App\Enums\ConversationStatus;
use App\Enums\Role;
use App\Filament\Resources\DocumentConversations\Pages\ListDocumentConversations;
use App\Filament\Resources\DocumentConversations\Pages\ViewDocumentConversation;
use App\Models\DocumentConversation;
use App\Models\Organization;
use App\Models\Supplier;
use App\Models\User;
use Filament\Facades\Filament;
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

it('shows ai question for conversations needing context', function () {
    $conversation = DocumentConversation::factory()->needsContext()->create([
        'supplier_id' => $this->supplier->id,
        'user_id' => $this->user->id,
        'ai_question' => 'What is the VAT rate?',
    ]);

    Livewire::test(ViewDocumentConversation::class, ['record' => $conversation->id])
        ->assertSuccessful()
        ->assertSee('What is the VAT rate?');
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
