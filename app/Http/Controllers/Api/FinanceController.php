<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Workspace;
use App\Models\FinancialTransaction;
use App\Support\DateOnly;
use App\Services\FinanceLedger;
use Illuminate\Http\Request;

class FinanceController extends Controller
{
    public function show(Request $request, Workspace $workspace, FinanceLedger $ledger)
    {
        $this->authorize('view', $workspace);
        $summary = $ledger->overview($workspace);
        $summary['upcoming_payments'] = $workspace->paymentObligations()->where('status', '!=', 'paid')->whereNotNull('due_at')->orderBy('due_at')->limit(5)->get()->map(fn ($item) => DateOnly::model($item, ['due_at']));
        $summary['recent_transactions'] = $workspace->financialTransactions()->with(['account:id,name', 'destinationAccount:id,name'])->whereNull('voided_at')->orderByDesc('occurred_at')->orderByDesc('id')->limit(6)->get()->map(fn (FinancialTransaction $transaction) => DateOnly::model($transaction, ['occurred_at']));

        return response()->json(['summary' => $summary]);
    }
}
