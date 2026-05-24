<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\TaskComment;
use App\Models\User;
use App\Models\WorkProject;
use Illuminate\Http\Request;

class WorkProjectController extends Controller
{
    public function index(Request $request)
    {
        $query = WorkProject::with(['members:id,name,email', 'creator:id,name'])
            ->withCount('tasks')
            ->latest();

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        return response()->json($query->paginate(20));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'status'      => 'nullable|in:planning,active,on_hold,completed,archived',
            'starts_at'   => 'nullable|date',
            'due_at'      => 'nullable|date',
            'member_ids'  => 'nullable|array',
            'member_ids.*'=> 'exists:users,id',
        ]);

        $project = WorkProject::create([
            ...$validated,
            'created_by' => $request->user()->id,
            'status'       => $validated['status'] ?? 'active',
        ]);

        if (!empty($validated['member_ids'])) {
            $project->members()->sync($validated['member_ids']);
        }

        return response()->json($project->load(['members:id,name,email']), 201);
    }

    public function show(WorkProject $workProject)
    {
        $workProject->load([
            'members:id,name,email,role',
            'creator:id,name',
            'tasks.assignees:id,name,email',
        ]);

        $taskIds = $workProject->tasks->pluck('id');

        $workProject->setAttribute(
            'recent_activity',
            TaskComment::whereIn('task_id', $taskIds)
                ->with(['user:id,name', 'task:id,title'])
                ->latest()
                ->limit(30)
                ->get()
        );

        $workProject->setAttribute(
            'recent_files',
            TaskAttachment::whereIn('task_id', $taskIds)
                ->with(['user:id,name', 'task:id,title'])
                ->latest()
                ->limit(30)
                ->get()
        );

        return response()->json($workProject);
    }

    public function update(Request $request, WorkProject $workProject)
    {
        $validated = $request->validate([
            'name'        => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'status'      => 'sometimes|in:planning,active,on_hold,completed,archived',
            'starts_at'   => 'nullable|date',
            'due_at'      => 'nullable|date',
            'member_ids'  => 'nullable|array',
            'member_ids.*'=> 'exists:users,id',
        ]);

        $workProject->update($validated);

        if (array_key_exists('member_ids', $validated)) {
            $workProject->members()->sync($validated['member_ids'] ?? []);
        }

        return response()->json($workProject->fresh(['members:id,name,email']));
    }

    public function destroy(WorkProject $workProject)
    {
        $workProject->delete();

        return response()->json(['message' => 'Work project deleted.']);
    }

    public function dashboard(Request $request)
    {
        $tasks = Task::with(['workProject:id,name', 'assignees:id,name']);

        return response()->json([
            'projects_total'   => WorkProject::count(),
            'tasks_total'      => Task::count(),
            'tasks_review'     => Task::where('status', 'review')->count(),
            'tasks_overdue'    => Task::where('is_overdue', true)->where('status', '!=', 'done')->count(),
            'tasks_in_progress'=> Task::where('status', 'in_progress')->count(),
            'recent_tasks'     => $tasks->latest()->limit(10)->get(),
            'staff_summary'    => User::where('role', 'staff')->where('is_active', true)
                ->withCount(['assignedTasks'])
                ->get(['id', 'name', 'email']),
        ]);
    }
}
