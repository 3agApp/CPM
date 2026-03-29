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
        $ekEur = fake()->randomFloat(2, 5, 300);
        $ek = round($ekEur * 1.1, 2);
        $vk1 = round($ek * 1.5, 2);
        $vk3 = round($vk1 * 1.4, 2);
        $vk2 = round($vk3 * 0.85, 2);
        $uvpEur = round($ekEur * 2.5, 2);
        $vkDeChf = round($uvpEur * 1.1, 2);
        $priceDiff = $vkDeChf > 0 ? round((($vk3 - $vkDeChf) / $vkDeChf) * 100, 2) : 0;

        return [
            'document_conversation_id' => DocumentConversation::factory(),
            'artnr' => strtoupper(fake()->bothify('???#####')),
            'bestellnr' => fake()->numerify('####'),
            'artean' => fake()->ean13(),
            'hersteller_id' => fake()->numerify('###'),
            'brand_name' => fake()->company(),
            'bez1' => fake()->words(4, true),
            'wg1' => fake()->numerify('###'),
            'wg2' => fake()->numerify('###'),
            'ek_eur' => $ekEur,
            'uvp_eur' => $uvpEur,
            'ek' => $ek,
            'vk1' => $vk1,
            'vk2' => $vk2,
            'vk3' => $vk3,
            'mwst' => 8.10,
            'vk_de_chf' => $vkDeChf,
            'price_diff_percent' => $priceDiff,
            'margin_amount' => round($vk1 - $ek, 2),
            'margin_percent' => $ek > 0 ? round((($vk1 - $ek) / $ek) * 100, 2) : 0,
            'shop_margin_amount' => round($vk3 - $vk1, 2),
            'shop_margin_percent' => $vk1 > 0 ? round((($vk3 - $vk1) / $vk3) * 100, 2) : 0,
            'aktiv' => true,
            'webshop' => fake()->boolean(),
            'ws_aktiv' => fake()->boolean(),
            'ws_abverkauf' => false,
            'verkaufsmenge' => 1,
            'wbztage' => fake()->numberBetween(1, 30),
        ];
    }
}
