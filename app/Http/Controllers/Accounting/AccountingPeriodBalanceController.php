<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Domain\Accounting\Models\AccountingPeriodBalance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountingPeriodBalanceController extends Controller
{
    /**
     * Display accounting period balances
     * Optionally filter by period_id
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = AccountingPeriodBalance::with(['accountingPeriod:id,period_code,status', 'account:id,account_code,name']);

        // Filter by period_id if provided
        if ($request->has('period_id')) {
            $query->where('accounting_period_id', $request->period_id);
        }

        // Filter by account_id if provided
        if ($request->has('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        // Order by period and account
        $balances = $query->orderBy('accounting_period_id', 'desc')
            ->orderBy('account_code')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Accounting period balances retrieved successfully.',
            'data' => [
                'count' => $balances->count(),
                'balances' => $balances->map(function ($balance) {
                    return [
                        'id' => $balance->id,
                        'period' => [
                            'id' => $balance->accountingPeriod->id,
                            'code' => $balance->accountingPeriod->period_code,
                            'status' => $balance->accountingPeriod->status,
                        ],
                        'account' => [
                            'id' => $balance->account_id,
                            'code' => $balance->account_code,
                            'name' => $balance->account_name,
                            'type' => $balance->account_type,
                        ],
                        'totals' => [
                            'debit' => number_format($balance->total_debit, 2, '.', ''),
                            'credit' => number_format($balance->total_credit, 2, '.', ''),
                            'balance' => number_format($balance->balance, 2, '.', ''),
                        ],
                        'created_at' => $balance->created_at->toIso8601String(),
                        'updated_at' => $balance->updated_at->toIso8601String(),
                    ];
                })
            ]
        ], 200);
    }
}
