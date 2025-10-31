<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Like;
use App\Models\Post;
use Illuminate\Http\Request;

class LikeController extends Controller
{
    public function store(Request $request, Post $post)
    {
        $user = $request->user();

        $post->likes()->firstOrCreate([
            'user_id' => $user->id,
        ]);

        return response()->json([
            'liked' => true,
            'likes_count' => $post->likes()->count(),
        ], 201);
    }

    public function destroy(Request $request, Post $post)
    {
        $user = $request->user();

        Like::where('post_id', $post->id)
            ->where('user_id', $user->id)
            ->delete();

        return response()->json([
            'liked' => false,
            'likes_count' => $post->likes()->count(),
        ]);
    }
}
