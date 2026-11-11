<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TaskResource;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function show(Request $request, Workspace $workspace): JsonResponse
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
        ]);
    }
}
