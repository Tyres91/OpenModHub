<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use Inertia\Inertia;
use Inertia\Response;

class FaqController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Faqs/Index', [
            'faqs' => Faq::query()
                ->active()
                ->ordered()
                ->get()
                ->map(fn (Faq $faq): array => [
                    'id' => $faq->id,
                    'question' => $faq->getQuestion(),
                    'answer' => $faq->getAnswer(),
                ])
                ->values(),
        ]);
    }
}
