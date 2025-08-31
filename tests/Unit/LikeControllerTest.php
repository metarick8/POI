<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Like;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LikeControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected string $token;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        config(['jwt.secret' => env('JWT_SECRET', 'your-secret-key')]);

        $this->user = User::factory()->create();
        $this->token = auth()->login($this->user);
    }

    /** @test */
    public function it_can_like_an_article()
    {
        $article = Article::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson("/api/articles/{$article->id}/like");

        $response->assertStatus(201)
                 ->assertJsonFragment(['message' => 'Liked']);

        $this->assertDatabaseHas('likes', [
            'article_id' => $article->id,
            'user_id'    => $this->user->id,
        ]);
    }

    /** @test */
    public function it_can_unlike_an_article()
    {
        $article = Article::factory()->create();

        // Pre-like the article
        Like::create([
            'article_id' => $article->id,
            'user_id'    => $this->user->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson("/api/articles/{$article->id}/unlike");

        $response->assertStatus(200)
                 ->assertJsonFragment(['message' => 'Unliked']);

        $this->assertDatabaseMissing('likes', [
            'article_id' => $article->id,
            'user_id'    => $this->user->id,
        ]);
    }
}
