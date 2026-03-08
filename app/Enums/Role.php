<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum Role: string implements HasColor, HasLabel
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Member = 'member';

    public function getLabel(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::Admin => 'Admin',
            self::Member => 'Member',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Owner => 'danger',
            self::Admin => 'warning',
            self::Member => 'primary',
        };
    }

    public function canManageMembers(): bool
    {
        return in_array($this, [self::Owner, self::Admin]);
    }

    public function canManageOrganization(): bool
    {
        return in_array($this, [self::Owner, self::Admin]);
    }

    public function canDeleteOrganization(): bool
    {
        return $this === self::Owner;
    }
}
