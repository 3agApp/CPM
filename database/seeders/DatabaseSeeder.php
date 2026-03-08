<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $acme = Organization::factory()->create([
            'name' => 'Acme Corp',
            'slug' => 'acme-corp',
        ]);

        $globex = Organization::factory()->create([
            'name' => 'Globex Inc',
            'slug' => 'globex-inc',
        ]);

        $user->organizations()->attach($acme, ['role' => Role::Owner->value]);
        $user->organizations()->attach($globex, ['role' => Role::Admin->value]);
    }
}
