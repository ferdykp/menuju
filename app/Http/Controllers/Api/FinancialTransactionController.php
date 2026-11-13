<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FinancialAccount;
use App\Models\FinancialTransaction;
use App\Models\Workspace;
use App\Support\DateOnly;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class FinancialTransactionController extends Controller
{
    public function index(Request $request, Workspace $workspace)
    {
        $this->authorize('view', $workspace);
        $query = $workspace->financialTransactions()->with(['account:id,name', 'destinationAccount:id,name', 'paymentRecords:id,financial_transaction_id,amount,paid_at'])->whereNull('voided_at');
        foreach (['type', 'category'] as $filter) if ($request->filled($filter)) $query->where($filter, $request->query($filter));
        if ($request->filled('account_id')) $query->where(fn ($builder) => $builder->where('account_id', $request->query('account_id'))->orWhere('destination_account_id', $request->query('account_id')));
        if ($request->filled('month') && preg_match('/^\d{4}-\d{2}$/', $request->query('month'))) {
            $monthStart = $request->query('month').'-01';
            $monthEnd = \Illuminate\Support\Carbon::parse($monthStart)->addMonth()->format('Y-m-d');
            $query->where('occurred_at', '>=', $monthStart)->where('occurred_at', '<', $monthEnd);
        }
        $transactions = $query->orderByDesc('occurred_at')->orderByDesc('id')->paginate(50)->withQueryString();
        $transactions->through(fn (FinancialTransaction $transaction) => $this->serialize($transaction));

        return response()->json(['transactions' => $transactions]);
    }

    public function store(Request $request, Workspace $workspace)
    {
        $this->authorize('view', $workspace);
        $data = $this->validateTransaction($request, $workspace);
        if ($data['type'] === 'transfer' && empty($data['destination_account_id'])) return response()->json(['message' => 'Pilih akun tujuan transfer.'], 422);
        if ($data['type'] === 'transfer' && $data['account_id'] === $data['destination_account_id']) return response()->json(['message' => 'Pilih dua akun yang berbeda.'], 422);
        $transaction = DB::transaction(fn () => $workspace->financialTransactions()->create($data + ['created_by' => $request->user()->id]));

        return response()->json(['transaction' => $this->serialize($transaction->load(['account:id,name', 'destinationAccount:id,name']))], 201);
    }

    public function update(Request $request, FinancialTransaction $transaction)
    {
        $this->authorize('view', $transaction->workspace);
        abort_if($transaction->type === 'transfer' || $transaction->savings_goal_id || $transaction->paymentRecords()->exists(), 409, 'Mutasi terkait harus dikoreksi dari sumber aslinya.');
        $data = $this->validateTransaction($request, $transaction->workspace, true);
        $transaction->update($data);

        return response()->json(['transaction' => $this->serialize($transaction->fresh()->load(['account:id,name', 'destinationAccount:id,name']))]);
    }

    public function void(Request $request, FinancialTransaction $transaction)
    {
        $this->authorize('view', $transaction->workspace);
        abort_if($transaction->savings_goal_id || $transaction->paymentRecords()->exists(), 409, 'Mutasi terkait harus dikoreksi dari sumber aslinya.');
        $transaction->update(['voided_at' => now()]);

        return response()->json(['message' => 'Mutasi dibatalkan; saldo akun dihitung ulang.']);
    }

    private function validateTransaction(Request $request, Workspace $workspace, bool $partial = false): array
    {
        $exists = fn () => Rule::exists('financial_accounts', 'id')->where(fn ($query) => $query->where('workspace_id', $workspace->id)->where('is_archived', false));
        return $request->validate([
            'type' => $partial ? ['sometimes', Rule::in(['income', 'expense'])] : ['required', Rule::in(['income', 'expense', 'transfer'])],
            'account_id' => [$partial ? 'sometimes' : 'required', 'integer', $exists()],
            'destination_account_id' => [$partial ? 'sometimes' : 'nullable', 'nullable', 'integer', $exists()],
            'category' => [$partial ? 'sometimes' : 'nullable', 'nullable', 'string', 'max:100'],
            'description' => [$partial ? 'sometimes' : 'required', 'string', 'max:180'],
            'amount' => [$partial ? 'sometimes' : 'required', 'numeric', 'gt:0', 'max:999999999999.99'],
            'occurred_at' => [$partial ? 'sometimes' : 'required', 'date_format:Y-m-d'],
            'reference' => ['sometimes', 'nullable', 'string', 'max:120'], 'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ]);
    }

    private function serialize(FinancialTransaction $transaction): array
    {
        return DateOnly::model($transaction, ['occurred_at']);
    }
}
