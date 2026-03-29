<?php

use App\Enums\Role;
use App\Filament\Resources\DocumentConversations\Pages\ViewDocumentConversation;
use App\Filament\Resources\DocumentConversations\RelationManagers\ProductsRelationManager;
use App\Models\Brand;
use App\Models\DocumentConversation;
use App\Models\Organization;
use App\Models\Product;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

function productsRelationManagerContext(): array
{
    $organization = Organization::factory()->create();
    $user = User::factory()->create();
    $organization->members()->attach($user, ['role' => Role::Owner->value]);
    $brand = Brand::factory()->create(['organization_id' => $organization->id]);

    actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('dashboard'));
    Filament::setTenant($organization);

    $conversation = DocumentConversation::factory()->create([
        'brand_id' => $brand->id,
        'user_id' => $user->id,
    ]);

    return [$conversation, $user];
}

it('can render the products relation manager', function () {
    [$conversation] = productsRelationManagerContext();

    Livewire::test(ProductsRelationManager::class, [
        'ownerRecord' => $conversation,
        'pageClass' => ViewDocumentConversation::class,
    ])->assertOk();
});

it('can list products for a conversation', function () {
    [$conversation] = productsRelationManagerContext();

    $products = Product::factory()->count(3)->create([
        'document_conversation_id' => $conversation->id,
    ]);

    Livewire::test(ProductsRelationManager::class, [
        'ownerRecord' => $conversation,
        'pageClass' => ViewDocumentConversation::class,
    ])
        ->assertCanSeeTableRecords($products)
        ->assertCountTableRecords(3);
});

it('can create a product', function () {
    [$conversation] = productsRelationManagerContext();

    Livewire::test(ProductsRelationManager::class, [
        'ownerRecord' => $conversation,
        'pageClass' => ViewDocumentConversation::class,
    ])
        ->callAction(TestAction::make(CreateAction::class)->table(), [
            'artnr' => 'TEST001',
            'bez1' => 'Test Product',
            'vk1' => 29.90,
            'ek' => 15.00,
        ])
        ->assertNotified();

    assertDatabaseHas(Product::class, [
        'artnr' => 'TEST001',
        'bez1' => 'Test Product',
        'document_conversation_id' => $conversation->id,
    ]);
});

it('can edit a product', function () {
    [$conversation] = productsRelationManagerContext();

    $product = Product::factory()->create([
        'document_conversation_id' => $conversation->id,
        'artnr' => 'OLD001',
    ]);

    Livewire::test(ProductsRelationManager::class, [
        'ownerRecord' => $conversation,
        'pageClass' => ViewDocumentConversation::class,
    ])
        ->callAction(TestAction::make(EditAction::class)->table($product), [
            'artnr' => 'NEW001',
        ])
        ->assertNotified();

    assertDatabaseHas(Product::class, [
        'id' => $product->id,
        'artnr' => 'NEW001',
    ]);
});

it('can delete a product', function () {
    [$conversation] = productsRelationManagerContext();

    $product = Product::factory()->create([
        'document_conversation_id' => $conversation->id,
    ]);

    Livewire::test(ProductsRelationManager::class, [
        'ownerRecord' => $conversation,
        'pageClass' => ViewDocumentConversation::class,
    ])
        ->callAction(TestAction::make(DeleteAction::class)->table($product))
        ->assertNotified();

    expect(Product::find($product->id))->toBeNull();
});
