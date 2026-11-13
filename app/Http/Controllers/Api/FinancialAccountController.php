<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FinancialAccount;
use App\Models\Workspace;
use App\Services\FinanceLedger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FinancialAccountController extends Controller
{
    public function index(Request $request, Workspace $workspace, FinanceLedger $ledger)
    {
        $this->authorize('view', $workspace);
        $accounts = $workspace->financialAccounts()->orderBy('is_archived')->orderBy('name')->get();

        return response()->json(['accounts' => $accounts->map(fn ($account) => [...$account->toArray(), 'balance' => $ledger->balance($account)])]);
    }

    public function store(Request $request, Workspace $workspace, FinanceLedger $ledger)
    {
        $this->authorize('view', $workspace);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'], 'account_type' => ['required', Rule::in(['cash', 'bank', 'other'])],
            'institution' => ['nullable', 'string', 'max:120'], 'opening_balance' => ['required', 'numeric', 'min:-999999999999.99', 'max:999999999999.99'],
        ]);
        $account = $workspace->financialAccounts()->create($data + ['created_by' => $request->user()->id, 'currency' => 'IDR']);

        return response()->json(['account' => [...$account->toArray(), 'balance' => $ledger->balance($account)]], 201);
    }

    public function update(Request $request, FinancialAccount $account, FinanceLedger $ledger)
    {
        $this->authorize('view', $account->workspace);
        $account->update($request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:120'], 'account_type' => ['sometimes', Rule::in(['cash', 'bank', 'other'])],
            'institution' => ['sometimes', 'nullable', 'string', 'max:120'], 'is_archived' => ['sometimes', 'boolean'],
        ]));

        return response()->json(['account' => [...$account->fresh()->toArray(), 'balance' => $ledger->balance($account->fresh())]]);
    }
}
