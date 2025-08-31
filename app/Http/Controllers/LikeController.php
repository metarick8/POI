<?php

namespace App\Http\Controllers;

use App\JSONResponseTrait;
use App\Models\Article;
use App\Models\Like;
use Illuminate\Http\Request;

class LikeController extends Controller
{
    use JSONResponseTrait;
    // like an article
    public function like(Request $request, Article $article)
    {
        $userId = $request->user()->id;

        $like = Like::firstOrCreate([
            'article_id' => $article->id,
            'user_id' => $userId,
        ]);

        return $this->successResponse('Liked',$like,201);
    }

    // unlike an article
    public function unlike(Request $request, Article $article)
    {
        $userId = $request->user()->id;

        $like = Like::where('article_id', $article->id)
            ->where('user_id', $userId)
            ->delete();

        return $this->successResponse('Unliked',$like);
    }
}
