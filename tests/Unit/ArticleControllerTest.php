<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ArticleControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure JWT secret is available in test context
        config(['jwt.secret' => env('JWT_SECRET', 'your-secret-key')]);

        $user = User::factory()->create();

        // Authenticate with JWT (using User model as JWTSubject)
        $this->token = auth()->login($user);
    }

    /** @test */
    public function it_can_create_an_article()
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/articles', [
            'title'   => 'Unit Test Blog',
            'content' => 'This is content',
            'type'    => 'blog',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('articles', ['title' => 'Unit Test Blog']);
    }

    /** @test */
    public function it_can_list_articles()
    {
        Article::factory()->count(2)->create();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/articles');

        $response->assertStatus(200)
                 ->assertJsonFragment(['message' => 'Articles retrieved successfully']);
    }

    /** @test */
    public function it_can_show_single_article()
    {
        $article = Article::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson("/api/articles/{$article->id}");

        $response->assertStatus(200)
                 ->assertJsonFragment(['title' => $article->title]);
    }

    /** @test */
    public function it_can_delete_an_article()
    {
        $article = Article::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson("/api/articles/{$article->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('articles', ['id' => $article->id]);
    }
}
