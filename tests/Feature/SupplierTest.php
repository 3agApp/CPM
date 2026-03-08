<?php

use App\Models\Organization;
use App\Models\Supplier;

it('supplier belongs to organization', function () {
    $supplier = Supplier::factory()->create();

    expect($supplier->organization)->toBeInstanceOf(Organization::class);
});

it('supplier is scoped to organization', function () {
    $org1 = Organization::factory()->create();
    $org2 = Organization::factory()->create();

    Supplier::factory()->create(['organization_id' => $org1->id, 'name' => 'Org1 Supplier']);
    Supplier::factory()->create(['organization_id' => $org2->id, 'name' => 'Org2 Supplier']);

    expect(Supplier::where('organization_id', $org1->id)->count())->toBe(1)
        ->and(Supplier::where('organization_id', $org1->id)->first()->name)->toBe('Org1 Supplier');
});

it('deleting organization cascades to suppliers', function () {
    $organization = Organization::factory()->create();
    Supplier::factory()->count(3)->create(['organization_id' => $organization->id]);

    expect(Supplier::where('organization_id', $organization->id)->count())->toBe(3);

    $organization->delete();

    expect(Supplier::where('organization_id', $organization->id)->count())->toBe(0);
});
