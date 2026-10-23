<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.categories.index', [
            'categories' => Category::query()->withCount('products')->orderBy('name')->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.categories.form', ['category' => new Category]);
    }

    public function store(Request $request, ActivityLogger $activityLogger): RedirectResponse
    {
        $validated = $this->validateCategory($request);
        $category = Category::create([
            ...$validated,
            'slug' => $this->uniqueSlug($validated['name']),
        ]);
        $activityLogger->record($request->user(), 'category.created', $category, "Created category {$category->name}.");

        return redirect()->route('admin.categories.index')->with('success', 'Category created.');
    }

    public function edit(Category $category): View
    {
        return view('admin.categories.form', ['category' => $category]);
    }

    public function update(Request $request, Category $category, ActivityLogger $activityLogger): RedirectResponse
    {
        $validated = $this->validateCategory($request, $category);
        $category->update([
            ...$validated,
            'slug' => $this->uniqueSlug($validated['name'], $category),
        ]);
        $activityLogger->record($request->user(), 'category.updated', $category, "Updated category {$category->name}.");

        return redirect()->route('admin.categories.index')->with('success', 'Category updated.');
    }

    public function destroy(Category $category, ActivityLogger $activityLogger): RedirectResponse
    {
        if ($category->products()->exists()) {
            return redirect()->route('admin.categories.index')->with('error', 'Move or remove products from this category before deleting it.');
        }

        $activityLogger->record(request()->user(), 'category.deleted', $category, "Deleted category {$category->name}.");
        $category->delete();

        return redirect()->route('admin.categories.index')->with('success', 'Category deleted.');
    }

    /**
     * @return array{name: string, description?: ?string, is_active: bool}
     */
    private function validateCategory(Request $request, ?Category $category = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('categories', 'name')->ignore($category?->id)],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['required', 'boolean'],
        ]);
    }

    private function uniqueSlug(string $name, ?Category $category = null): string
    {
        $slug = Str::slug($name);
        $query = Category::query()->where('slug', $slug);

        if ($category) {
            $query->where('id', '!=', $category->id);
        }

        return $query->exists() ? "{$slug}-".Str::lower(Str::random(6)) : $slug;
    }
}
