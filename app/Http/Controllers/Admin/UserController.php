<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\BlockUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Permission;
use App\Models\Rank;
use App\Models\Role;
use App\Models\User;
use App\Models\UserSanction;
use App\Models\Warning;
use App\Services\WarningService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(WarningService $warningService): Response
    {
        Gate::authorize('viewAny', User::class);

        $users = User::query()
            ->with([
                'specialRank',
                'roles',
                'permissions',
                'warnings.issuer:id,name',
                'warnings.remover:id,name',
                'sanctions.issuer:id,name',
                'sanctions.remover:id,name',
            ])
            ->withCount(['mods as mods_count'])
            ->latest()
            ->get()
            ->map(fn (User $user) => $this->userPayload($user, $warningService))
            ->values();

        $roles = Role::query()->orderBy('name')->get(['id', 'name', 'slug']);

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'roles' => $roles,
            'permissions' => Permission::query()->orderBy('group')->orderBy('name')->get(['id', 'name', 'slug', 'group']),
            'specialRanks' => Rank::query()
                ->where('is_special', true)
                ->orderBy('name')
                ->get(['id', 'name', 'color', 'icon']),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->safe()->only(['name', 'email', 'locale', 'rank_id']);

        if ($request->input('rank_id') === '') {
            $data['rank_id'] = null;
        }

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        if ($request->has('roles')) {
            $roleSlugs = collect($request->input('roles', []))->map(fn ($slug) => strtolower($slug))->all();
            $roleIds = Role::query()->whereIn('slug', $roleSlugs)->pluck('id')->all();
            $user->roles()->sync($roleIds);
        }

        if ($request->has('permissions')) {
            $permissionSlugs = collect($request->input('permissions', []))->map(fn ($slug) => strtolower($slug))->all();
            $permissionIds = Permission::query()->whereIn('slug', $permissionSlugs)->pluck('id')->all();
            $user->permissions()->sync($permissionIds);
        }

        return back()->with('status', __('messages.flash.user_updated'));
    }

    public function block(BlockUserRequest $request, User $user): RedirectResponse
    {
        $user->update([
            'blocked_at' => now(),
            'blocked_until' => $request->validated('blocked_until'),
            'blocked_by' => $request->user()->id,
            'block_reason' => $request->validated('block_reason'),
        ]);

        return back()->with('status', __('messages.flash.user_blocked'));
    }

    public function unblock(User $user): RedirectResponse
    {
        Gate::authorize('update', $user);

        $user->update([
            'blocked_at' => null,
            'blocked_until' => null,
            'blocked_by' => null,
            'block_reason' => null,
        ]);

        return back()->with('status', __('messages.flash.user_unblocked'));
    }

    public function destroy(User $user): RedirectResponse
    {
        Gate::authorize('delete', $user);

        $user->delete();

        return back()->with('status', __('messages.admin.users.user_deleted'));
    }

    /**
     * @return array<string, mixed>
     */
    private function userPayload(User $user, WarningService $warningService): array
    {
        $warnings = $user->warnings
            ->sortByDesc('created_at')
            ->values()
            ->map(fn (Warning $warning): array => [
                'id' => $warning->id,
                'points' => $warning->points,
                'reason' => $warning->reason,
                'status' => $warning->isActive() ? 'active' : ($warning->status === Warning::STATUS_REMOVED ? 'removed' : 'expired'),
                'issued_by' => $warning->issuer?->name,
                'issued_at' => $warning->created_at->toISOString(),
                'expires_at' => $warning->expires_at?->toISOString(),
                'removed_by' => $warning->remover?->name,
                'removed_at' => $warning->removed_at?->toISOString(),
            ])
            ->all();

        $sanctions = $user->sanctions
            ->sortByDesc('created_at')
            ->values()
            ->map(fn (UserSanction $sanction): array => [
                'id' => $sanction->id,
                'type' => $sanction->type,
                'reason' => $sanction->reason,
                'active' => $sanction->isActive(),
                'issued_by' => $sanction->issuer?->name,
                'issued_at' => $sanction->created_at->toISOString(),
                'expires_at' => $sanction->expires_at?->toISOString(),
                'removed_by' => $sanction->remover?->name,
                'removed_at' => $sanction->removed_at?->toISOString(),
            ])
            ->all();

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'locale' => $user->locale,
            'roles' => $user->roles->pluck('slug')->all(),
            'permissions' => $user->permissions->pluck('slug')->all(),
            'rank_id' => $user->rank_id,
            'special_rank' => $user->specialRank ? [
                'id' => $user->specialRank->id,
                'name' => $user->specialRank->name,
                'color' => $user->specialRank->color,
                'icon' => $user->specialRank->icon,
            ] : null,
            'email_verified_at' => $user->email_verified_at?->toISOString(),
            'blocked_at' => $user->blocked_at?->toISOString(),
            'blocked_until' => $user->blocked_until?->toISOString(),
            'blocked_by' => $user->blocked_by,
            'block_reason' => $user->block_reason,
            'mods_count' => $user->mods_count ?? 0,
            'created_at' => $user->created_at->toISOString(),
            'active_warning_points' => $warningService->getActivePoints($user),
            'warnings' => $warnings,
            'sanctions' => $sanctions,
        ];
    }
}
