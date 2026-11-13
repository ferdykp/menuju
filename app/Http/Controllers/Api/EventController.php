<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventAgendaItem;
use App\Models\Need;
use App\Models\Task;
use App\Models\Workspace;
use App\Support\PaginationMeta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EventController extends Controller
{
    public function index(Request $request, Workspace $workspace)
    {
        $this->authorize('view', $workspace);
        $eventsQuery = $workspace->events()->orderByRaw('event_date is null')->orderBy('event_date')->orderBy('start_time');
        if ($request->boolean('for_select')) {
            return response()->json(['events' => $eventsQuery->get(['id', 'title', 'event_date'])]);
        }

        $events = $eventsQuery
            ->withCount(['tasks', 'tasks as open_tasks_count' => fn ($tasks) => $tasks->where('status', '!=', 'completed')])
            ->withCount(['agendaItems', 'needs', 'needs as open_needs_count' => fn ($needs) => $needs->where('status', '!=', 'purchased')])
            ->withSum(['needs as estimated_needs_total' => fn ($needs) => $needs->where('status', '!=', 'purchased')], 'estimated_amount')
            ->withSum(['needs as actual_needs_total' => fn ($needs) => $needs->where('status', 'purchased')], 'purchased_amount')
            ->paginate(min(max($request->integer('per_page', 20), 1), 100));
        $nextEvent = $workspace->events()
            ->whereNotNull('event_date')
            ->whereDate('event_date', '>=', now($workspace->timezone)->toDateString())
            ->orderBy('event_date')->orderBy('start_time')->first();

        return response()->json([
            'events' => $events->getCollection()->map(fn (Event $event) => $this->serialize($event)),
            'pagination' => PaginationMeta::from($events),
            'next_event' => $nextEvent ? [
                'id' => $nextEvent->id,
                'title' => $nextEvent->title,
                'event_date' => $nextEvent->event_date?->format('Y-m-d'),
                'start_time' => $nextEvent->start_time,
                'venue_name' => $nextEvent->venue_name,
            ] : null,
        ]);
    }

    public function store(Request $request, Workspace $workspace)
    {
        $this->authorize('view', $workspace);
        $data = $this->validateEvent($request);
        $event = $workspace->events()->create($data + ['created_by' => $request->user()->id, 'timezone' => $workspace->timezone]);

        return response()->json(['event' => $this->serialize($event)], 201);
    }

    public function show(Request $request, Workspace $workspace, Event $event)
    {
        $this->authorize('view', $workspace);
        abort_unless($event->workspace_id === $workspace->id, 404);

        $event->load('agendaItems', 'needs.needCategory')->loadCount(['tasks', 'tasks as open_tasks_count' => fn ($tasks) => $tasks->where('status', '!=', 'completed')]);

        return response()->json(['event' => $this->serialize($event)]);
    }

    public function update(Request $request, Workspace $workspace, Event $event)
    {
        $this->authorize('view', $workspace);
        abort_unless($event->workspace_id === $workspace->id, 404);
        $event->update($this->validateEvent($request, true));

        return response()->json(['event' => $this->serialize($event->fresh())]);
    }

    public function destroy(Request $request, Workspace $workspace, Event $event)
    {
        $this->authorize('view', $workspace);
        abort_unless($event->workspace_id === $workspace->id, 404);
        DB::transaction(function () use ($event): void {
            Need::withTrashed()->where('event_id', $event->id)->update(['event_id' => null]);
            Task::withTrashed()->where('event_id', $event->id)->update(['event_id' => null]);
            $event->delete();
        });

        return response()->noContent();
    }

    public function storeAgenda(Request $request, Event $event)
    {
        $this->authorize('view', $event->workspace);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'], 'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'], 'location' => ['nullable', 'string', 'max:160'],
            'notes' => ['nullable', 'string', 'max:1500'],
        ]);
        $data['sort_order'] = $event->agendaItems()->count();

        return response()->json(['agenda_item' => $event->agendaItems()->create($data)], 201);
    }

    public function updateAgenda(Request $request, Event $event, EventAgendaItem $agendaItem)
    {
        $this->authorize('view', $event->workspace);
        abort_unless($agendaItem->event_id === $event->id, 404);
        $agendaItem->update($request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:160'], 'start_time' => ['sometimes', 'nullable', 'date_format:H:i'],
            'end_time' => ['sometimes', 'nullable', 'date_format:H:i'], 'location' => ['sometimes', 'nullable', 'string', 'max:160'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:1500'],
        ]));

        return response()->json(['agenda_item' => $agendaItem->fresh()]);
    }

    public function destroyAgenda(Request $request, Event $event, EventAgendaItem $agendaItem)
    {
        $this->authorize('view', $event->workspace);
        abort_unless($agendaItem->event_id === $event->id, 404);
        $agendaItem->delete();

        return response()->noContent();
    }

    private function validateEvent(Request $request, bool $partial = false): array
    {
        $base = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'title' => [$base, 'string', 'max:160'],
            'event_type' => ['sometimes', Rule::in(['engagement', 'ceremony', 'reception', 'family', 'other'])],
            'event_date' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'start_time' => ['sometimes', 'nullable', 'date_format:H:i'], 'end_time' => ['sometimes', 'nullable', 'date_format:H:i'],
            'venue_name' => ['sometimes', 'nullable', 'string', 'max:160'], 'address' => ['sometimes', 'nullable', 'string', 'max:500'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:3000'],
        ]);
    }

    private function serialize(Event $event): array
    {
        $row = $event->toArray();
        $row['event_date'] = $event->event_date?->format('Y-m-d');
        if ($event->relationLoaded('agendaItems')) $row['agenda_items'] = $event->agendaItems;
        if ($event->relationLoaded('needs')) {
            $needs = $event->needs;
            $openNeeds = $needs->where('status', '!=', 'purchased');
            $purchasedNeeds = $needs->where('status', 'purchased');
            $row['needs'] = $needs->map(fn ($need) => [
                'id' => $need->id,
                'title' => $need->title,
                'status' => $need->status,
                'quantity' => $need->quantity,
                'estimated_amount' => $need->estimated_amount,
                'purchased_amount' => $need->purchased_amount,
                'need_category' => $need->needCategory,
            ]);
            $row['needs_summary'] = [
                'total' => $needs->count(),
                'open' => $openNeeds->count(),
                'purchased' => $purchasedNeeds->count(),
                'estimated' => (float) $openNeeds->sum('estimated_amount'),
                'actual' => (float) $purchasedNeeds->sum('purchased_amount'),
            ];
        } else {
            $row['needs_summary'] = [
                'total' => $event->needs_count ?? 0,
                'open' => $event->open_needs_count ?? 0,
                'purchased' => max(($event->needs_count ?? 0) - ($event->open_needs_count ?? 0), 0),
                'estimated' => (float) ($event->estimated_needs_total ?? 0),
                'actual' => (float) ($event->actual_needs_total ?? 0),
            ];
        }

        $row['tasks_summary'] = [
            'total' => (int) ($event->tasks_count ?? $event->tasks()->count()),
            'open' => (int) ($event->open_tasks_count ?? $event->tasks()->where('status', '!=', 'completed')->count()),
            'completed' => max((int) ($event->tasks_count ?? 0) - (int) ($event->open_tasks_count ?? 0), 0),
        ];

        return $row;
    }
}
