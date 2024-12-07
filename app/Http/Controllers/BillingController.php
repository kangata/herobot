<?php

namespace App\Http\Controllers;

use App\Models\Balance;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Services\XenditService;

class BillingController extends Controller
{
    protected $xenditService;

    public function __construct(XenditService $xenditService)
    {
        $this->xenditService = $xenditService;
    }

    public function index(Request $request)
    {
        $team = $request->user()->currentTeam;
        $balance = $team->balance;
        $transactions = $team->transactions()->latest()->take(10)->get();

        return Inertia::render('Billing/Index', [
            'balance' => $balance,
            'transactions' => $transactions,
        ]);
    }

    public function topup(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:10',
        ]);

        $params = [
            'external_id' => 'topup_' . time(),
            'amount' => $request->amount,
            'payer_email' => $request->user()->email,
            'description' => 'Top up credits',
            'success_redirect_url' => route('billing.topup.success'),
            'failure_redirect_url' => route('billing.topup.failure'),
        ];

        $invoice = $this->xenditService->createInvoice($params);

        return Inertia::location($invoice['invoice_url']);
    }

    public function topupSuccess(Request $request)
    {
        // Handle successful top-up
        $team = $request->user()->currentTeam;
        $amount = $request->amount;

        $balance = $team->balance;
        $balance->amount += $amount;
        $balance->save();

        Transaction::create([
            'team_id' => $team->id,
            'amount' => $amount,
            'type' => 'topup',
            'description' => 'Credit top-up'
        ]);

        return redirect()->route('billing.index')->with('success', 'Top-up successful!');
    }

    public function topupFailure()
    {
        return redirect()->route('billing.index')->with('error', 'Top-up failed. Please try again.');
    }
}
