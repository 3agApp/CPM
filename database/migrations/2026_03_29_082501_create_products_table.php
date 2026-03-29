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
            $table->string('artnr')->nullable();
            $table->string('wg1')->nullable();
            $table->string('wg2')->nullable();
            $table->string('bez1')->nullable();
            $table->string('bestellnr')->nullable();
            $table->decimal('vk1', 10, 2)->nullable();
            $table->decimal('vk2', 10, 2)->nullable();
            $table->decimal('vk3', 10, 2)->nullable();
            $table->decimal('ek', 10, 2)->nullable();
            $table->decimal('mwst', 5, 2)->nullable();
            $table->string('artean')->nullable();
            $table->text('langtext')->nullable();
            $table->boolean('aktiv')->default(true);
            $table->text('kurztext')->nullable();
            $table->string('hersteller_id')->nullable();
            $table->boolean('webshop')->default(false);
            $table->boolean('ws_aktiv')->default(false);
            $table->string('ws_dateavailable')->nullable();
            $table->decimal('gewnetto', 10, 2)->nullable();
            $table->decimal('gewbrutto', 10, 2)->nullable();
            $table->string('uspland')->nullable();
            $table->string('zolltarifnr')->nullable();
            $table->integer('verkaufsmenge')->nullable();
            $table->integer('verkaufsmenge_staffel')->nullable();
            $table->integer('wbztage')->nullable();
            $table->boolean('ws_abverkauf')->default(false);
            $table->string('zolltarifnr_ch')->nullable();
            $table->string('zolltarifnr_bez')->nullable();
            $table->string('ursprungsland')->nullable();
            $table->string('gtin2')->nullable();
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
