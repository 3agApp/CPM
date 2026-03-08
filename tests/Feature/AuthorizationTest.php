<?php

use App\Enums\Role;
use App\Models\Organization;
use App\Models\User;

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->owner = User::factory()->create();
    $this->admin = User::factory()->create();
    $this->member = User::factory()->create();
    $this->outsider = User::factory()->create();

    $this->organization->members()->attach($this->owner, ['role' => Role::Owner->value]);
    $this->organization->members()->attach($this->admin, ['role' => Role::Admin->value]);
    $this->organization->members()->attach($this->member, ['role' => Role::Member->value]);
});

describe('OrganizationPolicy', function () {
    it('allows any user to view any organizations', function () {
        expect($this->owner->can('viewAny', Organization::class))->toBeTrue();
    });

    it('allows members to view their organization', function () {
        expect($this->owner->can('view', $this->organization))->toBeTrue()
            ->and($this->member->can('view', $this->organization))->toBeTrue();
    });

    it('denies non-members from viewing organization', function () {
        expect($this->outsider->can('view', $this->organization))->toBeFalse();
    });

    it('allows owner to update organization', function () {
        expect($this->owner->can('update', $this->organization))->toBeTrue();
    });

    it('allows admin to update organization', function () {
        expect($this->admin->can('update', $this->organization))->toBeTrue();
    });

    it('denies member from updating organization', function () {
        expect($this->member->can('update', $this->organization))->toBeFalse();
    });

    it('allows only owner to delete organization', function () {
        expect($this->owner->can('delete', $this->organization))->toBeTrue()
            ->and($this->admin->can('delete', $this->organization))->toBeFalse()
            ->and($this->member->can('delete', $this->organization))->toBeFalse();
    });
});

describe('Role enum', function () {
    it('owner can manage members', function () {
        expect(Role::Owner->canManageMembers())->toBeTrue();
    });

    it('admin can manage members', function () {
        expect(Role::Admin->canManageMembers())->toBeTrue();
    });

    it('member cannot manage members', function () {
        expect(Role::Member->canManageMembers())->toBeFalse();
    });

    it('owner can manage organization', function () {
        expect(Role::Owner->canManageOrganization())->toBeTrue();
    });

    it('admin can manage organization', function () {
        expect(Role::Admin->canManageOrganization())->toBeTrue();
    });

    it('member cannot manage organization', function () {
        expect(Role::Member->canManageOrganization())->toBeFalse();
    });

    it('only owner can delete organization', function () {
        expect(Role::Owner->canDeleteOrganization())->toBeTrue()
            ->and(Role::Admin->canDeleteOrganization())->toBeFalse()
            ->and(Role::Member->canDeleteOrganization())->toBeFalse();
    });

    it('has labels', function () {
        expect(Role::Owner->getLabel())->toBe('Owner')
            ->and(Role::Admin->getLabel())->toBe('Admin')
            ->and(Role::Member->getLabel())->toBe('Member');
    });
});
