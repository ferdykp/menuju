<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BudgetCategory;
use App\Models\Workspace;
use App\Models\FinancialTransaction;
use App\Support\DateOnly;
use Illuminate\Http\Request;

class BudgetController extends Controller
{
    public function show(Request $request, BudgetCategory $category)
    {
        $this->authorize('view', $category->workspace);
        $workspace = $category->workspace;
        $actual = $workspace->financialTransactions()->where('type', 'expense')->whereNull('voided_at')->where('category', $category->name)->sum('amount');
        $obligations = $category->obligations()->orderBy('due_at')->get()->map(fn ($item) => DateOnly::model($item, ['due_at']));
        $transactions = $workspace->financialTransactions()->with('account:id,name')->where('type', 'expense')->whereNull('voided_at')->where('category', $category->name)->orderByDesc('occurred_at')->limit(20)->get()->map(fn (FinancialTransaction $transaction) => DateOnly::model($transaction, ['occurred_at']));
        $needs = $category->needs()->orderByRaw('needed_by is null')->orderBy('needed_by')->orderBy('title')->get()->map(fn ($need) => DateOnly::model($need, ['needed_by', 'purchased_at']));

        return response()->json([
            'category' => [...$category->load('event:id,title')->toArray(), 'actual_amount' => (float) $actual, 'remaining_amount' => round((float) $category->planned_amount - (float) $actual, 2)],
            'obligations' => $obligations,
            'transactions' => $transactions,
            'needs' => $needs,
        ]);
    }

    public function index(Request $request, Workspace $workspace)
    {
        $this->authorize('view', $workspace);
        $categories = $workspace->budgetCategories()->with('event:id,title')->orderBy('name')->get()->map(function (BudgetCategory $category) use ($workspace) {
            $actual = $workspace->financialTransactions()->where('type', 'expense')->whereNull('voided_at')->where('category', $category->name)->sum('amount');
            return [...$category->toArray(), 'actual_amount' => (float) $actual, 'remaining_amount' => round((float) $category->planned_amount - (float) $actual, 2)];
        });
        return response()->json(['categories' => $categories, 'summary' => ['planned' => $categories->sum('planned_amount'), 'actual' => $categories->sum('actual_amount'), 'remaining' => $categories->sum('remaining_amount')]]);
    }

    public function store(Request $request, Workspace $workspace)
    {
        $this->authorize('view', $workspace);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'], 'planned_amount' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'event_id' => ['nullable', 'integer', 'exists:events,id'], 'color' => ['nullable', 'string', 'max:12'], 'notes' => ['nullable', 'string', 'max:1500'],
        ]);
        if (! empty($data['event_id'])) abort_unless($workspace->events()->whereKey($data['event_id'])->exists(), 422, 'Pilih acara dari workspace ini.');
        $category = $workspace->budgetCategories()->create($data);
        return response()->json(['category' => $category], 201);
    }

    public function update(Request $request, BudgetCategory $category)
    {
        $this->authorize('view', $category->workspace);
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:100'], 'planned_amount' => ['sometimes', 'required', 'numeric', 'min:0', 'max:999999999999.99'],
            'event_id' => ['sometimes', 'nullable', 'integer', 'exists:events,id'], 'color' => ['sometimes', 'nullable', 'string', 'max:12'], 'notes' => ['sometimes', 'nullable', 'string', 'max:1500'],
        ]);
        if (! empty($data['event_id'])) abort_unless($category->workspace->events()->whereKey($data['event_id'])->exists(), 422, 'Pilih acara dari workspace ini.');
        $category->update($data);
        return response()->json(['category' => $category->fresh()]);
    }

    public function destroy(Request $request, BudgetCategory $category)
    {
        $this->authorize('view', $category->workspace);
        $category->delete();
        return response()->noContent();
    }
}
