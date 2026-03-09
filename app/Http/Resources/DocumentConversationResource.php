<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentConversationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'supplier_id' => $this->supplier_id,
            'status' => $this->status->value,
            'status_label' => $this->status->getLabel(),
            'original_filename' => $this->original_filename,
            'ai_question' => $this->ai_question,
            'has_output' => $this->output_path !== null,
            'error_message' => $this->error_message,
            'messages' => $this->whenLoaded('messages', fn () => $this->messages->map(fn ($message) => [
                'id' => $message->id,
                'role' => $message->role->value,
                'content' => $message->content,
                'created_at' => $message->created_at,
            ])),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
