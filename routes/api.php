<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\CommentReportController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\LikeController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\SavedPostController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\TagController;
use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\Api\UserManagementController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

Route::prefix('v1')->group(function () {
    Route::post('auth/register', [AuthController::class, 'register']);
    Route::post('auth/login', [AuthController::class, 'login']);

    Route::post('/token', function(Request $request) {

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Bad credentials'], 401);
        }

        return [
            'token' => $user->createToken('postman')->plainTextToken,
        ];
    });

    Route::get('/test', function () {
        return 'ok';
    });


    Route::get('settings', [SettingController::class, 'show']);

    Route::get('posts', [PostController::class, 'index']);
    Route::get('posts/{post}', [PostController::class, 'show']);
    Route::get('posts/{post}/comments', [CommentController::class, 'index']);

    Route::post('messages', [MessageController::class, 'store']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me', [AuthController::class, 'me']);

        Route::get('profile', [ProfileController::class, 'show']);
        Route::match(['put', 'patch'], 'profile', [ProfileController::class, 'update']);

        Route::post('posts/{post}/comments', [CommentController::class, 'store']);
        Route::patch('comments/{comment}', [CommentController::class, 'update']);
        Route::delete('comments/{comment}', [CommentController::class, 'destroy']);

        Route::post('comments/{comment}/report', [CommentReportController::class, 'store']);

        Route::post('posts/{post}/like', [LikeController::class, 'store']);
        Route::delete('posts/{post}/like', [LikeController::class, 'destroy']);

        Route::post('posts/{post}/save', [SavedPostController::class, 'store']);
        Route::delete('posts/{post}/save', [SavedPostController::class, 'destroy']);
        Route::get('me/saved-posts', [SavedPostController::class, 'index']);
    });

    Route::middleware(['auth:sanctum', 'admin'])->group(function () {
        Route::post('posts', [PostController::class, 'store']);
        Route::match(['put', 'patch'], 'posts/{post}', [PostController::class, 'update']);
        Route::delete('posts/{post}', [PostController::class, 'destroy']);

        Route::get('admin/dashboard', DashboardController::class);

        Route::get('admin/comment-reports', [CommentReportController::class, 'index']);
        Route::patch('admin/comment-reports/{commentReport}', [CommentReportController::class, 'update']);

        Route::put('settings', [SettingController::class, 'update']);

        Route::get('admin/messages', [MessageController::class, 'index']);
        Route::get('admin/messages/{message}', [MessageController::class, 'show']);
        Route::delete('admin/messages/{message}', [MessageController::class, 'destroy']);

        Route::get('admin/users', [UserManagementController::class, 'index']);
        Route::get('admin/users/{user}', [UserManagementController::class, 'show']);
        Route::match(['put', 'patch'], 'admin/users/{user}', [UserManagementController::class, 'update']);

        Route::get('admin/media', [MediaController::class, 'index']);
        Route::post('admin/media', [MediaController::class, 'store']);
        Route::get('admin/media/{media}', [MediaController::class, 'show']);
        Route::match(['put', 'patch'], 'admin/media/{media}', [MediaController::class, 'update']);
        Route::delete('admin/media/{media}', [MediaController::class, 'destroy']);

        Route::get('admin/categories', [CategoryController::class, 'index']);
        Route::post('admin/categories', [CategoryController::class, 'store']);
        Route::get('admin/categories/{category}', [CategoryController::class, 'show']);
        Route::match(['put', 'patch'], 'admin/categories/{category}', [CategoryController::class, 'update']);
        Route::delete('admin/categories/{category}', [CategoryController::class, 'destroy']);

        Route::get('admin/tags', [TagController::class, 'index']);
        Route::post('admin/tags', [TagController::class, 'store']);
        Route::get('admin/tags/{tag}', [TagController::class, 'show']);
        Route::match(['put', 'patch'], 'admin/tags/{tag}', [TagController::class, 'update']);
        Route::delete('admin/tags/{tag}', [TagController::class, 'destroy']);
    });
});
