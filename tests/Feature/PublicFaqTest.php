<?php

namespace Tests\Feature;

use App\Models\Faq;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicFaqTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_faq_page_is_accessible(): void
    {
        $response = $this->get(route('faqs.index'));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Faqs/Index')
                ->has('faqs')
            );
    }

    public function test_public_page_shows_only_active_faqs(): void
    {
        Faq::query()->create([
            'question_en' => 'Active question',
            'question_de' => 'Aktive Frage',
            'answer_en' => '<p>Active answer</p>',
            'answer_de' => '<p>Aktive Antwort</p>',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        Faq::query()->create([
            'question_en' => 'Inactive question',
            'question_de' => 'Inaktive Frage',
            'answer_en' => '<p>Inactive answer</p>',
            'answer_de' => '<p>Inaktive Antwort</p>',
            'sort_order' => 2,
            'is_active' => false,
        ]);

        $response = $this->get(route('faqs.index'));

        $response->assertInertia(fn ($page) => $page
            ->has('faqs', 1)
            ->where('faqs.0.question', 'Active question')
        );
    }

    public function test_public_page_shows_english_content_by_default(): void
    {
        Faq::query()->create([
            'question_en' => 'English question',
            'question_de' => 'German question',
            'answer_en' => '<p>English answer</p>',
            'answer_de' => '<p>German answer</p>',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $response = $this->get(route('faqs.index'));

        $response->assertInertia(fn ($page) => $page
            ->has('faqs', 1)
            ->where('faqs.0.question', 'English question')
            ->where('faqs.0.answer', '<p>English answer</p>')
        );
    }

    public function test_public_page_shows_german_content_when_locale_is_de(): void
    {
        Faq::query()->create([
            'question_en' => 'English question',
            'question_de' => 'German question',
            'answer_en' => '<p>English answer</p>',
            'answer_de' => '<p>German answer</p>',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $response = $this->withSession(['locale' => 'de'])->get(route('faqs.index'));

        $response->assertInertia(fn ($page) => $page
            ->has('faqs', 1)
            ->where('faqs.0.question', 'German question')
            ->where('faqs.0.answer', '<p>German answer</p>')
        );
    }

    public function test_faqs_are_ordered_by_sort_order(): void
    {
        Faq::query()->create([
            'question_en' => 'Third',
            'question_de' => 'Dritte',
            'answer_en' => '<p>Third answer</p>',
            'answer_de' => '<p>Dritte Antwort</p>',
            'sort_order' => 30,
            'is_active' => true,
        ]);

        Faq::query()->create([
            'question_en' => 'First',
            'question_de' => 'Erste',
            'answer_en' => '<p>First answer</p>',
            'answer_de' => '<p>Erste Antwort</p>',
            'sort_order' => 10,
            'is_active' => true,
        ]);

        Faq::query()->create([
            'question_en' => 'Second',
            'question_de' => 'Zweite',
            'answer_en' => '<p>Second answer</p>',
            'answer_de' => '<p>Zweite Antwort</p>',
            'sort_order' => 20,
            'is_active' => true,
        ]);

        $response = $this->get(route('faqs.index'));

        $response->assertInertia(fn ($page) => $page
            ->has('faqs', 3)
            ->where('faqs.0.question', 'First')
            ->where('faqs.1.question', 'Second')
            ->where('faqs.2.question', 'Third')
        );
    }

    public function test_public_page_shows_empty_state_when_no_faqs(): void
    {
        $response = $this->get(route('faqs.index'));

        $response->assertInertia(fn ($page) => $page
            ->has('faqs', 0)
        );
    }
}
