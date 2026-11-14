<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Workspace;
use App\Models\WorkspaceDecision;
use App\Support\PaginationMeta;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WorkspaceActivityController extends Controller
{
    public function index(Request $request, Workspace $workspace)
    {
        $this->authorize('view', $workspace);
        $activity = $workspace->activities()->with('actor:id,name')->orderByDesc('occurred_at')
            ->paginate(min(max($request->integer('per_page', 30), 1), 100));
        $decisions = $workspace->decisions()->with('event:id,title')->orderByRaw("case status when 'open' then 0 when 'decided' then 1 else 2 end")
            ->orderByDesc('updated_at')->limit(30)->get();
        return response()->json([
            'activities' => $activity->getCollection(),
            'pagination' => PaginationMeta::from($activity),
            'decisions' => $decisions,
            'events' => $workspace->events()->orderBy('event_date')->get(['id', 'title']),
        ]);
    }

    public function store(Request $request, Workspace $workspace)
    {
        $this->authorize('view', $workspace);
        $decision = $workspace->decisions()->create($this->validateDecision($request, $workspace) + ['created_by' => $request->user()->id]);
        return response()->json(['decision' => $decision->load('event:id,title')], 201);
    }

    public function update(Request $request, Workspace $workspace, WorkspaceDecision $decision)
    {
        $this->authorize('view', $workspace);
        abort_unless($decision->workspace_id === $workspace->id, 404);
        $decision->update($this->validateDecision($request, $workspace, true));
        return response()->json(['decision' => $decision->fresh()->load('event:id,title')]);
    }

    public function destroy(Request $request, Workspace $workspace, WorkspaceDecision $decision)
    {
        $this->authorize('view', $workspace);
        abort_unless($decision->workspace_id === $workspace->id, 404);
        $decision->delete();
        return response()->noContent();
    }

    private function validateDecision(Request $request, Workspace $workspace, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';
        return $request->validate([
            'title' => [$required, 'string', 'max:180'],
            'context' => ['sometimes', 'nullable', 'string', 'max:3000'],
            'options' => ['sometimes', 'nullable', 'array', 'max:12'],
            'options.*' => ['string', 'max:240'],
            'event_id' => ['sometimes', 'nullable', 'integer', Rule::exists('events', 'id')->where(fn ($query) => $query->where('workspace_id', $workspace->id)->whereNull('deleted_at'))],
            'status' => ['sometimes', Rule::in(['open', 'decided', 'parked'])],
            'outcome' => ['sometimes', 'nullable', 'string', 'max:3000'],
        ]);
    }
}
