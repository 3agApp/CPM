<?php

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Brand>
 */
class BrandFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->company(),
            'ai_context' => fake()->optional()->paragraph(),
            'article_number_prefix' => strtoupper(fake()->lexify('???')),
            'default_wg1' => fake()->words(2, true),
            'default_wg2' => fake()->words(2, true),
            'default_manufacturer_id' => strtoupper(fake()->bothify('???###')),
            'default_supplier_margin' => fake()->randomFloat(2, 10, 35),
            'minimum_shop_margin' => fake()->randomFloat(2, 5, 25),
            'price_currency' => 'EUR',
            'currency_factor' => 1.1000,
            'default_rounding_rule' => 'end_with_90',
            'is_active' => true,
            'is_webshop' => fake()->boolean(),
            'is_webshop_active' => false,
        ];
    }
}
