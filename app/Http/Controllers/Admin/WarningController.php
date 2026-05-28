<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWarningRequest;
use App\Models\User;
use App\Models\Warning;
use App\Services\WarningService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

class WarningController extends Controller
{
    public function store(StoreWarningRequest $request, User $user, WarningService $warningService): RedirectResponse
    {
        Gate::authorize('warn', User::class);

        $expiresAt = $request->filled('expires_at')
            ? Carbon::parse($request->validated('expires_at'))
            : null;

        $warningService->issueWarning(
            $user,
            (int) $request->validated('points'),
            $request->validated('reason'),
            $request->user(),
            $expiresAt,
        );

        return back()->with('status', __('messages.flash.warning_issued'));
    }

    public function destroy(Request $request, Warning $warning, WarningService $warningService): RedirectResponse
    {
        Gate::authorize('warn', User::class);

        $warningService->removeWarning($warning, $request->user());

        return back()->with('status', __('messages.flash.warning_removed'));
    }
}
