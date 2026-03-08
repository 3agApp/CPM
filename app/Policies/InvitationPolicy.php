<?php

namespace App\Policies;

use App\Models\Invitation;
use App\Models\User;
use Filament\Facades\Filament;

class InvitationPolicy
{
    public function viewAny(User $user): bool
    {
        $role = $user->getRoleForOrganization(Filament::getTenant());

        return $role?->canManageMembers() ?? false;
    }

    public function view(User $user, Invitation $invitation): bool
    {
        $role = $user->getRoleForOrganization(Filament::getTenant());

        return $role?->canManageMembers() ?? false;
    }

    public function create(User $user): bool
    {
        $role = $user->getRoleForOrganization(Filament::getTenant());

        return $role?->canManageMembers() ?? false;
    }

    public function delete(User $user, Invitation $invitation): bool
    {
        $role = $user->getRoleForOrganization(Filament::getTenant());

        return $role?->canManageMembers() ?? false;
    }
}
