<?php

namespace Database\Factories;

use App\Enums\MessageRole;
use App\Models\DocumentConversation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ConversationMessage>
 */
class ConversationMessageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'document_conversation_id' => DocumentConversation::factory(),
            'role' => MessageRole::User,
            'content' => fake()->sentence(),
        ];
    }

    public function user(): static
    {
        return $this->state(fn () => ['role' => MessageRole::User]);
    }

    public function assistant(): static
    {
        return $this->state(fn () => ['role' => MessageRole::Assistant]);
    }
}
