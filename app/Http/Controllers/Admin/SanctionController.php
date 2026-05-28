<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSanctionRequest;
use App\Models\User;
use App\Models\UserSanction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SanctionController extends Controller
{
    public function store(StoreSanctionRequest $request, User $user): RedirectResponse
    {
        Gate::authorize('sanction', User::class);

        UserSanction::create([
            'user_id' => $user->id,
            'type' => $request->validated('type'),
            'reason' => $request->validated('reason'),
            'issued_by' => $request->user()->id,
            'expires_at' => $request->validated('expires_at'),
        ]);

        return back()->with('status', __('messages.flash.sanction_created'));
    }

    public function destroy(Request $request, UserSanction $sanction): RedirectResponse
    {
        Gate::authorize('sanction', User::class);

        $sanction->update([
            'removed_by' => $request->user()->id,
            'removed_at' => now(),
        ]);

        return back()->with('status', __('messages.flash.sanction_removed'));
    }
}
