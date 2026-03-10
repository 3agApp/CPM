<?php

use App\Enums\Role;
use App\Filament\Pages\Tenancy\EditOrganizationProfile;
use App\Models\Organization;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

it('can create an organization', function () {
    $organization = Organization::factory()->create();

    expect($organization)->toBeInstanceOf(Organization::class)
        ->and($organization->name)->not->toBeEmpty()
        ->and($organization->slug)->not->toBeEmpty();
});

it('has members relationship', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->create();

    $organization->members()->attach($user, ['role' => Role::Owner->value]);

    expect($organization->members)->toHaveCount(1)
        ->and($organization->members->first()->id)->toBe($user->id)
        ->and($organization->members->first()->pivot->role)->toBe(Role::Owner->value);
});

it('has suppliers relationship', function () {
    $organization = Organization::factory()->create();

    $organization->suppliers()->create(['name' => 'Test Supplier']);

    expect($organization->suppliers)->toHaveCount(1);
});

it('has invitations relationship', function () {
    $organization = Organization::factory()->create();

    expect($organization->invitations())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
});

it('implements HasCurrentTenantLabel', function () {
    $organization = Organization::factory()->create();

    expect($organization->getCurrentTenantLabel())->toBe('Active Organization');
});

it('enforces unique slug', function () {
    Organization::factory()->create(['slug' => 'test-org']);

    expect(fn () => Organization::factory()->create(['slug' => 'test-org']))
        ->toThrow(\Illuminate\Database\QueryException::class);
});

it('enforces unique membership per user per organization', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->create();

    $organization->members()->attach($user, ['role' => Role::Member->value]);

    expect(fn () => $organization->members()->attach($user, ['role' => Role::Admin->value]))
        ->toThrow(\Illuminate\Database\QueryException::class);
});

it('redirects to the updated organization profile URL after changing the slug', function () {
    $organization = Organization::factory()->create(['slug' => 'old-slug']);
    $user = User::factory()->create();

    $organization->members()->attach($user, ['role' => Role::Owner->value]);

    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('dashboard'));
    Filament::setTenant($organization);

    Livewire::test(EditOrganizationProfile::class)
        ->set('data.name', 'Updated Organization')
        ->set('data.slug', 'new-slug')
        ->call('save')
        ->assertRedirect(route('filament.dashboard.tenant.profile', ['tenant' => 'new-slug']));

    expect($organization->fresh()->slug)->toBe('new-slug');
});
