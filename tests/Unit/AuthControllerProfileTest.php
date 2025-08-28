<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Debater;
use App\Models\User;
use App\Models\Coach;
use Tests\TestCase;

class AuthControllerProfileTest extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['jwt.secret' => env('JWT_SECRET', 'your-secret-key')]);
    }

    /** @test */
    public function it_returns_debater_profile_for_authenticated_debater()
    {
        $user = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'mobile_number' => '1234567890',
            'governorate' => 'Cairo',
            'profile_picture_url' => 'http://example.com/profile.jpg',
            'birth_date' => '1990-01-01',
            'education_degree' => 'Bachelor',
        ]);

        $coachUser = User::factory()->create([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
        ]);

        $coach = Coach::factory()->create([
            'user_id' => $coachUser->id,
        ]);

        $debater = Debater::factory()->create([
            'user_id' => $user->id,
            'coach_id' => $coach->id,
        ]);

        $token = auth('debater')->login($debater);

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->getJson('/api/profile');

        $response->assertStatus(200)
            ->assertJsonPath('message', "Here's your debater profile")
            ->assertJsonPath('data.0.profile.first_name', 'John')
            ->assertJsonPath('data.0.coach_name', 'Jane Smith')
            ->assertJsonPath('data.0.guard', 'debater');
    }

    /** @test */
    public function it_returns_admin_profile_for_authenticated_admin()
    {
        $admin = Admin::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
        ]);

        $token = auth('admin')->login($admin);

        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->getJson('/api/profile');

        $response->assertStatus(200)
            ->assertJsonPath('message', "Here's your admin profile")
            ->assertJsonPath('data.0.name', 'Admin User')
            ->assertJsonPath('data.0.email', 'admin@example.com');
    }

    /** @test */
    public function it_returns_401_for_invalid_token()
    {
        $response = $this->withHeaders(['Authorization' => 'Bearer invalid-token'])
            ->getJson('/api/profile');

        $response->assertStatus(401)
            ->assertJson(['error' => 'Unauthorized']);
    }

    /** @test */
    public function it_returns_401_for_no_token()
    {
        $response = $this->getJson('/api/profile');

        $response->assertStatus(401)
            ->assertJson(['error' => 'Unauthorized']);
    }
}
