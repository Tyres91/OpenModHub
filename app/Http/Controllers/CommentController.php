<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Models\Comment;
use App\Models\Mod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CommentController extends Controller
{
    public function store(StoreCommentRequest $request, Mod $mod): RedirectResponse
    {
        $request->user()->comments()->create([
            'mod_id' => $mod->id,
            'body' => $request->validated('body'),
            'status' => Comment::STATUS_VISIBLE,
        ]);

        return back()->with('status', __('messages.flash.comment_posted'));
    }

    public function hide(Request $request, Comment $comment): RedirectResponse
    {
        Gate::authorize('moderate', $comment);

        $comment->update([
            'status' => Comment::STATUS_HIDDEN,
            'moderated_by' => $request->user()->id,
            'moderated_at' => now(),
        ]);

        return back()->with('status', __('messages.flash.comment_hidden'));
    }

    public function show(Request $request, Comment $comment): RedirectResponse
    {
        Gate::authorize('moderate', $comment);

        $comment->update([
            'status' => Comment::STATUS_VISIBLE,
            'moderated_by' => $request->user()->id,
            'moderated_at' => now(),
        ]);

        return back()->with('status', __('messages.flash.comment_shown'));
    }

    public function destroy(Comment $comment): RedirectResponse
    {
        Gate::authorize('delete', $comment);

        $comment->delete();

        return back()->with('status', __('messages.flash.comment_deleted'));
    }
}
