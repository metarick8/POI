<?php

namespace Tests\Unit;

use App\Http\Controllers\AuthController;
use App\Http\Resources\AdminResource;
use App\Http\Resources\DebaterResource;
use App\Models\Admin;
use App\Models\Debater;
use App\Models\User;
use App\Models\Coach;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Mockery;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * @covers \App\Http\Controllers\AuthController::profile
 */
class AuthControllerProfileTest extends TestCase
{
    protected $controller;
    protected $authServiceMock;
    protected $jwtAuthMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock AuthService
        $this->authServiceMock = Mockery::mock(AuthService::class);
        $this->app->instance(AuthService::class, $this->authServiceMock);

        // Initialize the controller with the mocked AuthService
        $this->controller = new AuthController($this->authServiceMock);

        // Mock JWTAuth
        $this->jwtAuthMock = Mockery::mock('Tymon\JWTAuth\JWTAuth');
        $this->app->instance('jwt.auth', $this->jwtAuthMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_returns_debater_profile_for_authenticated_user()
    {
        // Arrange: Mock User, Debater, and Coach
        $user = Mockery::mock(User::class);
        $user->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $user->shouldReceive('getAttribute')->with('first_name')->andReturn('John');
        $user->shouldReceive('getAttribute')->with('last_name')->andReturn('Doe');
        $user->shouldReceive('getAttribute')->with('email')->andReturn('john.doe@example.com');
        $user->shouldReceive('getAttribute')->with('mobile_number')->andReturn('1234567890');
        $user->shouldReceive('getAttribute')->with('governorate')->andReturn('Cairo');
        $user->shouldReceive('getAttribute')->with('profile_picture_url')->andReturn('http://example.com/profile.jpg');
        $user->shouldReceive('getAttribute')->with('birth_date')->andReturn('1990-01-01');
        $user->shouldReceive('getAttribute')->with('education_degree')->andReturn('Bachelor');
        $user->shouldReceive('getAttribute')->with('faculty')->andReturn(null);

        $coach = Mockery::mock(Coach::class);
        $coach->shouldReceive('getAttribute')->with('id')->andReturn(2);
        $coach->user = Mockery::mock(User::class);
        $coach->user->shouldReceive('getAttribute')->with('first_name')->andReturn('Jane');
        $coach->user->shouldReceive('getAttribute')->with('last_name')->andReturn('Smith');

        $debater = Mockery::mock(Debater::class);
        $debater->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $debater->shouldReceive('getAttribute')->with('user_id')->andReturn(1);
        $debater->user = $user;
        $debater->coach = $coach;

        // Mock JWTAuth::parseToken()->authenticate()
        $this->jwtAuthMock->shouldReceive('parseToken')->andReturnSelf();
        $this->jwtAuthMock->shouldReceive('authenticate')->andReturn($user);

        // Mock getAuthenticatedActor to return debater
        $debaterResource = new DebaterResource($debater);
        $this->mockGetAuthenticatedActor(1, ['debater', $debaterResource]);

        // Act: Call the profile method
        $response = $this->controller->profile();

        // Assert: Check the response
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals([
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
        ], $response->getData(true));
    }

    /** @test */
    public function it_returns_404_for_unauthenticated_user()
    {
        // Arrange: Mock JWTAuth to return null for authenticate
        $this->jwtAuthMock->shouldReceive('parseToken')->andReturnSelf();
        $this->jwtAuthMock->shouldReceive('authenticate')->andReturn(null);

        // Act: Call the profile method
        $response = $this->controller->profile();

        // Assert: Check for 404 response
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals([
            'error' => 'User not found',
        ], $response->getData(true));
    }

    /** @test */
    public function it_returns_admin_profile_for_authenticated_admin()
    {
        // Arrange: Mock Admin
        $admin = Mockery::mock(Admin::class);
        $admin->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $admin->shouldReceive('getAttribute')->with('name')->andReturn('Admin User');
        $admin->shouldReceive('getAttribute')->with('email')->andReturn('admin@example.com');

        // Mock JWTAuth::parseToken()->authenticate()
        $this->jwtAuthMock->shouldReceive('parseToken')->andReturnSelf();
        $this->jwtAuthMock->shouldReceive('authenticate')->andReturn($admin);

        // Mock getAuthenticatedActor to return admin
        $adminResource = new AdminResource($admin);
        $this->mockGetAuthenticatedActor(1, ['admin', $adminResource]);

        // Act: Call the profile method
        $response = $this->controller->profile();

        // Assert: Check the response
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals([
            'message' => "Here's your admin profile",
            'data' => [[
                'id' => 1,
                'name' => 'Admin User',
                'email' => 'admin@example.com',
            ]],
        ], $response->getData(true));
    }

    /** @test */
    public function it_handles_token_invalid_exception()
    {
        // Arrange: Mock JWTAuth to throw TokenInvalidException
        $this->jwtAuthMock->shouldReceive('parseToken')
            ->andThrow(new \Tymon\JWTAuth\Exceptions\TokenInvalidException('Invalid token'));

        // Act: Call the profile method
        $response = $this->controller->profile();

        // Assert: Check for 401 response
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals([
            'error' => 'Invalid token',
        ], $response->getData(true));
    }

    /** @test */
    public function it_handles_token_not_provided_exception()
    {
        // Arrange: Mock JWTAuth to throw JWTException
        $this->jwtAuthMock->shouldReceive('parseToken')
            ->andThrow(new \Tymon\JWTAuth\Exceptions\JWTException('Token not provided'));

        // Act: Call the profile method
        $response = $this->controller->profile();

        // Assert: Check for 401 response
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals([
            'error' => 'Token not provided',
        ], $response->getData(true));
    }

    /**
     * Mock the getAuthenticatedActor method.
     *
     * @param int $userId
     * @param array $returnValue
     * @return void
     */
    protected function mockGetAuthenticatedActor($userId, $returnValue)
    {
        $controllerMock = Mockery::mock(AuthController::class, [$this->authServiceMock])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $controllerMock->shouldReceive('getAuthenticatedActor')
            ->with($userId)
            ->andReturn($returnValue);

        $this->app->instance(AuthController::class, $controllerMock);
        $this->controller = $controllerMock;
    }
}
