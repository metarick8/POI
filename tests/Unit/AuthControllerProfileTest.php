<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Debater;
use App\Models\User;
use App\Models\Coach;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthControllerProfileTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Ensure JWT_SECRET is set
        config(['jwt.secret' => env('JWT_SECRET', 'your-secret-key')]);
    }

    /** @test */
    public function it_returns_debater_profile_for_authenticated_debater()
    {
        // Arrange: Create a User and Debater
        $user = User::factory()->create([
            'id' => 1,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'mobile_number' => '1234567890',
            'governorate' => 'Cairo',
            'profile_picture_url' => 'http://example.com/profile.jpg',
            'birth_date' => '1990-01-01',
            'education_degree' => 'Bachelor',
            'password' => bcrypt('password'),
        ]);

        $coachUser = User::factory()->create([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
        ]);

        $coach = Coach::factory()->create([
            'id' => 2,
            'user_id' => $coachUser->id,
        ]);

        $debater = Debater::factory()->create([
            'id' => 1,
            'user_id' => $user->id,
            'coach_id' => $coach->id,
        ]);

        // Generate token for debater guard
        $token = auth('debater')->login($debater);

        // Act: Make a GET request to the profile endpoint
        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->getJson('/api/profile');

        // Assert: Check the response
        $response->assertStatus(200)
            ->assertJson([
                'message' => "Here's your debater profile",
                'data' => [[
                    'profile' => [
                        'id' => 1,
                        'first_name' => 'John',
                        'last_name' => 'Doe',
                        'email' => 'john.doe@example.com',
                        'mobile_number' => '1234567890',
                        'governorate' => 'Cairo',
                        'profile_picture_url' => 'http://example.com/profile.jpg',
                        'birth_date' => '1990-01-01',
                        'education_degree' => 'Bachelor',
                        'faculty' => null,
                        'university' => null,
                    ],
                    'coach_name' => 'Jane Smith',
                    'coach_id' => 2,
                    'debates' => '',
                    'guard' => 'debater',
                ]],
            ]);
    }

    /** @test */
    public function it_returns_admin_profile_for_authenticated_admin()
    {
        // Arrange: Create an Admin
        $admin = Admin::factory()->create([
            'id' => 1,
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        // Generate token for admin guard
        $token = auth('admin')->login($admin);

        // Act: Make a GET request to the profile endpoint
        $response = $this->withHeaders(['Authorization' => "Bearer $token"])
            ->getJson('/api/profile');

        // Assert: Check the response
        $response->assertStatus(200)
            ->assertJson([
                'message' => "Here's your admin profile",
                'data' => [[
                    'id' => 1,
                    'name' => 'Admin User',
                    'email' => 'admin@example.com',
                ]],
            ]);
    }

    /** @test */
    public function it_returns_401_for_invalid_token()
    {
        // Act: Make a GET request with an invalid token
        $response = $this->withHeaders(['Authorization' => 'Bearer invalid-token'])
            ->getJson('/api/profile');

        // Assert: Check for 401 response
        $response->assertStatus(401)
            ->assertJson(['error' => 'Invalid token']);
    }

    /** @test */
    public function it_returns_401_for_no_token()
    {
        // Act: Make a GET request without a token
        $response = $this->getJson('/api/profile');

        // Assert: Check for 401 response
        $response->assertStatus(401)
            ->assertJson(['error' => 'Token not provided']);
    }
}
