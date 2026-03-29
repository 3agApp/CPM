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
        'wg1',
        'wg2',
        'bez1',
        'bestellnr',
        'vk1',
        'vk2',
        'vk3',
        'ek',
        'mwst',
        'artean',
        'langtext',
        'aktiv',
        'kurztext',
        'hersteller_id',
        'webshop',
        'ws_aktiv',
        'ws_dateavailable',
        'gewnetto',
        'gewbrutto',
        'uspland',
        'zolltarifnr',
        'verkaufsmenge',
        'verkaufsmenge_staffel',
        'wbztage',
        'ws_abverkauf',
        'zolltarifnr_ch',
        'zolltarifnr_bez',
        'ursprungsland',
        'gtin2',
    ];

    protected function casts(): array
    {
        return [
            'vk1' => 'decimal:2',
            'vk2' => 'decimal:2',
            'vk3' => 'decimal:2',
            'ek' => 'decimal:2',
            'mwst' => 'decimal:2',
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
