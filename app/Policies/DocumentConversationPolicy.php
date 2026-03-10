<?php

namespace App\Policies;

use App\Models\DocumentConversation;
use App\Models\Organization;
use App\Models\User;
use Filament\Facades\Filament;

class DocumentConversationPolicy
{
    public function viewAny(User $user): bool
    {
        $tenant = Filament::getTenant();

        return $tenant instanceof Organization && $user->canAccessTenant($tenant);
    }

    public function view(User $user, DocumentConversation $documentConversation): bool
    {
        return $user->canAccessTenant($documentConversation->supplier->organization);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, DocumentConversation $documentConversation): bool
    {
        return $user->canAccessTenant($documentConversation->supplier->organization);
    }

    public function delete(User $user, DocumentConversation $documentConversation): bool
    {
        $role = $user->getRoleForOrganization($documentConversation->supplier->organization);

        return $role?->canManageOrganization() ?? false;
    }

    public function restore(User $user, DocumentConversation $documentConversation): bool
    {
        return $this->delete($user, $documentConversation);
    }

    public function forceDelete(User $user, DocumentConversation $documentConversation): bool
    {
        return $this->delete($user, $documentConversation);
    }

    public function deleteAny(User $user): bool
    {
        $tenant = Filament::getTenant();

        if (! $tenant instanceof Organization) {
            return false;
        }

        $role = $user->getRoleForOrganization($tenant);

        return $role?->canManageOrganization() ?? false;
    }
}
