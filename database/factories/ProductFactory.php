<?php

namespace Database\Factories;

use App\Models\DocumentConversation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'document_conversation_id' => DocumentConversation::factory(),
            'artnr' => strtoupper(fake()->bothify('???#####')),
            'wg1' => fake()->numerify('###'),
            'wg2' => fake()->numerify('###'),
            'bez1' => fake()->words(4, true),
            'bestellnr' => fake()->numerify('####'),
            'vk1' => fake()->randomFloat(2, 10, 500),
            'vk2' => fake()->randomFloat(2, 10, 500),
            'vk3' => fake()->randomFloat(2, 10, 500),
            'ek' => fake()->randomFloat(2, 5, 300),
            'mwst' => 8.10,
            'artean' => fake()->ean13(),
            'aktiv' => true,
            'hersteller_id' => fake()->numerify('###'),
            'webshop' => fake()->boolean(),
            'ws_aktiv' => fake()->boolean(),
            'verkaufsmenge' => 1,
            'wbztage' => fake()->numberBetween(1, 30),
            'ws_abverkauf' => false,
        ];
    }
}
