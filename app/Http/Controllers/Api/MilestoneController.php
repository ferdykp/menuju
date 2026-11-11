<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Milestone;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MilestoneController extends Controller
{
    public function index(Request $request, Workspace $workspace)
    {
        abort_unless($workspace->members()->where('users.id', $request->user()->id)->wherePivot('status', 'active')->exists(), 403);

        $milestones = $workspace->milestones()
            ->withCount('tasks')
            ->withCount(['tasks as completed_tasks_count' => fn ($query) => $query->where('status', 'done')])
            ->with(['tasks:id,milestone_id,title,status,due_at,assignee_id'])
            ->orderByRaw('target_date is null')
            ->orderBy('target_date')
            ->orderBy('sort_order')
            ->get();

        return response()->json(['milestones' => $milestones->map(fn (Milestone $milestone) => $this->serialize($milestone))]);
    }

    public function store(Request $request, Workspace $workspace)
    {
        abort_unless($workspace->members()->where('users.id', $request->user()->id)->wherePivot('status', 'active')->exists(), 403);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:3000'],
            'category' => ['nullable', 'string', 'max:80'],
            'target_date' => ['nullable', 'date'],
            'status' => ['nullable', Rule::in(['upcoming', 'in_progress', 'done'])],
        ]);

        $milestone = $workspace->milestones()->create($data + [
            'created_by' => $request->user()->id,
            'category' => 'Umum',
            'status' => 'upcoming',
        ]);

        return response()->json(['milestone' => $this->serialize($milestone)], 201);
    }

    public function update(Request $request, Workspace $workspace, Milestone $milestone)
    {
        abort_unless($milestone->workspace_id === $workspace->id, 404);
        abort_unless($workspace->members()->where('users.id', $request->user()->id)->wherePivot('status', 'active')->exists(), 403);
        $data = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:160'],
            'description' => ['sometimes', 'nullable', 'string', 'max:3000'],
            'category' => ['sometimes', 'nullable', 'string', 'max:80'],
            'target_date' => ['sometimes', 'nullable', 'date'],
            'status' => ['sometimes', Rule::in(['upcoming', 'in_progress', 'done'])],
        ]);
        $milestone->update($data);

        return response()->json(['milestone' => $this->serialize($milestone->fresh())]);
    }

    public function destroy(Request $request, Workspace $workspace, Milestone $milestone)
    {
        abort_unless($milestone->workspace_id === $workspace->id, 404);
        abort_unless($workspace->members()->where('users.id', $request->user()->id)->wherePivot('status', 'active')->exists(), 403);
        $milestone->delete();

        return response()->noContent();
    }

    private function serialize(Milestone $milestone): array
    {
        $row = $milestone->toArray();
        $row['target_date'] = $milestone->target_date?->format('Y-m-d');
        if ($milestone->relationLoaded('tasks')) {
            $row['tasks'] = $milestone->tasks->map(function ($task): array {
                $taskRow = $task->toArray();
                $taskRow['due_at'] = $task->due_at?->format('Y-m-d');

                return $taskRow;
            })->all();
        }

        return $row;
    }
}
