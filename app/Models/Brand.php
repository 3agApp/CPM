<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends Model
{
    /** @use HasFactory<\Database\Factories\BrandFactory> */
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'name',
        'ai_context',
        'article_number_prefix',
        'default_wg1',
        'default_wg2',
        'default_manufacturer_id',
        'default_supplier_margin',
        'minimum_shop_margin',
        'price_currency',
        'default_rounding_rule',
        'is_active',
        'is_webshop',
        'is_webshop_active',
    ];

    protected function casts(): array
    {
        return [
            'default_supplier_margin' => 'decimal:2',
            'minimum_shop_margin' => 'decimal:2',
            'is_active' => 'boolean',
            'is_webshop' => 'boolean',
            'is_webshop_active' => 'boolean',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function documentConversations(): HasMany
    {
        return $this->hasMany(DocumentConversation::class);
    }
}
