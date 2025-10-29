<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SavedPostResource;
use App\Models\Post;
use App\Models\SavedPost;
use Illuminate\Http\Request;

class SavedPostController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) min($request->integer('per_page', 10) ?: 10, 100);

        $saved = $request->user()
            ->savedPosts()
            ->with(['post.author'])
            ->latest('created_at')
            ->paginate($perPage)
            ->withQueryString();

        return SavedPostResource::collection($saved);
    }

    public function store(Request $request, Post $post)
    {
        $user = $request->user();

        $saved = SavedPost::firstOrCreate([
            'post_id' => $post->id,
            'user_id' => $user->id,
        ]);

        return SavedPostResource::make($saved->load('post.author'))
            ->response()
            ->setStatusCode(201);
    }

    public function destroy(Request $request, Post $post)
    {
        $user = $request->user();

        SavedPost::where('post_id', $post->id)
            ->where('user_id', $user->id)
            ->delete();

        return response()->json([
            'message' => 'Post removed from saved list.',
        ]);
    }
}
