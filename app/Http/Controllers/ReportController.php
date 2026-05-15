<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReportRequest;
use App\Models\Mod;
use App\Models\Report;
use Illuminate\Http\RedirectResponse;

class ReportController extends Controller
{
    public function store(StoreReportRequest $request, Mod $mod): RedirectResponse
    {
        $request->user()->reports()->create([
            'mod_id' => $mod->id,
            'reason' => $request->validated('reason'),
            'message' => $request->validated('message'),
            'status' => Report::STATUS_PENDING,
        ]);

        return back()->with('status', __('messages.flash.report_submitted'));
    }
}
