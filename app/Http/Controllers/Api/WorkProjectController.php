<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\TaskComment;
use App\Models\User;
use App\Models\WorkProject;
use App\Services\WorkProjectPermissionService;
use App\Services\WorkProjectTrashService;
use Illuminate\Http\Request;

class WorkProjectController extends Controller
{
    public function __construct(
        protected WorkProjectPermissionService $permissions,
        protected WorkProjectTrashService $trash,
    ) {}

    public function index(Request $request)
    {
        $this->trash->purgeExpired();

        $query = WorkProject::with(['members:id,name,email,role', 'creator:id,name'])
            ->withCount('tasks')
            ->latest();

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $paginated = $query->paginate(20);
        $paginated->getCollection()->transform(fn ($p) => $this->formatProject($p));

        return response()->json($paginated);
    }

    public function store(Request $request)
    {
        $validated = $this->validateProject($request);

        $project = WorkProject::create([
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
            'status'      => $validated['status'] ?? 'active',
            'starts_at'   => $validated['starts_at'] ?? null,
            'due_at'      => $validated['due_at'] ?? null,
            'created_by'  => $request->user()->id,
        ]);

        $this->syncMembersFromRequest($project, $validated);

        return response()->json($this->formatProject($project->load(['members:id,name,email,role'])), 201);
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

        return response()->json($this->formatProject($workProject));
    }

    public function update(Request $request, WorkProject $workProject)
    {
        $validated = $this->validateProject($request, true);

        $workProject->update(collect($validated)->only([
            'name', 'description', 'status', 'starts_at', 'due_at',
        ])->filter(fn ($v) => $v !== null)->toArray());

        if (array_key_exists('members', $validated) || array_key_exists('member_ids', $validated)) {
            $this->syncMembersFromRequest($workProject, $validated);
        }

        return response()->json($this->formatProject($workProject->fresh(['members:id,name,email,role'])));
    }

    public function destroy(WorkProject $workProject)
    {
        $workProject->delete();

        return response()->json([
            'message' => 'Work project moved to trash. It can be restored within ' . WorkProject::TRASH_RETENTION_DAYS . ' days.',
        ]);
    }

    public function trashedIndex(Request $request)
    {
        $this->trash->purgeExpired();

        $query = WorkProject::onlyTrashed()
            ->with(['members:id,name,email,role', 'creator:id,name'])
            ->withCount('tasks')
            ->orderByDesc('deleted_at');

        $paginated = $query->paginate(20);
        $paginated->getCollection()->transform(fn ($p) => $this->formatTrashedProject($p));

        return response()->json($paginated);
    }

    public function restore(int $id)
    {
        $project = WorkProject::onlyTrashed()->findOrFail($id);
        $project->restore();

        return response()->json([
            'message' => 'Work project restored.',
            'project' => $this->formatProject($project->fresh(['members:id,name,email,role'])),
        ]);
    }

    public function dashboard(Request $request)
    {
        $tasks = Task::with(['workProject:id,name', 'assignees:id,name'])
            ->whereHas('workProject');

        return response()->json([
            'projects_total'    => WorkProject::count(),
            'tasks_total'       => Task::count(),
            'tasks_review'      => Task::where('status', 'review')->count(),
            'tasks_overdue'     => Task::where('is_overdue', true)->where('status', '!=', 'done')->count(),
            'tasks_in_progress' => Task::where('status', 'in_progress')->count(),
            'recent_tasks'      => $tasks->latest()->limit(10)->get(),
            'staff_summary'     => User::where('role', 'staff')->where('is_active', true)
                ->withCount(['assignedTasks'])
                ->get(['id', 'name', 'email']),
        ]);
    }

    protected function validateProject(Request $request, bool $partial = false): array
    {
        $rules = [
            'name'        => ($partial ? 'sometimes|' : '') . 'required|string|max:255',
            'description' => 'nullable|string',
            'status'      => 'nullable|in:planning,active,on_hold,completed,archived',
            'starts_at'   => 'nullable|date',
            'due_at'      => 'nullable|date',
            'members'     => 'nullable|array',
            'members.*.user_id' => 'required_with:members|exists:users,id',
            'members.*.can_create_tasks' => 'boolean',
            'members.*.can_edit_task_details' => 'boolean',
            'members.*.can_assign_tasks' => 'boolean',
            'members.*.can_delete_tasks' => 'boolean',
            'members.*.can_moderate_content' => 'boolean',
            'member_ids'  => 'nullable|array',
            'member_ids.*'=> 'exists:users,id',
        ];

        return $request->validate($rules);
    }

    protected function syncMembersFromRequest(WorkProject $project, array $validated): void
    {
        if (!empty($validated['members'])) {
            $this->permissions->syncMembers($project, $validated['members']);
        } elseif (array_key_exists('member_ids', $validated)) {
            $this->permissions->syncMemberIds($project, $validated['member_ids'] ?? []);
        }
    }

    protected function formatProject(WorkProject $project): WorkProject
    {
        if ($project->relationLoaded('members')) {
            $project->setAttribute(
                'members',
                $project->members->map(fn ($m) => $this->permissions->formatMember($m))->values()
            );
        }

        return $project;
    }

    protected function formatTrashedProject(WorkProject $project): WorkProject
    {
        $project = $this->formatProject($project);
        $project->setAttribute('deleted_at', $project->deleted_at?->toIso8601String());
        $project->setAttribute('purge_at', $project->purgeAt()?->toIso8601String());
        $project->setAttribute('days_until_purge', $project->daysUntilPurge());

        return $project;
    }
}
