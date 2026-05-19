<?php

namespace Tests\Feature;

use App\Models\Faq;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminFaqTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_faq(): void
    {
        $admin = $this->userWithRole('admin');

        $response = $this->actingAs($admin)->post(route('admin.faqs.store'), [
            'question_en' => 'How do I submit a mod?',
            'question_de' => 'Wie reiche ich einen Mod ein?',
            'answer_en' => '<p>Go to the <strong>Submit Mod</strong> page and fill out the form.</p>',
            'answer_de' => '<p>Gehe zur Seite <strong>Mod einreichen</strong> und fülle das Formular aus.</p>',
            'is_active' => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('faqs', [
            'question_en' => 'How do I submit a mod?',
            'question_de' => 'Wie reiche ich einen Mod ein?',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('faqs', [
            'question_en' => 'How do I submit a mod?',
            'sort_order' => 10,
        ]);
    }

    public function test_admin_can_update_faq(): void
    {
        $admin = $this->userWithRole('admin');
        $faq = Faq::query()->create([
            'question_en' => 'Old question?',
            'question_de' => 'Alte Frage?',
            'answer_en' => '<p>Old answer.</p>',
            'answer_de' => '<p>Alte Antwort.</p>',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.faqs.update', $faq), [
            'question_en' => 'New question?',
            'question_de' => 'Neue Frage?',
            'answer_en' => '<p>New answer.</p>',
            'answer_de' => '<p>Neue Antwort.</p>',
            'is_active' => false,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('faqs', [
            'id' => $faq->id,
            'question_en' => 'New question?',
            'is_active' => false,
        ]);
    }

    public function test_admin_can_delete_faq(): void
    {
        $admin = $this->userWithRole('admin');
        $faq = Faq::query()->create([
            'question_en' => 'Question?',
            'question_de' => 'Frage?',
            'answer_en' => '<p>Answer.</p>',
            'answer_de' => '<p>Antwort.</p>',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.faqs.destroy', $faq));

        $response->assertRedirect();
        $this->assertDatabaseMissing('faqs', ['id' => $faq->id]);
    }

    public function test_admin_can_view_faqs_index(): void
    {
        $admin = $this->userWithRole('admin');

        Faq::query()->create([
            'question_en' => 'Question 1',
            'question_de' => 'Frage 1',
            'answer_en' => '<p>Answer 1</p>',
            'answer_de' => '<p>Antwort 1</p>',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.faqs.index'));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Faqs/Index')
                ->has('faqs', 1)
            );
    }

    public function test_editor_cannot_manage_faqs(): void
    {
        $editor = $this->userWithRole('editor');

        $this->actingAs($editor)
            ->get(route('admin.faqs.index'))
            ->assertForbidden();

        $this->actingAs($editor)
            ->post(route('admin.faqs.store'), [
                'question_en' => 'Question',
                'question_de' => 'Frage',
                'answer_en' => '<p>Answer</p>',
                'answer_de' => '<p>Antwort</p>',
            ])
            ->assertForbidden();
    }

    public function test_user_cannot_manage_faqs(): void
    {
        $user = $this->userWithRole('user');

        $this->actingAs($user)
            ->get(route('admin.faqs.index'))
            ->assertForbidden();
    }

    public function test_validation_requires_english_and_german_fields(): void
    {
        $admin = $this->userWithRole('admin');

        $response = $this->actingAs($admin)->post(route('admin.faqs.store'), [
            'question_en' => '',
            'question_de' => '',
            'answer_en' => '',
            'answer_de' => '',
        ]);

        $response->assertSessionHasErrors(['question_en', 'question_de', 'answer_en', 'answer_de']);
    }

    public function test_validation_rejects_excessive_question_length(): void
    {
        $admin = $this->userWithRole('admin');

        $response = $this->actingAs($admin)->post(route('admin.faqs.store'), [
            'question_en' => str_repeat('a', 501),
            'question_de' => str_repeat('a', 501),
            'answer_en' => '<p>Valid answer.</p>',
            'answer_de' => '<p>Gültige Antwort.</p>',
        ]);

        $response->assertSessionHasErrors(['question_en', 'question_de']);
    }

    public function test_admin_can_reorder_faqs(): void
    {
        $admin = $this->userWithRole('admin');

        $faq1 = Faq::query()->create([
            'question_en' => 'First',
            'question_de' => 'Erste',
            'answer_en' => '<p>Answer 1</p>',
            'answer_de' => '<p>Antwort 1</p>',
            'sort_order' => 10,
            'is_active' => true,
        ]);

        $faq2 = Faq::query()->create([
            'question_en' => 'Second',
            'question_de' => 'Zweite',
            'answer_en' => '<p>Answer 2</p>',
            'answer_de' => '<p>Antwort 2</p>',
            'sort_order' => 20,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.faqs.reorder'), [
            'faqs' => [
                ['id' => $faq2->id, 'sort_order' => 10],
                ['id' => $faq1->id, 'sort_order' => 20],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('faqs', ['id' => $faq2->id, 'sort_order' => 10]);
        $this->assertDatabaseHas('faqs', ['id' => $faq1->id, 'sort_order' => 20]);
    }

    public function test_editor_cannot_reorder_faqs(): void
    {
        $editor = $this->userWithRole('editor');

        $this->actingAs($editor)
            ->patch(route('admin.faqs.reorder'), [
                'faqs' => [
                    ['id' => 1, 'sort_order' => 10],
                ],
            ])
            ->assertForbidden();
    }

    private function userWithRole(string $slug): User
    {
        $role = Role::query()->firstOrCreate([
            'slug' => $slug,
        ], [
            'name' => ucfirst($slug),
        ]);

        $user = User::factory()->create();
        $user->roles()->attach($role);

        return $user;
    }
}
