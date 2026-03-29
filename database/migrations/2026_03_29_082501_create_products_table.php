<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('document_conversation_id')->constrained()->cascadeOnDelete();

            // Identifiers
            $table->string('artnr')->nullable();
            $table->string('bestellnr')->nullable();
            $table->string('artean')->nullable();
            $table->string('gtin2')->nullable();
            $table->string('hersteller_id')->nullable();
            $table->string('brand_name')->nullable();

            // Description
            $table->string('bez1')->nullable();
            $table->text('kurztext')->nullable();
            $table->text('langtext')->nullable();

            // Classification
            $table->string('wg1')->nullable();
            $table->string('wg2')->nullable();

            // Source pricing (EUR/USD from supplier)
            $table->decimal('ek_eur', 10, 2)->nullable();
            $table->decimal('uvp_eur', 10, 2)->nullable();

            // Calculated pricing (CHF)
            $table->decimal('ek', 10, 2)->nullable();
            $table->decimal('vk1', 10, 2)->nullable();
            $table->decimal('vk2', 10, 2)->nullable();
            $table->decimal('vk3', 10, 2)->nullable();
            $table->decimal('mwst', 5, 2)->nullable();

            // Comparison pricing
            $table->decimal('vk_de_chf', 10, 2)->nullable();
            $table->decimal('price_diff_percent', 8, 2)->nullable();

            // Margins
            $table->decimal('margin_amount', 10, 2)->nullable();
            $table->decimal('margin_percent', 8, 2)->nullable();
            $table->decimal('shop_margin_amount', 10, 2)->nullable();
            $table->decimal('shop_margin_percent', 8, 2)->nullable();

            // Logistics
            $table->decimal('gewnetto', 10, 2)->nullable();
            $table->decimal('gewbrutto', 10, 2)->nullable();
            $table->integer('verkaufsmenge')->nullable();
            $table->integer('verkaufsmenge_staffel')->nullable();
            $table->integer('wbztage')->nullable();

            // Origin & Customs
            $table->string('uspland')->nullable();
            $table->string('ursprungsland')->nullable();
            $table->string('zolltarifnr')->nullable();
            $table->string('zolltarifnr_ch')->nullable();
            $table->string('zolltarifnr_bez')->nullable();

            // Flags
            $table->boolean('aktiv')->default(true);
            $table->boolean('webshop')->default(false);
            $table->boolean('ws_aktiv')->default(false);
            $table->string('ws_dateavailable')->nullable();
            $table->boolean('ws_abverkauf')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
