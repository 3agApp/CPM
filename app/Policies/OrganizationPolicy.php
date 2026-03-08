<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;

class OrganizationPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Organization $organization): bool
    {
        return $user->canAccessTenant($organization);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Organization $organization): bool
    {
        $role = $user->getRoleForOrganization($organization);

        return $role?->canManageOrganization() ?? false;
    }

    public function delete(User $user, Organization $organization): bool
    {
        $role = $user->getRoleForOrganization($organization);

        return $role?->canDeleteOrganization() ?? false;
    }
}
