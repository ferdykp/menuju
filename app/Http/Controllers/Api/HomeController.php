<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TaskResource;
use App\Models\Workspace;
use App\Services\FinanceLedger;
use App\Support\DateOnly;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function show(Request $request, Workspace $workspace, FinanceLedger $ledger): JsonResponse
    {
        $this->authorize('view', $workspace);

        $totalTasks = $workspace->tasks()->count();
        $completedTasks = $workspace->tasks()->where('status', 'completed')->count();
        $today = today($workspace->timezone);
        $upcomingTasks = $workspace->tasks()
            ->whereNot('status', 'completed')
            ->whereNotNull('due_at')
            ->whereBetween('due_at', [$today, $today->copy()->addDays(7)])
            ->with(['assignee:id,name', 'creator:id,name'])
            ->orderBy('due_at')
            ->limit(5)
            ->get();
        $finance = $ledger->overview($workspace);
        $nextPayment = $workspace->paymentObligations()->where('status', '!=', 'paid')->whereNotNull('due_at')->orderBy('due_at')->first();
        $overduePayments = $workspace->paymentObligations()->where('status', '!=', 'paid')->whereNotNull('due_at')->whereDate('due_at', '<', $today)->count();
        $upcomingPayments = $workspace->paymentObligations()->where('status', '!=', 'paid')->whereNotNull('due_at')->whereBetween('due_at', [$today, $today->copy()->addDays(7)])->count();
        $overdueTasks = $workspace->tasks()->where('status', '!=', 'completed')->whereNotNull('due_at')->whereDate('due_at', '<', $today)->count();
        $dueTasksCount = $workspace->tasks()->where('status', '!=', 'completed')->whereNotNull('due_at')->whereBetween('due_at', [$today, $today->copy()->addDays(7)])->count();
        $pendingRsvps = $workspace->guests()->where('invitation_status', 'sent')->where('rsvp_status', 'pending')->count();
        $actualByCategory = $workspace->financialTransactions()->where('type', 'expense')->whereNull('voided_at')
            ->selectRaw('category, SUM(amount) as actual_amount')->groupBy('category')->pluck('actual_amount', 'category');
        $overBudgetCategories = $workspace->budgetCategories()->get()->filter(fn ($category) =>
            (float) $category->planned_amount > 0 && (float) $actualByCategory->get($category->name, 0) > (float) $category->planned_amount
        )->count();
        $nextEvent = $workspace->events()->whereNotNull('event_date')->whereDate('event_date', '>=', $today)->orderBy('event_date')->first(['id', 'title', 'event_date', 'venue_name']);
        $guestQuery = $workspace->guests();
        $guests = [
            'total' => (clone $guestQuery)->count(),
            'attending' => (int) (clone $guestQuery)->where('rsvp_status', 'attending')->sum(\Illuminate\Support\Facades\DB::raw('1 + rsvp_plus_ones')),
            'invited' => (clone $guestQuery)->where('invitation_status', 'sent')->count(),
        ];

        $priorities = collect([
            $overduePayments ? ['key' => 'overdue_payments', 'level' => 'urgent', 'title' => "{$overduePayments} pembayaran terlambat", 'detail' => 'Periksa kewajiban vendor dan catat tindak lanjutnya.', 'href' => '/app/budgets?tab=payments'] : null,
            $overdueTasks ? ['key' => 'overdue_tasks', 'level' => 'urgent', 'title' => "{$overdueTasks} tugas melewati tenggat", 'detail' => 'Tinjau tenggat atau perbarui penanggung jawab.', 'href' => '/app/tasks'] : null,
            $overBudgetCategories ? ['key' => 'over_budget', 'level' => 'urgent', 'title' => "{$overBudgetCategories} kategori melewati anggaran", 'detail' => 'Tinjau rencana dan realisasi biaya.', 'href' => '/app/budgets'] : null,
            $pendingRsvps ? ['key' => 'pending_rsvps', 'level' => 'normal', 'title' => "{$pendingRsvps} tamu belum merespons", 'detail' => 'Tinjau status undangan dan RSVP.', 'href' => '/app/guests'] : null,
            $dueTasksCount ? ['key' => 'upcoming_tasks', 'level' => 'normal', 'title' => "{$dueTasksCount} tugas dalam 7 hari", 'detail' => 'Lihat daftar langkah terdekat.', 'href' => '/app/tasks'] : null,
            $upcomingPayments ? ['key' => 'upcoming_payments', 'level' => 'normal', 'title' => "{$upcomingPayments} pembayaran jatuh tempo dalam 7 hari", 'detail' => 'Siapkan pembayaran atau perbarui jadwalnya.', 'href' => '/app/budgets?tab=payments'] : null,
        ])->filter()->values();
        $snoozedKeys = $workspace->prioritySnoozes()->where('user_id', $request->user()->id)->whereDate('snoozed_until', '>=', $today)->pluck('priority_key');
        $priorities = $priorities->reject(fn ($priority) => $snoozedKeys->contains($priority['key']))->values();

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
            'next_payment' => $nextPayment,
            'next_event' => $nextEvent ? DateOnly::model($nextEvent, ['event_date']) : null,
            'priorities' => $priorities,
        ]);
    }

    public function snoozePriority(Request $request, Workspace $workspace)
    {
        $this->authorize('view', $workspace);
        $data = $request->validate(['key' => ['required', 'string', 'in:overdue_payments,overdue_tasks,over_budget,pending_rsvps,upcoming_tasks,upcoming_payments']]);
        $workspace->prioritySnoozes()->updateOrCreate(
            ['user_id' => $request->user()->id, 'priority_key' => $data['key']],
            ['snoozed_until' => today($workspace->timezone)->addDays(7)],
        );
        return response()->noContent();
    }
}
