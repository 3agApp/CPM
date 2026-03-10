<?php

use App\Enums\Role;
use App\Filament\Resources\Brands\Pages\CreateBrand;
use App\Models\Brand;
use App\Models\Organization;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

it('can create a brand with configuration fields from the filament resource form', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->create();

    $organization->members()->attach($user, ['role' => Role::Owner->value]);

    $this->actingAs($user);

    Filament::setCurrentPanel(Filament::getPanel('dashboard'));
    Filament::setTenant($organization);

    Livewire::test(CreateBrand::class)
        ->fillForm([
            'name' => 'LEGO Deutschland GmbH',
            'ai_context' => 'Treat LEGO as a premium toy brand with strict category naming and consistent SKU prefixes.',
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

    $brand = Brand::query()->first();

    expect($brand)
        ->not->toBeNull()
        ->and($brand->organization->is($organization))->toBeTrue()
        ->and($brand->name)->toBe('LEGO Deutschland GmbH')
        ->and($brand->ai_context)->toBe('Treat LEGO as a premium toy brand with strict category naming and consistent SKU prefixes.')
        ->and($brand->article_number_prefix)->toBe('LEGO')
        ->and($brand->default_wg1)->toBe('Bausteine')
        ->and($brand->default_wg2)->toBe('LEGO Sets')
        ->and($brand->default_manufacturer_id)->toBe('LEGO001')
        ->and($brand->default_supplier_margin)->toBe('25.00')
        ->and($brand->minimum_shop_margin)->toBe('15.00')
        ->and($brand->price_currency)->toBe('EUR')
        ->and($brand->default_rounding_rule)->toBe('end_with_90')
        ->and($brand->is_active)->toBeTrue()
        ->and($brand->is_webshop)->toBeTrue()
        ->and($brand->is_webshop_active)->toBeTrue();
});
