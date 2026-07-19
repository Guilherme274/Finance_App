<?php

namespace Database\Seeders;

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
        // User::factory(10)->create();

        $user = User::firstOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Usuário Administrador',
                'password' => bcrypt('password'),
            ]
        );

        \App\Models\BankAccount::firstOrCreate(
            ['user_id' => $user->id, 'name' => 'Nubank'],
            [
                'institution' => 'Nubank',
                'balance' => 0.00,
                'type' => 'CREDIT',
                'color' => '#8b5cf6',
            ]
        );

        \App\Models\BankAccount::firstOrCreate(
            ['user_id' => $user->id, 'name' => 'Mercado Pago'],
            [
                'institution' => 'Mercado Pago',
                'balance' => 0.00,
                'type' => 'DEBIT',
                'color' => '#009ee3',
            ]
        );
    }
}
