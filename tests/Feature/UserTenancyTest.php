<?php

use App\Enums\Role;
use App\Models\Organization;
use App\Models\User;
use Filament\Facades\Filament;

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->owner = User::factory()->create();
    $this->admin = User::factory()->create();
    $this->member = User::factory()->create();

    $this->organization->members()->attach($this->owner, ['role' => Role::Owner->value]);
    $this->organization->members()->attach($this->admin, ['role' => Role::Admin->value]);
    $this->organization->members()->attach($this->member, ['role' => Role::Member->value]);
});

it('user can access their organizations', function () {
    expect($this->owner->canAccessTenant($this->organization))->toBeTrue()
        ->and($this->admin->canAccessTenant($this->organization))->toBeTrue()
        ->and($this->member->canAccessTenant($this->organization))->toBeTrue();
});

it('user cannot access organizations they do not belong to', function () {
    $otherOrg = Organization::factory()->create();

    expect($this->owner->canAccessTenant($otherOrg))->toBeFalse();
});

it('user can get tenants', function () {
    $panel = Filament::getPanel('dashboard');

    $tenants = $this->owner->getTenants($panel);

    expect($tenants)->toHaveCount(1)
        ->and($tenants->first()->id)->toBe($this->organization->id);
});

it('user can get their role for an organization', function () {
    expect($this->owner->getRoleForOrganization($this->organization))->toBe(Role::Owner)
        ->and($this->admin->getRoleForOrganization($this->organization))->toBe(Role::Admin)
        ->and($this->member->getRoleForOrganization($this->organization))->toBe(Role::Member);
});

it('user returns null role for non-member organization', function () {
    $otherOrg = Organization::factory()->create();

    expect($this->owner->getRoleForOrganization($otherOrg))->toBeNull();
});

it('user can belong to multiple organizations', function () {
    $secondOrg = Organization::factory()->create();
    $this->owner->organizations()->attach($secondOrg, ['role' => Role::Admin->value]);

    expect($this->owner->organizations)->toHaveCount(2);
});
