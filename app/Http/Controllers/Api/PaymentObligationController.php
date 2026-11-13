<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FinancialAccount;
use App\Models\PaymentObligation;
use App\Models\Workspace;
use App\Support\DateOnly;
use App\Support\PaginationMeta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentObligationController extends Controller
{
    public function index(Request $request, Workspace $workspace)
    {
        $this->authorize('view', $workspace);
        $items = $workspace->paymentObligations()->with(['budgetCategory:id,name','event:id,title'])->withSum('records as paid_amount','amount')->orderByRaw('due_at is null')->orderBy('due_at')
            ->paginate(min(max($request->integer('per_page', 25), 1), 100));
        $obligations = $items->getCollection()->map(fn ($item) => [...DateOnly::model($item, ['due_at']), 'remaining_amount' => max((float) $item->total_amount - (float) $item->paid_amount, 0)]);
        return response()->json(['obligations' => $obligations, 'pagination' => PaginationMeta::from($items)]);
    }

    public function store(Request $request, Workspace $workspace)
    {
        $this->authorize('view', $workspace);
        $data = $request->validate([
            'title' => ['required','string','max:180'], 'vendor_name' => ['nullable','string','max:160'],
            'budget_category_id' => ['nullable','integer','exists:budget_categories,id'], 'event_id' => ['nullable','integer','exists:events,id'],
            'total_amount' => ['required','numeric','gt:0','max:999999999999.99'], 'due_at' => ['nullable','date_format:Y-m-d'], 'notes' => ['nullable','string','max:2000'],
        ]);
        if (! empty($data['budget_category_id'])) abort_unless($workspace->budgetCategories()->whereKey($data['budget_category_id'])->exists(), 422);
        if (! empty($data['event_id'])) abort_unless($workspace->events()->whereKey($data['event_id'])->exists(), 422);
        $item = $workspace->paymentObligations()->create($data);
        return response()->json(['obligation' => DateOnly::model($item, ['due_at'])], 201);
    }

    public function update(Request $request, PaymentObligation $obligation)
    {
        $this->authorize('view', $obligation->workspace);
        $data = $request->validate([
            'title' => ['sometimes','required','string','max:180'], 'vendor_name' => ['sometimes','nullable','string','max:160'],
            'budget_category_id' => ['sometimes','nullable','integer','exists:budget_categories,id'], 'event_id' => ['sometimes','nullable','integer','exists:events,id'],
            'total_amount' => ['sometimes','required','numeric','gt:0','max:999999999999.99'], 'due_at' => ['sometimes','nullable','date_format:Y-m-d'], 'notes' => ['sometimes','nullable','string','max:2000'],
        ]);
        $workspace = $obligation->workspace;
        if (! empty($data['budget_category_id'])) abort_unless($workspace->budgetCategories()->whereKey($data['budget_category_id'])->exists(), 422, 'Kategori harus berasal dari workspace ini.');
        if (! empty($data['event_id'])) abort_unless($workspace->events()->whereKey($data['event_id'])->exists(), 422, 'Acara harus berasal dari workspace ini.');
        $obligation->update($data);
        return response()->json(['obligation' => DateOnly::model($obligation->fresh(), ['due_at'])]);
    }

    public function pay(Request $request, PaymentObligation $obligation)
    {
        $this->authorize('view', $obligation->workspace);
        $data = $request->validate([
            'account_id' => ['required','integer','exists:financial_accounts,id'], 'amount' => ['required','numeric','gt:0','max:999999999999.99'],
            'paid_at' => ['required','date_format:Y-m-d'], 'notes' => ['nullable','string','max:1200'],
        ]);
        $account = FinancialAccount::findOrFail($data['account_id']);
        abort_unless($account->workspace_id === $obligation->workspace_id && ! $account->is_archived, 422, 'Pilih akun aktif dari workspace ini.');
        $paidSoFar = (float) $obligation->records()->sum('amount');
        abort_if((float) $data['amount'] > ((float) $obligation->total_amount - $paidSoFar), 422, 'Jumlah pembayaran melebihi sisa kewajiban.');

        $record = DB::transaction(function () use ($request, $obligation, $account, $data, $paidSoFar) {
            $category = $obligation->budgetCategory?->name ?? 'Pembayaran';
            $transaction = $obligation->workspace->financialTransactions()->create([
                'created_by' => $request->user()->id, 'account_id' => $account->id, 'type' => 'expense', 'category' => $category,
                'description' => 'Pembayaran: '.$obligation->title, 'amount' => $data['amount'], 'occurred_at' => $data['paid_at'], 'notes' => $data['notes'] ?? null,
            ]);
            $record = $obligation->records()->create([
                'financial_transaction_id' => $transaction->id, 'amount' => $data['amount'], 'paid_at' => $data['paid_at'], 'notes' => $data['notes'] ?? null,
            ]);
            if ($paidSoFar + (float) $data['amount'] >= (float) $obligation->total_amount) $obligation->update(['status' => 'paid']);
            return $record->load('transaction.account:id,name');
        });
        $payment = DateOnly::model($record, ['paid_at']);
        if ($record->relationLoaded('transaction') && $record->transaction) {
            $payment['transaction'] = DateOnly::model($record->transaction, ['occurred_at']);
        }

        return response()->json(['payment' => $payment]);
    }

    public function destroy(Request $request, PaymentObligation $obligation)
    {
        $this->authorize('view', $obligation->workspace);
        abort_if($obligation->records()->exists(), 409, 'Kewajiban yang sudah dibayar tidak dapat dihapus.');
        $obligation->delete();
        return response()->noContent();
    }
}
