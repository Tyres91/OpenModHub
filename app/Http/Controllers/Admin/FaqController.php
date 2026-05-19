<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReorderFaqRequest;
use App\Http\Requests\StoreFaqRequest;
use App\Http\Requests\UpdateFaqRequest;
use App\Models\Faq;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class FaqController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', Faq::class);

        return Inertia::render('Admin/Faqs/Index', [
            'faqs' => Faq::query()
                ->ordered()
                ->get()
                ->map(fn (Faq $faq): array => $this->faqPayload($faq))
                ->values(),
        ]);
    }

    public function store(StoreFaqRequest $request): RedirectResponse
    {
        $maxSortOrder = Faq::max('sort_order') ?? 0;

        Faq::query()->create([
            ...$request->validated(),
            'sort_order' => $maxSortOrder + 10,
        ]);

        return back()->with('status', __('messages.flash.faq_created'));
    }

    public function update(UpdateFaqRequest $request, Faq $faq): RedirectResponse
    {
        $faq->update($request->validated());

        return back()->with('status', __('messages.flash.faq_updated'));
    }

    public function destroy(Faq $faq): RedirectResponse
    {
        Gate::authorize('delete', $faq);

        $faq->delete();

        return back()->with('status', __('messages.flash.faq_deleted'));
    }

    public function reorder(ReorderFaqRequest $request): RedirectResponse
    {
        foreach ($request->validated('faqs') as $faqData) {
            Faq::where('id', $faqData['id'])->update(['sort_order' => $faqData['sort_order']]);
        }

        return back()->with('status', __('messages.flash.faq_order_updated'));
    }

    /**
     * @return array<string, mixed>
     */
    private function faqPayload(Faq $faq): array
    {
        return [
            'id' => $faq->id,
            'question_en' => $faq->question_en,
            'question_de' => $faq->question_de,
            'answer_en' => $faq->answer_en,
            'answer_de' => $faq->answer_de,
            'sort_order' => $faq->sort_order,
            'is_active' => $faq->is_active,
            'created_at' => $faq->created_at->toISOString(),
            'updated_at' => $faq->updated_at->toISOString(),
        ];
    }
}
