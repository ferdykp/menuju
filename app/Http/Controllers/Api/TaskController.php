<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tasks\StoreTaskRequest;
use App\Http\Requests\Tasks\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request, Workspace $workspace): JsonResponse
    {
        $this->authorize('view', $workspace);

        $query = $workspace->tasks()->with(['assignee:id,name', 'creator:id,name']);

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(fn ($builder) => $builder
                ->where('title', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%"));
        }

        foreach (['status', 'category', 'assignee_id', 'priority'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->query($filter));
            }
        }

        $tasks = $query
            ->orderByRaw('due_at IS NULL')
            ->orderBy('due_at')
            ->orderByRaw("CASE priority WHEN 'high' THEN 0 WHEN 'normal' THEN 1 ELSE 2 END")
            ->paginate(min(max((int) $request->query('per_page', 20), 1), 50))
            ->withQueryString();

        $total = $workspace->tasks()->count();
        $completed = $workspace->tasks()->where('status', 'completed')->count();

        return response()->json([
            'data' => TaskResource::collection($tasks)->resolve(),
            'links' => $tasks->linkCollection(),
            'meta' => [
                'current_page' => $tasks->currentPage(),
                'from' => $tasks->firstItem(),
                'last_page' => $tasks->lastPage(),
                'per_page' => $tasks->perPage(),
                'to' => $tasks->lastItem(),
                'total' => $tasks->total(),
                'prev_page_url' => $tasks->previousPageUrl(),
                'next_page_url' => $tasks->nextPageUrl(),
                'summary' => [
                    'total' => $total,
                    'completed' => $completed,
                    'open' => $total - $completed,
                    'percent' => $total ? (int) round(($completed / $total) * 100) : 0,
                ],
            ],
        ]);
    }

    public function store(StoreTaskRequest $request, Workspace $workspace): JsonResponse
    {
        $data = $request->validated();
        $task = $workspace->tasks()->create([
            ...$data,
            'created_by' => $request->user()->id,
            'status' => $data['status'] ?? 'planned',
        ]);

        return response()->json([
            'task' => (new TaskResource($task->load(['assignee:id,name', 'creator:id,name'])))->resolve(),
        ], 201);
    }

    public function show(Task $task): JsonResponse
    {
        $this->authorize('view', $task);

        return response()->json([
            'task' => (new TaskResource($task->load(['assignee:id,name', 'creator:id,name'])))->resolve(),
        ]);
    }

    public function update(UpdateTaskRequest $request, Task $task): JsonResponse
    {
        $data = $request->validated();
        if (array_key_exists('status', $data)) {
            $data['completed_at'] = $data['status'] === 'completed' ? ($task->completed_at ?? now()) : null;
        }

        $task->update($data);

        return response()->json([
            'task' => (new TaskResource($task->refresh()->load(['assignee:id,name', 'creator:id,name'])))->resolve(),
        ]);
    }

    public function destroy(Task $task): JsonResponse
    {
        $this->authorize('delete', $task);
        $task->delete();

        return response()->json(['message' => 'Tugas berhasil dihapus.']);
    }
}
