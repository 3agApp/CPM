<?php

namespace App\Policies;

use App\Models\Brand;
use App\Models\User;
use Filament\Facades\Filament;

class BrandPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Brand $brand): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        $role = $user->getRoleForOrganization(Filament::getTenant());

        return $role?->canManageOrganization() ?? false;
    }

    public function update(User $user, Brand $brand): bool
    {
        $role = $user->getRoleForOrganization(Filament::getTenant());

        return $role?->canManageOrganization() ?? false;
    }

    public function delete(User $user, Brand $brand): bool
    {
        $role = $user->getRoleForOrganization(Filament::getTenant());

        return $role?->canManageOrganization() ?? false;
    }
}
