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
        Schema::create('document_conversations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('brand_id')->constrained('brands')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->string('original_filename');
            $table->string('stored_path');
            $table->string('output_path')->nullable();
            $table->text('ai_question')->nullable();
            $table->text('user_context')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_conversations');
    }
};
