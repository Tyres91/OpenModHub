<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateEmailTemplateRequest;
use App\Models\EmailTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class EmailTemplateController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', EmailTemplate::class);

        $templates = EmailTemplate::query()
            ->orderBy('key')
            ->get()
            ->map(fn (EmailTemplate $template): array => $this->templatePayload($template));

        return Inertia::render('Admin/EmailTemplates/Index', [
            'templates' => $templates,
        ]);
    }

    public function update(UpdateEmailTemplateRequest $request, EmailTemplate $emailTemplate): RedirectResponse
    {
        Gate::authorize('update', $emailTemplate);

        $validated = $request->validated();

        $emailTemplate->update([
            'subject_en' => $validated['subject_en'],
            'subject_de' => $validated['subject_de'],
            'body_en' => $validated['body_en'],
            'body_de' => $validated['body_de'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('status', __('messages.flash.email_template_updated'));
    }

    /** @return array<string, mixed> */
    private function templatePayload(EmailTemplate $template): array
    {
        return [
            'id' => $template->id,
            'key' => $template->key,
            'subject_en' => $template->subject_en,
            'subject_de' => $template->subject_de,
            'body_en' => $template->body_en,
            'body_de' => $template->body_de,
            'is_active' => (bool) $template->is_active,
            'placeholders' => $template->available_placeholders,
            'updated_at' => $template->updated_at->toISOString(),
        ];
    }
}
