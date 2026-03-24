<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProjectController extends Controller
{
    // Public: for landing page — ?category=slug (project category) or legacy tag name in JSON tags
    public function landingIndex(Request $request)
    {
        $query = Project::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id');

        $filter = $request->query('category');
        if ($filter && $filter !== 'All') {
            $pc = ProjectCategory::query()
                ->where('is_active', true)
                ->where('slug', $filter)
                ->first();
            if ($pc) {
                $query->where('project_category_id', $pc->id);
            } else {
                $query->whereJsonContains('tags', $filter);
            }
        }

        return response()->json($query->get());
    }

    public function showBySlug(string $slug)
    {
        $project = Project::where('slug', $slug)->where('is_active', true)->firstOrFail();
        return response()->json($project);
    }

    public function index()
    {
        return response()->json(
            Project::query()->with('projectCategory')->orderBy('sort_order')->orderBy('id')->paginate(15)
        );
    }

    public function show(Project $project)
    {
        $project->load('projectCategory');
        return response()->json($project);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'          => 'required|string|max:255',
            'title_ar'       => 'nullable|string|max:255',
            'project_category_id' => 'required|integer|exists:project_categories,id',
            'description'    => 'nullable|string',
            'description_ar' => 'nullable|string',
            'image'          => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
            'tags'           => 'nullable|array',
            'tags.*'         => 'string|max:100',
            'is_active'      => 'boolean',
        ]);

        $cat = ProjectCategory::findOrFail($validated['project_category_id']);
        $validated['category'] = $cat->name_en;
        $validated['category_ar'] = $cat->name_ar ?? '';

        $validated['slug'] = Str::slug($validated['title']);
        $base = $validated['slug'];
        $i = 1;
        while (Project::where('slug', $validated['slug'])->exists()) {
            $validated['slug'] = $base.'-'.$i++;
        }

        $validated['image'] = $request->file('image')->store('projects', 'public');
        $project = Project::create($validated);
        $project->load('projectCategory');

        return response()->json($project, 201);
    }

    public function update(Request $request, Project $project)
    {
        $validated = $request->validate([
            'title'          => 'sometimes|string|max:255',
            'title_ar'       => 'nullable|string|max:255',
            'project_category_id' => 'sometimes|nullable|integer|exists:project_categories,id',
            'description'    => 'nullable|string',
            'description_ar' => 'nullable|string',
            'image'          => 'sometimes|image|mimes:jpeg,png,jpg,webp|max:2048',
            'tags'           => 'nullable|array',
            'tags.*'         => 'string|max:100',
            'sort_order'     => 'integer',
            'is_active'      => 'boolean',
        ]);

        if ($request->has('project_category_id')) {
            $cid = $request->input('project_category_id');
            if ($cid === null || $cid === '' || $cid === '0') {
                $validated['project_category_id'] = null;
            } else {
                $cat = ProjectCategory::findOrFail((int) $cid);
                $validated['project_category_id'] = $cat->id;
                $validated['category'] = $cat->name_en;
                $validated['category_ar'] = $cat->name_ar ?? '';
            }
        }

        if ($request->hasFile('image')) {
            if ($project->getRawOriginal('image') && ! str_starts_with($project->getRawOriginal('image'), 'http')) {
                Storage::disk('public')->delete($project->getRawOriginal('image'));
            }
            $validated['image'] = $request->file('image')->store('projects', 'public');
        }

        if (array_key_exists('title', $validated)) {
            $validated['slug'] = Str::slug($validated['title']);
            $base = $validated['slug'];
            $j = 1;
            while (Project::where('slug', $validated['slug'])->where('id', '!=', $project->id)->exists()) {
                $validated['slug'] = $base.'-'.$j++;
            }
        }

        $project->update($validated);
        $project->load('projectCategory');

        return response()->json($project);
    }

    public function destroy(Project $project)
    {
        $path = $project->getRawOriginal('image');
        if ($path && ! str_starts_with($path, 'http')) {
            Storage::disk('public')->delete($path);
        }
        $project->delete();

        return response()->json(['message' => 'Project deleted.']);
    }
}
