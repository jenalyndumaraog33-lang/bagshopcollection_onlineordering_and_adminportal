<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return CategoryResource::collection(
            Category::query()->where('is_active', true)->orderBy('name')->paginate(50)
        );
    }

    public function show(Category $category): CategoryResource
    {
        abort_unless($category->is_active, 404);

        return CategoryResource::make($category);
    }

    public function store(Request $request, ActivityLogger $activityLogger): JsonResponse
    {
        $validated = $this->validatedCategory($request);
        $category = Category::create([
            ...$validated,
            'slug' => Str::slug($validated['name']).'-'.Str::lower(Str::random(6)),
        ]);
        $activityLogger->record($request->user(), 'category.created', $category, "Created category {$category->name} via API.");

        return CategoryResource::make($category)->response()->setStatusCode(201);
    }

    public function update(Request $request, Category $category, ActivityLogger $activityLogger): CategoryResource
    {
        $validated = $this->validatedCategory($request, $category);
        $category->update([
            ...$validated,
            'slug' => Str::slug($validated['name']).'-'.Str::lower(Str::random(6)),
        ]);
        $activityLogger->record($request->user(), 'category.updated', $category, "Updated category {$category->name} via API.");

        return CategoryResource::make($category);
    }

    public function destroy(Category $category, ActivityLogger $activityLogger): JsonResponse
    {
        if ($category->products()->exists()) {
            return response()->json(['message' => 'Move products out of this category before deleting it.'], 409);
        }

        $activityLogger->record(request()->user(), 'category.deleted', $category, "Deleted category {$category->name} via API.");
        $category->delete();

        return response()->json(['message' => 'Category deleted.']);
    }

    /**
     * @return array{name: string, description?: ?string, is_active: bool}
     */
    private function validatedCategory(Request $request, ?Category $category = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('categories', 'name')->ignore($category?->id)],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['required', 'boolean'],
        ]);
    }
}
