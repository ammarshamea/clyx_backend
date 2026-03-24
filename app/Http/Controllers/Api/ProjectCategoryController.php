<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProjectCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProjectCategoryController extends Controller
{
    public function publicIndex()
    {
        $rows = ProjectCategory::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'slug', 'name_en', 'name_ar', 'sort_order']);

        return response()->json($rows);
    }

    public function index()
    {
        return response()->json(
            ProjectCategory::orderBy('sort_order')->orderBy('id')->paginate(30)
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name_en'    => 'required|string|max:255',
            'name_ar'    => 'nullable|string|max:255',
            'slug'       => 'nullable|string|max:255|regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/|unique:project_categories,slug',
            'sort_order' => 'nullable|integer|min:0',
            'is_active'  => 'boolean',
        ]);

        $slug = $validated['slug'] ?? Str::slug($validated['name_en']);
        $base = $slug;
        $i = 1;
        while (ProjectCategory::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        $cat = ProjectCategory::create([
            'slug'       => $slug,
            'name_en'    => $validated['name_en'],
            'name_ar'    => $validated['name_ar'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active'  => $validated['is_active'] ?? true,
        ]);

        return response()->json($cat, 201);
    }

    public function show(ProjectCategory $projectCategory)
    {
        return response()->json($projectCategory);
    }

    public function update(Request $request, ProjectCategory $projectCategory)
    {
        $validated = $request->validate([
            'name_en'    => 'sometimes|string|max:255',
            'name_ar'    => 'nullable|string|max:255',
            'slug'       => [
                'nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('project_categories', 'slug')->ignore($projectCategory->id),
            ],
            'sort_order' => 'nullable|integer|min:0',
            'is_active'  => 'boolean',
        ]);

        if (isset($validated['slug'])) {
            $slug = $validated['slug'];
            $base = $slug;
            $j = 1;
            while (ProjectCategory::where('slug', $slug)->where('id', '!=', $projectCategory->id)->exists()) {
                $slug = $base.'-'.$j++;
            }
            $validated['slug'] = $slug;
        }

        $projectCategory->update($validated);

        return response()->json($projectCategory->fresh());
    }

    public function destroy(ProjectCategory $projectCategory)
    {
        if ($projectCategory->projects()->exists()) {
            return response()->json([
                'message' => 'Cannot delete category while projects are linked to it.',
                'errors'  => ['category' => ['Remove or reassign projects first.']],
            ], 422);
        }

        $projectCategory->delete();

        return response()->json(['message' => 'Category deleted.']);
    }
}
