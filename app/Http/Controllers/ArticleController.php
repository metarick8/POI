<?php

namespace App\Http\Controllers;

use App\JSONResponseTrait;
use App\Models\Article;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    use JSONResponseTrait;
    // create article
    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string',
            'content' => 'required|string',
            'type' => 'required|in:blog,course',
        ]);

        $article = Article::create($data);
        return $this->successResponse('Article created successfully',$article,201);
    }

    // delete article
    public function destroy(Article $article)
    {
        $article->delete();
        return $this->successResponse('Article deleted successfully',$article);
    }

    // list articles with like count
    public function index()
    {
        $articles = Article::withCount('likes')->get();
        return $this->successResponse('Articles retrieved successfully',$articles);
    }

    // get single article with like count
    public function show(Article $article)
    {
        $article->loadCount('likes');
        return $this->successResponse('Article retrieved successfully',$article);
    }
}

