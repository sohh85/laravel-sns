<?php

namespace App\Http\Controllers;

use App\Article;
use App\Tag;
use App\Http\Requests\ArticleRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class ArticleController extends Controller
{

    // クラスのインスタンスが生成された時に初期処理として特に呼び出さなくても実行
    public function __construct()
    {
        $this->authorizeResource(Article::class, 'article');
    }

    public function index()
    {
        // 全部の記事が(sortByDescメソッドで)最新の投稿日時順に並び替えられた上で$articles に代入
        // loadメソッドを使い、リレーション先テーブルからもデータ取得。これによりユーザ記事一覧でsqlの回数を減らせる
        $articles = Article::all()->sortByDesc('created_at')
            ->load(['user', 'likes', 'tags']);

        return view('articles.index', ['articles' => $articles]);
    }

    public function create() //記事投稿
    {
        $allTagNames = Tag::all()->map(function ($tag) {
            return ['text' => $tag->name];
        });

        return view('articles.create', [
            'allTagNames' => $allTagNames,
        ]); //タグテーブルからすべてのタグを取得しbladeに渡す
    }

    // 第一引数$requestはA(ArticleRequestクラスのインスタンス)と指定
    // 指定することで、A以外のものが来たらTypeErrorという例外を発生させる
    // int とか string とかも指定できる 
    // （DI）コントローラーはメソッドの引数で型宣言を行うと、そのクラスのインスタンスが自動生成されメソッド内で使える
    public function store(ArticleRequest $request, Article $article)
    {
        // allメソッドで送信された値を配列で取得
        $article->fill($request->all());
        // リクエストのuserメソッドを使うとUserクラスのインスタンスにアクセスできる。
        // そこからユーザーのidを取得し、user_idプロパティに代入
        $article->user_id = $request->user()->id;
        // saveメソッドでarticlesテーブルにレコードを新規登録
        $article->save();
        //eachメソッドでタグの数だけ行われる。
        $request->tags->each(function ($tagName) use ($article) {
            $tag = Tag::firstOrCreate(['name' => $tagName]); //firstOrCreateメソッドで重複登録回避
            $article->tags()->attach($tag); //中間テーブルarticle_tagに保存
        });
        return redirect()->route('articles.index');
    }

    public function edit(Article $article) //記事編集
    {
        $tagNames = $article->tags->map(function ($tag) {
            return ['text' => $tag->name];
        });

        $allTagNames = Tag::all()->map(function ($tag) {
            return ['text' => $tag->name];
        });

        return view('articles.edit', [
            'article' => $article,
            'tagNames' => $tagNames,
            'allTagNames' => $allTagNames,
        ]); //タグテーブルからすべてのタグを取得しbladeに渡す
    }

    // 記事更新時の処理
    public function update(ArticleRequest $request, Article $article)
    {
        $article->fill($request->all())->save();
        $article->tags()->detach();
        $request->tags->each(function ($tagName) use ($article) {
            $tag = Tag::firstOrCreate(['name' => $tagName]);
            $article->tags()->attach($tag);
        });
        return redirect()->route('articles.index');
    }

    // 記事削除時の処理
    public function destroy(Article $article)
    {
        $article->delete();
        return redirect()->route('articles.index');
    }

    public function show(Article $article)
    {
        return view('articles.show', ['article' => $article]);
    }

    public function like(Request $request, Article $article)
    {
        $article->likes()->detach($request->user()->id); //必ず削除することで重複いいね防止
        $article->likes()->attach($request->user()->id); //attachで追加（多対多の場合）
        // $article->likesで、likesで紐づいたユーザーモデルが返る

        return [
            'id' => $article->id,
            'countLikes' => $article->count_likes,
        ];
    }

    public function unlike(Request $request, Article $article)
    {
        $article->likes()->detach($request->user()->id);

        return [
            'id' => $article->id,
            'countLikes' => $article->count_likes,
        ];
    }
}
