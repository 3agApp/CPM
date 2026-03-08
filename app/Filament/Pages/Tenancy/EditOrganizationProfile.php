<?php

namespace App\Filament\Pages\Tenancy;

use App\Models\Organization;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\EditTenantProfile;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class EditOrganizationProfile extends EditTenantProfile
{
    public static function getLabel(): string
    {
        return 'Organization Settings';
    }

    public static function canView(Model $tenant): bool
    {
        $role = Filament::auth()->user()->getRoleForOrganization($tenant);

        return $role?->canManageOrganization() ?? false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($set, ?string $state) => $set('slug', Str::slug($state ?? ''))),
                TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(Organization::class, 'slug', ignorable: fn () => Filament::getTenant())
                    ->rules(['alpha_dash:ascii']),
            ]);
    }
}
