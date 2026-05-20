<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\BlockUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Permission;
use App\Models\Rank;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', User::class);

        $users = User::query()
            ->with(['specialRank', 'roles', 'permissions'])
            ->withCount(['mods as mods_count'])
            ->latest()
            ->get()
            ->map(fn (User $user): array => $this->userPayload($user))
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
            'blocked_by' => $request->user()->id,
            'block_reason' => $request->validated('block_reason'),
        ]);

        return back()->with('status', __('messages.flash.user_blocked'));
    }

    public function unblock(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('update', $user);

        $user->update([
            'blocked_at' => null,
            'blocked_by' => null,
            'block_reason' => null,
        ]);

        return back()->with('status', __('messages.flash.user_unblocked'));
    }

    /**
     * @return array<string, mixed>
     */
    private function userPayload(User $user): array
    {
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
            'blocked_by' => $user->blocked_by,
            'block_reason' => $user->block_reason,
            'mods_count' => $user->mods_count ?? 0,
            'created_at' => $user->created_at->toISOString(),
        ];
    }
}
