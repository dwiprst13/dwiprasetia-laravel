<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Models\Comment;
use App\Models\CommentReport;
use App\Models\Message;
use App\Models\Post;
use App\Models\User;

class DashboardController extends Controller
{
public function __invoke()
{
    $totals = [
        'users' => User::count(),
        'admins' => User::where('role', 'admin')->count(),
        'posts' => Post::count(),
        'published_posts' => Post::where('status', 'published')->count(),
        'draft_posts' => Post::where('status', 'draft')->count(),
        'comments' => Comment::count(),
        'pending_comment_reports' => CommentReport::where('status', 'pending')->count(),
        'messages' => Message::count(),
    ];

    $recentPosts = Post::with('author')
        ->latest('created_at')
        ->limit(5)
        ->get()
        ->loadCount(['likes', 'comments']);

    $startDate = now()->subDays(6)->startOfDay();

    $usersChart = User::selectRaw('DATE(created_at) as date, COUNT(*) as count')
        ->whereDate('created_at', '>=', $startDate)
        ->groupBy('date')
        ->orderBy('date')
        ->get();
    $postsChart = Post::selectRaw('DATE(created_at) as date, COUNT(*) as count')
        ->whereDate('created_at', '>=', $startDate)
        ->groupBy('date')
        ->orderBy('date')
        ->get();

    $commentsChart = Comment::selectRaw('DATE(created_at) as date, COUNT(*) as count')
        ->whereDate('created_at', '>=', $startDate)
        ->groupBy('date')
        ->orderBy('date')
        ->get();

    return response()->json([
        'totals' => $totals,
        'recent_posts' => PostResource::collection($recentPosts),

        'chart' => [
            'users' => $usersChart,
            'posts' => $postsChart,
            'comments' => $commentsChart,
        ],
    ]);
}

}
