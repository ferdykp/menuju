<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TaskResource;
use App\Models\Workspace;
use App\Support\DateOnly;
use App\Services\FinanceLedger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function show(Request $request, Workspace $workspace, FinanceLedger $ledger): JsonResponse
    {
        $this->authorize('view', $workspace);

        $totalTasks = $workspace->tasks()->count();
        $completedTasks = $workspace->tasks()->where('status', 'completed')->count();
        $upcomingTasks = $workspace->tasks()
            ->whereNot('status', 'completed')
            ->whereNotNull('due_at')
            ->whereBetween('due_at', [today($workspace->timezone), today($workspace->timezone)->addDays(7)])
            ->with(['assignee:id,name', 'creator:id,name'])
            ->orderBy('due_at')
            ->limit(5)
            ->get();
        $finance = $ledger->overview($workspace);
        $nextPayment = $workspace->paymentObligations()->where('status', '!=', 'paid')->whereNotNull('due_at')->orderBy('due_at')->first();
        $nextEvent = $workspace->events()->whereNotNull('event_date')->whereDate('event_date', '>=', today($workspace->timezone))->orderBy('event_date')->first(['id', 'title', 'event_date', 'venue_name']);
        $guestQuery = $workspace->guests();
        $guests = [
            'total' => (clone $guestQuery)->count(),
            'attending' => (int) (clone $guestQuery)->where('rsvp_status', 'attending')->sum(\Illuminate\Support\Facades\DB::raw('1 + rsvp_plus_ones')),
            'invited' => (clone $guestQuery)->where('invitation_status', 'sent')->count(),
        ];

        return response()->json([
            'workspace' => [
                'id' => $workspace->id,
                'name' => $workspace->name,
                'partner_one_name' => $workspace->partner_one_name,
                'partner_two_name' => $workspace->partner_two_name,
                'wedding_date' => $workspace->wedding_date?->toDateString(),
                'timezone' => $workspace->timezone,
                'days_until_wedding' => $workspace->wedding_date ? now($workspace->timezone)->startOfDay()->diffInDays($workspace->wedding_date, false) : null,
            ],
            'tasks' => [
                'total' => $totalTasks,
                'completed' => $completedTasks,
                'open' => $totalTasks - $completedTasks,
                'completion_percent' => $totalTasks ? (int) round(($completedTasks / $totalTasks) * 100) : 0,
                'due_this_week' => TaskResource::collection($upcomingTasks)->resolve(),
            ],
            'finance' => $finance,
            'guests' => $guests,
            'next_payment' => $nextPayment ? DateOnly::model($nextPayment, ['due_at']) : null,
            'next_event' => $nextEvent ? DateOnly::model($nextEvent, ['event_date']) : null,
        ]);
    }
}
