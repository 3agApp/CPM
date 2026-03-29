<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;

    protected $fillable = [
        'document_conversation_id',
        'artnr',
        'bestellnr',
        'artean',
        'gtin2',
        'hersteller_id',
        'brand_name',
        'bez1',
        'kurztext',
        'langtext',
        'wg1',
        'wg2',
        'ek_eur',
        'uvp_eur',
        'ek',
        'vk1',
        'vk2',
        'vk3',
        'mwst',
        'vk_de_chf',
        'price_diff_percent',
        'margin_amount',
        'margin_percent',
        'shop_margin_amount',
        'shop_margin_percent',
        'gewnetto',
        'gewbrutto',
        'verkaufsmenge',
        'verkaufsmenge_staffel',
        'wbztage',
        'uspland',
        'ursprungsland',
        'zolltarifnr',
        'zolltarifnr_ch',
        'zolltarifnr_bez',
        'aktiv',
        'webshop',
        'ws_aktiv',
        'ws_dateavailable',
        'ws_abverkauf',
    ];

    protected function casts(): array
    {
        return [
            'ek_eur' => 'decimal:2',
            'uvp_eur' => 'decimal:2',
            'ek' => 'decimal:2',
            'vk1' => 'decimal:2',
            'vk2' => 'decimal:2',
            'vk3' => 'decimal:2',
            'mwst' => 'decimal:2',
            'vk_de_chf' => 'decimal:2',
            'price_diff_percent' => 'decimal:2',
            'margin_amount' => 'decimal:2',
            'margin_percent' => 'decimal:2',
            'shop_margin_amount' => 'decimal:2',
            'shop_margin_percent' => 'decimal:2',
            'gewnetto' => 'decimal:2',
            'gewbrutto' => 'decimal:2',
            'aktiv' => 'boolean',
            'webshop' => 'boolean',
            'ws_aktiv' => 'boolean',
            'ws_abverkauf' => 'boolean',
        ];
    }

    public function documentConversation(): BelongsTo
    {
        return $this->belongsTo(DocumentConversation::class);
    }
}
