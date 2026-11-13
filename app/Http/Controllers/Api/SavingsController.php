<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FinancialAccount;
use App\Models\SavingsGoal;
use App\Models\SavingsDeposit;
use App\Models\Workspace;
use App\Support\DateOnly;
use App\Support\PaginationMeta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SavingsController extends Controller
{
    public function index(Request $request, Workspace $workspace)
    {
        $this->authorize('view', $workspace);
        $goals = $workspace->savingsGoals()->withSum(['deposits as saved_amount' => fn ($query) => $query->whereNull('reversed_at')], 'amount')->orderBy('is_archived')->orderBy('target_date')->get();
        return response()->json(['goals' => $goals->map(fn (SavingsGoal $goal) => $this->serializeGoal($goal))]);
    }

    public function store(Request $request, Workspace $workspace)
    {
        $this->authorize('view', $workspace);
        $goal = $workspace->savingsGoals()->create($request->validate([
            'name' => ['required', 'string', 'max:140'], 'target_amount' => ['required', 'numeric', 'gt:0', 'max:999999999999.99'],
            'target_date' => ['nullable', 'date_format:Y-m-d'], 'notes' => ['nullable', 'string', 'max:2000'],
        ]) + ['created_by' => $request->user()->id]);
        return response()->json(['goal' => $this->serializeGoal($goal->loadSum('deposits as saved_amount', 'amount'))], 201);
    }

    public function show(Request $request, SavingsGoal $goal)
    {
        $this->authorize('view', $goal->workspace);
        $goal->loadSum(['deposits as saved_amount' => fn ($query) => $query->whereNull('reversed_at')], 'amount');
        $deposits = $goal->deposits()->whereNull('reversed_at')->with('transaction.account:id,name')
            ->orderByDesc('deposited_at')->orderByDesc('id')
            ->paginate(min(max($request->integer('per_page', 25), 1), 100));
        $data = $this->serializeGoal($goal);
        $data['saved_amount'] = (float) ($goal->saved_amount ?? 0);
        $data['remaining_amount'] = max((float) $goal->target_amount - (float) $data['saved_amount'], 0);
        $data['deposits'] = $deposits->getCollection()->map(fn (SavingsDeposit $deposit) => $this->serializeDeposit($deposit));
        return response()->json(['goal' => $data, 'deposit_pagination' => PaginationMeta::from($deposits)]);
    }

    public function update(Request $request, SavingsGoal $goal)
    {
        $this->authorize('view', $goal->workspace);
        $goal->update($request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:140'], 'target_amount' => ['sometimes', 'required', 'numeric', 'gt:0', 'max:999999999999.99'],
            'target_date' => ['sometimes', 'nullable', 'date_format:Y-m-d'], 'notes' => ['sometimes', 'nullable', 'string', 'max:2000'], 'is_archived' => ['sometimes', 'boolean'],
        ]));
        return response()->json(['goal' => $this->serializeGoal($goal->fresh()->loadSum('deposits as saved_amount', 'amount'))]);
    }

    public function deposit(Request $request, SavingsGoal $goal)
    {
        $this->authorize('view', $goal->workspace);
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0', 'max:999999999999.99'], 'deposited_at' => ['required', 'date_format:Y-m-d'],
            'account_id' => ['nullable', 'integer', 'exists:financial_accounts,id'], 'notes' => ['nullable', 'string', 'max:1500'],
        ]);
        if (! empty($data['account_id'])) {
            $account = FinancialAccount::findOrFail($data['account_id']);
            abort_unless($account->workspace_id === $goal->workspace_id && ! $account->is_archived, 422, 'Pilih akun aktif dari workspace ini.');
        }
        $deposit = DB::transaction(function () use ($goal, $request, $data) {
            $transaction = null;
            if (! empty($data['account_id'])) {
                $transaction = $goal->workspace->financialTransactions()->create([
                    'created_by' => $request->user()->id, 'account_id' => $data['account_id'], 'type' => 'savings_contribution',
                    'description' => 'Setoran tabungan: '.$goal->name, 'category' => 'Tabungan', 'amount' => $data['amount'], 'occurred_at' => $data['deposited_at'],
                ]);
            }
            return $goal->deposits()->create([...$data, 'financial_transaction_id' => $transaction?->id]);
        });
        return response()->json(['deposit' => $this->serializeDeposit($deposit->load('transaction.account:id,name'))], 201);
    }

    public function updateDeposit(Request $request, SavingsGoal $goal, SavingsDeposit $deposit)
    {
        $this->authorize('view', $goal->workspace);
        abort_unless($deposit->savings_goal_id === $goal->id && ! $deposit->reversed_at, 404);
        $data = $request->validate(['amount' => ['sometimes', 'required', 'numeric', 'gt:0', 'max:999999999999.99'], 'deposited_at' => ['sometimes', 'required', 'date_format:Y-m-d'], 'account_id' => ['sometimes', 'nullable', 'integer', 'exists:financial_accounts,id'], 'notes' => ['sometimes', 'nullable', 'string', 'max:1500']]);
        $account = ! empty($data['account_id']) ? FinancialAccount::findOrFail($data['account_id']) : null;
        if ($account) abort_unless($account->workspace_id === $goal->workspace_id && ! $account->is_archived, 422, 'Pilih akun aktif dari workspace ini.');
        DB::transaction(function () use ($deposit, $data, $request, $goal, $account) {
            $deposit->update(collect($data)->except('account_id')->all());
            if ($deposit->financial_transaction_id && ! $account) {
                $deposit->transaction()->update(['voided_at' => now()]);
                $deposit->update(['financial_transaction_id' => null]);
            } elseif ($deposit->financial_transaction_id) {
                $transactionChanges = [];
                if (isset($data['amount'])) $transactionChanges['amount'] = $data['amount'];
                if (isset($data['deposited_at'])) $transactionChanges['occurred_at'] = $data['deposited_at'];
                $transactionChanges['account_id'] = $account->id;
                if ($transactionChanges) $deposit->transaction()->update($transactionChanges);
            } elseif ($account) {
                $transaction = $goal->workspace->financialTransactions()->create([
                    'created_by' => $request->user()->id, 'account_id' => $account->id, 'type' => 'savings_contribution',
                    'description' => 'Setoran tabungan: '.$goal->name, 'category' => 'Tabungan', 'amount' => $deposit->amount,
                    'occurred_at' => $deposit->deposited_at, 'notes' => $deposit->notes,
                ]);
                $deposit->update(['financial_transaction_id' => $transaction->id]);
            }
        });
        return response()->json(['deposit' => $this->serializeDeposit($deposit->fresh()->load('transaction.account:id,name'))]);
    }

    public function reverseDeposit(Request $request, SavingsGoal $goal, SavingsDeposit $deposit)
    {
        $this->authorize('view', $goal->workspace);
        abort_unless($deposit->savings_goal_id === $goal->id && ! $deposit->reversed_at, 404);
        DB::transaction(function () use ($deposit) {
            $deposit->update(['reversed_at' => now()]);
            if ($deposit->financial_transaction_id) $deposit->transaction()->update(['voided_at' => now()]);
        });
        return response()->noContent();
    }

    private function serializeGoal(SavingsGoal $goal): array
    {
        $data = DateOnly::model($goal, ['target_date']);
        if ($goal->relationLoaded('deposits')) {
            $data['deposits'] = $goal->deposits->map(fn (SavingsDeposit $deposit) => $this->serializeDeposit($deposit));
        }

        return $data;
    }

    private function serializeDeposit(SavingsDeposit $deposit): array
    {
        $data = DateOnly::model($deposit, ['deposited_at']);
        if ($deposit->relationLoaded('transaction') && $deposit->transaction) {
            $data['transaction'] = DateOnly::model($deposit->transaction, ['occurred_at']);
        }

        return $data;
    }
}
