<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Comment\ReportCommentRequest;
use App\Http\Requests\Comment\UpdateCommentReportStatusRequest;
use App\Http\Resources\CommentReportResource;
use App\Models\Comment;
use App\Models\CommentReport;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CommentReportController extends Controller
{
    public function index(Request $request)
    {
        $reports = CommentReport::query()
            ->with([
                'comment.post',
                'comment.author',
                'reporter',
                'handler',
            ])
            ->latest()
            ->paginate((int) min($request->integer('per_page', 15) ?: 15, 100))
            ->withQueryString();

        return CommentReportResource::collection($reports);
    }

    public function store(ReportCommentRequest $request, Comment $comment)
    {
        $user = $request->user();

        if ($comment->user_id === $user->id) {
            throw ValidationException::withMessages([
                'comment' => 'You cannot report your own comment.',
            ]);
        }

        $data = $request->validated();

        $payload = [
            'comment_id' => $comment->id,
            'reported_by' => $user->id,
        ];

        $report = CommentReport::firstOrNew($payload);

        if (! empty($data['reason'])) {
            $report->reason = $data['reason'];
        }

        if (! $report->exists) {
            $report->status = 'pending';
        }

        $report->save();

        $this->syncReportsCount($comment);

        return CommentReportResource::make($report->load([
            'comment.post',
            'comment.author',
            'reporter',
            'handler',
        ]))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateCommentReportStatusRequest $request, CommentReport $commentReport)
    {
        $data = $request->validated();

        $commentReport->fill([
            'status' => $data['status'],
        ]);

        if ($commentReport->status === 'pending') {
            $commentReport->handled_by = null;
            $commentReport->handled_at = null;
        } else {
            $commentReport->handled_by = $request->user()->id;
            $commentReport->handled_at = now();
        }

        $commentReport->save();

        $moderationAction = $data['moderation_action'] ?? null;

        if ($moderationAction === 'hide') {
            $commentReport->comment->update(['status' => 'hidden']);
        } elseif ($moderationAction === 'restore') {
            $commentReport->comment->update(['status' => 'visible']);
        }

        $this->syncReportsCount($commentReport->comment);

        return CommentReportResource::make($commentReport->load([
            'comment.post',
            'comment.author',
            'reporter',
            'handler',
        ]));
    }

    protected function syncReportsCount(Comment $comment): void
    {
        $pendingCount = $comment->reports()->where('status', 'pending')->count();
        $comment->update(['reports_count' => $pendingCount]);
    }
}
