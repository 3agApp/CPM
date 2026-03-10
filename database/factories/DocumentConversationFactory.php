<?php

namespace Database\Factories;

use App\Enums\ConversationStatus;
use App\Models\Brand;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DocumentConversation>
 */
class DocumentConversationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'brand_id' => Brand::factory(),
            'user_id' => User::factory(),
            'status' => ConversationStatus::Pending,
            'original_filename' => fake()->word().'.csv',
            'stored_path' => 'documents/'.fake()->uuid().'.csv',
        ];
    }

    public function processing(): static
    {
        return $this->state(fn () => ['status' => ConversationStatus::Processing]);
    }

    public function needsContext(): static
    {
        return $this->state(fn () => [
            'status' => ConversationStatus::NeedsContext,
            'ai_question' => fake()->sentence(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => ConversationStatus::Completed,
            'output_path' => 'outputs/'.fake()->uuid().'.csv',
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => ConversationStatus::Failed,
            'error_message' => fake()->sentence(),
        ]);
    }
}
