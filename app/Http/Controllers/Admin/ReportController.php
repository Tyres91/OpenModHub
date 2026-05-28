<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', Report::class);

        $reports = Report::with(['user', 'mod', 'reviewer'])
            ->latest()
            ->paginate(20)
            ->through(fn (Report $report) => [
                'id' => $report->id,
                'reason' => $report->reason,
                'message' => $report->message,
                'status' => $report->status,
                'created_at' => $report->created_at->format('Y-m-d H:i'),
                'user' => [
                    'id' => $report->user->id,
                    'name' => $report->user->name,
                ],
                'mod' => [
                    'id' => $report->mod->id,
                    'title' => $report->mod->title,
                    'slug' => $report->mod->slug,
                ],
                'reviewer' => $report->reviewer ? [
                    'id' => $report->reviewer->id,
                    'name' => $report->reviewer->name,
                ] : null,
            ]);

        return Inertia::render('Admin/Reports/Index', [
            'reports' => $reports,
        ]);
    }

    public function resolve(Request $request, Report $report): RedirectResponse
    {
        Gate::authorize('review', $report);

        $report->update([
            'status' => Report::STATUS_RESOLVED,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return back()->with('status', __('messages.flash.report_resolved'));
    }

    public function dismiss(Request $request, Report $report): RedirectResponse
    {
        Gate::authorize('review', $report);

        $report->update([
            'status' => Report::STATUS_DISMISSED,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return back()->with('status', __('messages.flash.report_dismissed'));
    }
}
