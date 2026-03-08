<?php

namespace App\Policies;

use App\Models\Supplier;
use App\Models\User;
use Filament\Facades\Filament;

class SupplierPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Supplier $supplier): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        $role = $user->getRoleForOrganization(Filament::getTenant());

        return $role?->canManageOrganization() ?? false;
    }

    public function update(User $user, Supplier $supplier): bool
    {
        $role = $user->getRoleForOrganization(Filament::getTenant());

        return $role?->canManageOrganization() ?? false;
    }

    public function delete(User $user, Supplier $supplier): bool
    {
        $role = $user->getRoleForOrganization(Filament::getTenant());

        return $role?->canManageOrganization() ?? false;
    }
}
