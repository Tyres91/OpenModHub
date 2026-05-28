<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\WarningService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsNotBlocked
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return $next($request);
        }

        if ($user->blocked_until !== null && $user->blocked_until->isPast() && $user->blocked_at !== null) {
            $user->update([
                'blocked_at' => null,
                'blocked_until' => null,
                'blocked_by' => null,
                'block_reason' => null,
            ]);
            $user->refresh();
        }

        if ($user->isBlocked()) {
            Auth::guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $message = __('messages.auth.account_blocked');

            if ($user->blocked_until !== null && $user->blocked_until->isFuture()) {
                $message = __('messages.sanctions.account_locked', [
                    'date' => $user->blocked_until->format('d.m.Y H:i'),
                    'reason' => $user->block_reason ?? '',
                ]);
            } elseif ($user->block_reason !== null) {
                $message = __('messages.sanctions.account_locked_permanent', [
                    'reason' => $user->block_reason,
                ]);
            }

            return redirect('/')->with('error', $message);
        }

        $warningService = app(WarningService::class);
        $accountLock = $warningService->getActiveAccountLock($user);

        if ($accountLock !== null) {
            Auth::guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $message = $accountLock->expires_at !== null && $accountLock->expires_at->isFuture()
                ? __('messages.sanctions.account_locked', [
                    'date' => $accountLock->expires_at->format('d.m.Y H:i'),
                    'reason' => $accountLock->reason,
                ])
                : __('messages.sanctions.account_locked_permanent', [
                    'reason' => $accountLock->reason,
                ]);

            return redirect('/')->with('error', $message);
        }

        return $next($request);
    }
}
