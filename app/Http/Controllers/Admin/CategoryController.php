<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReorderCategoryRequest;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', Category::class);

        return Inertia::render('Admin/Categories/Index', [
            'categories' => Category::query()
                ->withCount('mods')
                ->ordered()
                ->get()
                ->map(fn (Category $category): array => $this->categoryPayload($category))
                ->values(),
        ]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $maxSortOrder = Category::query()->max('sort_order') ?? 0;

        Category::query()->create([
            ...$request->validated(),
            'slug' => $this->uniqueSlug($request->string('name')->toString()),
            'sort_order' => $maxSortOrder + 10,
        ]);

        return back()->with('status', __('messages.flash.category_created'));
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated());

        return back()->with('status', __('messages.flash.category_updated'));
    }

    public function destroy(Category $category): RedirectResponse
    {
        Gate::authorize('delete', $category);

        if ($category->mods()->exists()) {
            return back()->with('error', __('messages.flash.category_not_deletable'));
        }

        $category->delete();

        return back()->with('status', __('messages.flash.category_deleted'));
    }

    public function reorder(ReorderCategoryRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            foreach ($request->validated('categories') as $category) {
                Category::query()
                    ->whereKey($category['id'])
                    ->update(['sort_order' => $category['sort_order']]);
            }
        });

        return back()->with('status', __('messages.flash.category_order_updated'));
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'category';
        $slug = $base;
        $counter = 2;

        while (Category::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * @return array<string, mixed>
     */
    private function categoryPayload(Category $category): array
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'description' => $category->description,
            'is_active' => $category->is_active,
            'sort_order' => $category->sort_order,
            'mods_count' => $category->mods_count,
            'created_at' => $category->created_at->toISOString(),
            'updated_at' => $category->updated_at->toISOString(),
        ];
    }
}
