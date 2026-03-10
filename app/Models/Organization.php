<?php

namespace App\Models;

use Filament\Models\Contracts\HasCurrentTenantLabel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model implements HasCurrentTenantLabel
{
    /** @use HasFactory<\Database\Factories\OrganizationFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
    ];

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    public function brands(): HasMany
    {
        return $this->hasMany(Brand::class);
    }

    public function getCurrentTenantLabel(): string
    {
        return 'Active Organization';
    }
}
