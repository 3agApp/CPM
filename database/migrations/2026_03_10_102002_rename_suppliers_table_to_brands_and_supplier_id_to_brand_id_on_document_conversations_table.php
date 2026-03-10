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
        if (Schema::hasTable('suppliers') && ! Schema::hasTable('brands')) {
            Schema::rename('suppliers', 'brands');
        }

        if (! Schema::hasTable('document_conversations')) {
            return;
        }

        if (Schema::hasColumn('document_conversations', 'supplier_id') && ! Schema::hasColumn('document_conversations', 'brand_id')) {
            Schema::table('document_conversations', function (Blueprint $table) {
                $table->dropForeign(['supplier_id']);
            });

            Schema::table('document_conversations', function (Blueprint $table) {
                $table->renameColumn('supplier_id', 'brand_id');
            });

            Schema::table('document_conversations', function (Blueprint $table) {
                $table->foreign('brand_id')->references('id')->on('brands')->cascadeOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('document_conversations')) {
            return;
        }

        if (Schema::hasColumn('document_conversations', 'brand_id') && ! Schema::hasColumn('document_conversations', 'supplier_id')) {
            Schema::table('document_conversations', function (Blueprint $table) {
                $table->dropForeign(['brand_id']);
            });

            Schema::table('document_conversations', function (Blueprint $table) {
                $table->renameColumn('brand_id', 'supplier_id');
            });

            Schema::table('document_conversations', function (Blueprint $table) {
                $table->foreign('supplier_id')->references('id')->on('suppliers')->cascadeOnDelete();
            });
        }

        if (Schema::hasTable('brands') && ! Schema::hasTable('suppliers')) {
            Schema::rename('brands', 'suppliers');
        }
    }
};
