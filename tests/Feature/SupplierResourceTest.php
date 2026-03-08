<?php

use App\Enums\Role;
use App\Filament\Resources\SupplierResource\Pages\CreateSupplier;
use App\Models\Organization;
use App\Models\Supplier;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

it('can create a supplier with configuration fields from the filament resource form', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->create();

    $organization->members()->attach($user, ['role' => Role::Owner->value]);

    $this->actingAs($user);

    Filament::setCurrentPanel(Filament::getPanel('dashboard'));
    Filament::setTenant($organization);

    Livewire::test(CreateSupplier::class)
        ->fillForm([
            'name' => 'LEGO Deutschland GmbH',
            'article_number_prefix' => 'LEGO',
            'default_wg1' => 'Bausteine',
            'default_wg2' => 'LEGO Sets',
            'default_manufacturer_id' => 'LEGO001',
            'default_supplier_margin' => 25,
            'minimum_shop_margin' => 15,
            'price_currency' => 'EUR',
            'default_rounding_rule' => 'end_with_90',
            'is_active' => true,
            'is_webshop' => true,
            'is_webshop_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $supplier = Supplier::query()->first();

    expect($supplier)
        ->not->toBeNull()
        ->and($supplier->organization->is($organization))->toBeTrue()
        ->and($supplier->name)->toBe('LEGO Deutschland GmbH')
        ->and($supplier->article_number_prefix)->toBe('LEGO')
        ->and($supplier->default_wg1)->toBe('Bausteine')
        ->and($supplier->default_wg2)->toBe('LEGO Sets')
        ->and($supplier->default_manufacturer_id)->toBe('LEGO001')
        ->and($supplier->default_supplier_margin)->toBe('25.00')
        ->and($supplier->minimum_shop_margin)->toBe('15.00')
        ->and($supplier->price_currency)->toBe('EUR')
        ->and($supplier->default_rounding_rule)->toBe('end_with_90')
        ->and($supplier->is_active)->toBeTrue()
        ->and($supplier->is_webshop)->toBeTrue()
        ->and($supplier->is_webshop_active)->toBeTrue();
});
