<?php

namespace App\Models;

use App\Enums\ConversationStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentConversation extends Model
{
    /** @use HasFactory<\Database\Factories\DocumentConversationFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'brand_id',
        'user_id',
        'status',
        'original_filename',
        'stored_path',
        'output_path',
        'ai_question',
        'user_context',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'status' => ConversationStatus::class,
        ];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ConversationMessage::class)->orderBy('created_at');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function isPending(): bool
    {
        return $this->status === ConversationStatus::Pending;
    }

    public function isProcessing(): bool
    {
        return $this->status === ConversationStatus::Processing;
    }

    public function needsContext(): bool
    {
        return $this->status === ConversationStatus::NeedsContext;
    }

    public function isCompleted(): bool
    {
        return $this->status === ConversationStatus::Completed;
    }

    public function isFailed(): bool
    {
        return $this->status === ConversationStatus::Failed;
    }
}
