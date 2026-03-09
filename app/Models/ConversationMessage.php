<?php

namespace App\Models;

use App\Enums\MessageRole;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversationMessage extends Model
{
    /** @use HasFactory<\Database\Factories\ConversationMessageFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'document_conversation_id',
        'role',
        'content',
    ];

    protected function casts(): array
    {
        return [
            'role' => MessageRole::class,
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(DocumentConversation::class, 'document_conversation_id');
    }
}
