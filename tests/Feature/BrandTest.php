<?php

use App\Models\Brand;
use App\Models\Organization;

it('brand belongs to organization', function () {
    $brand = Brand::factory()->create();

    expect($brand->organization)->toBeInstanceOf(Organization::class);
});

it('brand is scoped to organization', function () {
    $org1 = Organization::factory()->create();
    $org2 = Organization::factory()->create();

    Brand::factory()->create(['organization_id' => $org1->id, 'name' => 'Org1 Brand']);
    Brand::factory()->create(['organization_id' => $org2->id, 'name' => 'Org2 Brand']);

    expect(Brand::where('organization_id', $org1->id)->count())->toBe(1)
        ->and(Brand::where('organization_id', $org1->id)->first()->name)->toBe('Org1 Brand');
});

it('deleting organization cascades to brands', function () {
    $organization = Organization::factory()->create();
    Brand::factory()->count(3)->create(['organization_id' => $organization->id]);

    expect(Brand::where('organization_id', $organization->id)->count())->toBe(3);

    $organization->delete();

    expect(Brand::where('organization_id', $organization->id)->count())->toBe(0);
});
