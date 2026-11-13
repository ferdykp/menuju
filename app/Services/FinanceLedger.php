<?php

namespace App\Services;

use App\Models\FinancialAccount;
use App\Models\FinancialTransaction;
use App\Models\Workspace;

class FinanceLedger
{
    public function balance(FinancialAccount $account): float
    {
        $outgoing = FinancialTransaction::query()->where('account_id', $account->id)->whereNull('voided_at');
        $incomingTransfers = FinancialTransaction::query()->where('destination_account_id', $account->id)->where('type', 'transfer')->whereNull('voided_at')->sum('amount');
        $credits = (clone $outgoing)->where('type', 'income')->sum('amount');
        $debits = (clone $outgoing)->whereIn('type', ['expense', 'transfer', 'savings_contribution'])->sum('amount');

        return round((float) $account->opening_balance + (float) $credits - (float) $debits + (float) $incomingTransfers, 2);
    }

    public function overview(Workspace $workspace): array
    {
        $accounts = $workspace->financialAccounts()->where('is_archived', false)->get();
        $activeTransactions = $workspace->financialTransactions()->whereNull('voided_at');
        $income = (clone $activeTransactions)->where('type', 'income')->sum('amount');
        $expense = (clone $activeTransactions)->where('type', 'expense')->sum('amount');
        $planned = $workspace->budgetCategories()->sum('planned_amount');
        $goals = $workspace->savingsGoals()->where('is_archived', false)->get();
        $saved = $goals->sum(fn ($goal) => $goal->deposits()->whereNull('reversed_at')->sum('amount'));

        return [
            'balance' => round($accounts->sum(fn ($account) => $this->balance($account)), 2),
            'income' => round((float) $income, 2),
            'expense' => round((float) $expense, 2),
            'planned_budget' => round((float) $planned, 2),
            'actual_budget' => round((float) $expense, 2),
            'savings_target' => round((float) $goals->sum('target_amount'), 2),
            'saved' => round((float) $saved, 2),
        ];
    }
}
