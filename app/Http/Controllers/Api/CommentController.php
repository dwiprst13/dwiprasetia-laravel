<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Comment\StoreCommentRequest;
use App\Http\Requests\Comment\UpdateCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CommentController extends Controller
{
    public function index(Request $request, Post $post)
    {
        $user = $request->user();

        $commentsQuery = $post->comments()
            ->whereNull('parent_id')
            ->with([
                'author',
                'replies' => function ($query) use ($user) {
                    if (! $user || ! $user->isAdmin()) {
                        $query->visible();
                    }

                    $query->with('author')->orderBy('created_at');
                },
            ])
            ->withCount('replies')
            ->orderBy('created_at');

        if (! $user || ! $user->isAdmin()) {
            $commentsQuery->visible();
        }

        $perPage = (int) min($request->integer('per_page', 10) ?: 10, 100);

        $comments = $commentsQuery->paginate($perPage)->withQueryString();

        return CommentResource::collection($comments);
    }

    public function store(StoreCommentRequest $request, Post $post)
    {
        $data = $request->validated();

        if (! empty($data['parent_id'])) {
            $parentExists = Comment::where('id', $data['parent_id'])
                ->where('post_id', $post->id)
                ->exists();

            if (! $parentExists) {
                throw ValidationException::withMessages([
                    'parent_id' => 'Parent comment does not belong to this post.',
                ]);
            }
        }

        $comment = $post->comments()->create([
            'user_id' => $request->user()->id,
            'parent_id' => $data['parent_id'] ?? null,
            'body' => $data['body'],
        ]);

        $comment->load(['author', 'replies']);

        return CommentResource::make($comment)->response()->setStatusCode(201);
    }

    public function update(UpdateCommentRequest $request, Comment $comment)
    {
        $user = $request->user();

        if (! $user->isAdmin() && $comment->user_id !== $user->id) {
            abort(403);
        }

        $comment->update($request->validated());
        $comment->load(['author', 'replies']);

        return CommentResource::make($comment);
    }

    public function destroy(Request $request, Comment $comment)
    {
        $user = $request->user();

        if (! $user->isAdmin() && $comment->user_id !== $user->id) {
            abort(403);
        }

        $comment->delete();

        return response()->json([
            'message' => 'Comment deleted.',
        ]);
    }
}
