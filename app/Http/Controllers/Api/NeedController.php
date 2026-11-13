<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Need;
use App\Models\Workspace;
use App\Support\DateOnly;
use App\Support\PaginationMeta;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NeedController extends Controller
{
    public function show(Request $request, Need $need)
    {
        $this->authorize('view', $need->workspace);

        $need->load('budgetCategory:id,name', 'needCategory:id,name', 'event:id,title,event_date');

        return response()->json(['need' => DateOnly::model($need, ['needed_by', 'purchased_at'])]);
    }

    public function index(Request $request, Workspace $workspace)
    {
        $this->authorize('view', $workspace);
        $query = $workspace->needs()->with('budgetCategory:id,name', 'needCategory:id,name', 'event:id,title,event_date');
        if ($search = trim((string) $request->query('q', ''))) $query->where(fn ($q) => $q->where('title', 'like', "%{$search}%")->orWhere('vendor_name', 'like', "%{$search}%"));
        foreach (['status', 'category', 'priority'] as $field) if ($request->filled($field)) $query->where($field, $request->query($field));
        if ($request->filled('need_category_id')) $query->where('need_category_id', $request->integer('need_category_id'));
        if ($request->filled('event_id')) $query->where('event_id', $request->integer('event_id'));
        if ($request->filled('budget_category_id')) $query->where('budget_category_id', $request->integer('budget_category_id'));
        $needs = $query->orderByRaw('needed_by is null')->orderBy('needed_by')->orderBy('title')
            ->paginate(min(max($request->integer('per_page', 25), 1), 100));
        $all = $workspace->needs();
        $categorySummaries = $workspace->needCategories()
            ->withCount([
                'needs as item_count',
                'needs as open_count' => fn ($needs) => $needs->where('status', '!=', 'purchased'),
                'needs as purchased_count' => fn ($needs) => $needs->where('status', 'purchased'),
            ])
            ->withSum(['needs as estimated_total' => fn ($needs) => $needs->where('status', '!=', 'purchased')], 'estimated_amount')
            ->withSum(['needs as actual_total' => fn ($needs) => $needs->where('status', 'purchased')], 'purchased_amount')
            ->orderBy('name')
            ->get(['id', 'workspace_id', 'name']);

        return response()->json([
            'needs' => $needs->getCollection()->map(fn (Need $need) => DateOnly::model($need, ['needed_by', 'purchased_at'])),
            'pagination' => PaginationMeta::from($needs),
            'budget_categories' => $workspace->budgetCategories()->orderBy('name')->get(['id', 'name']),
            'need_categories' => $categorySummaries,
            'events' => $workspace->events()->orderByRaw('event_date is null')->orderBy('event_date')->orderBy('title')->get(['id', 'title', 'event_date']),
            'summary' => [
                'total' => (clone $all)->count(),
                'open' => (clone $all)->where('status', '!=', 'purchased')->count(),
                'purchased' => (clone $all)->where('status', 'purchased')->count(),
                'estimated' => (float) (clone $all)->where('status', '!=', 'purchased')->sum('estimated_amount'),
                'actual' => (float) (clone $all)->where('status', 'purchased')->sum('purchased_amount'),
            ],
        ]);
    }

    public function store(Request $request, Workspace $workspace)
    {
        $this->authorize('view', $workspace);
        $data = $this->validateNeed($request, $workspace);
        $data = $this->syncBudgetCategoryLabel($data, $workspace);
        $data = $this->syncNeedCategory($data, $workspace);
        unset($data['status']);
        $need = $workspace->needs()->create($data + ['created_by' => $request->user()->id, 'status' => 'planned', 'quantity' => 1, 'priority' => 'normal']);

        return response()->json(['need' => DateOnly::model($need, ['needed_by', 'purchased_at'])], 201);
    }

    public function update(Request $request, Need $need)
    {
        $this->authorize('view', $need->workspace);
        $workspace = $need->workspace;
        $data = $this->validateNeed($request, $workspace, true);
        $data = $this->syncBudgetCategoryLabel($data, $workspace);
        $need->update($this->syncNeedCategory($data, $workspace));

        $updatedNeed = $need->fresh()->load('budgetCategory:id,name', 'needCategory:id,name');

        return response()->json(['need' => DateOnly::model($updatedNeed, ['needed_by', 'purchased_at'])]);
    }

    public function purchase(Request $request, Need $need)
    {
        $this->authorize('view', $need->workspace);
        $data = $request->validate(['purchased_amount' => ['required', 'numeric', 'min:0', 'max:999999999999.99'], 'purchased_at' => ['nullable', 'date_format:Y-m-d']]);
        $need->update($data + ['purchased_at' => today()->toDateString(), 'status' => 'purchased']);

        return response()->json(['need' => DateOnly::model($need->fresh(), ['needed_by', 'purchased_at'])]);
    }

    public function destroy(Request $request, Need $need)
    {
        $this->authorize('view', $need->workspace);
        $need->delete();

        return response()->noContent();
    }

    private function validateNeed(Request $request, Workspace $workspace, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'title' => [$required, 'string', 'max:180'], 'category' => ['sometimes', 'string', 'max:80'],
            'need_category_id' => ['sometimes', 'nullable', 'integer', Rule::exists('need_categories', 'id')->where(fn ($query) => $query->where('workspace_id', $workspace->id))],
            'event_id' => ['sometimes', 'nullable', 'integer', Rule::exists('events', 'id')->where(fn ($query) => $query->where('workspace_id', $workspace->id)->whereNull('deleted_at'))],
            'vendor_name' => ['sometimes', 'nullable', 'string', 'max:160'], 'budget_category' => ['sometimes', 'nullable', 'string', 'max:80'],
            'budget_category_id' => ['sometimes', 'nullable', 'integer', Rule::exists('budget_categories', 'id')->where(fn ($query) => $query->where('workspace_id', $workspace->id))],
            'quantity' => ['sometimes', 'integer', 'min:1', 'max:9999'], 'estimated_amount' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'needed_by' => ['sometimes', 'nullable', 'date_format:Y-m-d'], 'priority' => ['sometimes', Rule::in(['low', 'normal', 'high'])],
            'status' => $partial ? ['sometimes', Rule::in(['planned', 'shortlisted'])] : ['sometimes', Rule::in(['planned', 'shortlisted', 'purchased'])],
            'notes' => ['sometimes', 'nullable', 'string', 'max:3000'],
        ]);
    }

    private function syncBudgetCategoryLabel(array $data, Workspace $workspace): array
    {
        if (array_key_exists('budget_category_id', $data)) {
            $data['budget_category'] = $data['budget_category_id']
                ? $workspace->budgetCategories()->whereKey($data['budget_category_id'])->value('name')
                : null;
        }

        return $data;
    }

    private function syncNeedCategory(array $data, Workspace $workspace): array
    {
        if (! array_key_exists('need_category_id', $data) && ! array_key_exists('category', $data)) {
            return $data;
        }

        $category = array_key_exists('need_category_id', $data) && $data['need_category_id']
            ? $workspace->needCategories()->findOrFail($data['need_category_id'])
            : $workspace->needCategories()->firstOrCreate(['name' => trim((string) ($data['category'] ?? 'Lainnya')) ?: 'Lainnya']);

        $data['need_category_id'] = $category->id;
        $data['category'] = $category->name;

        return $data;
    }
}
