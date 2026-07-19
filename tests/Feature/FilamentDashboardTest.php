<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilamentDashboardTest extends TestCase
{
    use RefreshDatabase;
    public function test_filament_admin_dashboard_loads_successfully(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'teste@gmail.com'],
            ['name' => 'Guilherme', 'password' => bcrypt('password')]
        );

        $response = $this->actingAs($user)->get('/admin');

        $response->assertStatus(200);
    }
}
