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
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('ai_context')->nullable();
            $table->string('article_number_prefix')->nullable();
            $table->string('default_wg1')->nullable();
            $table->string('default_wg2')->nullable();
            $table->string('default_manufacturer_id')->nullable();
            $table->decimal('default_supplier_margin', 5, 2)->default(25);
            $table->decimal('minimum_shop_margin', 5, 2)->default(15);
            $table->string('price_currency', 3)->default('EUR');
            $table->string('default_rounding_rule')->default('end_with_90');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_webshop')->default(false);
            $table->boolean('is_webshop_active')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
