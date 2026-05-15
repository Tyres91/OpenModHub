<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Rank;
use App\Models\RankPointRule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class RankPointRuleController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', Rank::class);

        return Inertia::render('Admin/RankPointRules/Index', [
            'pointRules' => RankPointRule::query()
                ->orderBy('id')
                ->get()
                ->map(fn (RankPointRule $rule): array => $this->pointRulePayload($rule))
                ->values(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        Gate::authorize('viewAny', Rank::class);

        $validated = $request->validate([
            'rules' => ['required', 'array'],
            'rules.*.key' => ['required', 'string', 'exists:rank_point_rules,key'],
            'rules.*.points' => ['required', 'integer', 'min:0', 'max:100000000'],
            'rules.*.threshold' => ['nullable', 'integer', 'min:1', 'max:100000000'],
            'rules.*.is_enabled' => ['boolean'],
        ]);

        foreach ($validated['rules'] as $ruleData) {
            RankPointRule::query()
                ->where('key', $ruleData['key'])
                ->update([
                    'points' => $ruleData['points'],
                    'threshold' => $ruleData['threshold'] ?? null,
                    'is_enabled' => (bool) ($ruleData['is_enabled'] ?? false),
                ]);
        }

        return back()->with('status', __('messages.flash.rank_point_rules_updated'));
    }

    /** @return array<string, mixed> */
    private function pointRulePayload(RankPointRule $rule): array
    {
        return [
            'id' => $rule->id,
            'key' => $rule->key,
            'label' => $rule->label,
            'points' => $rule->points,
            'threshold' => $rule->threshold,
            'is_enabled' => $rule->is_enabled,
        ];
    }
}
