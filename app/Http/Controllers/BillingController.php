<?php

namespace App\Http\Controllers;

use App\Models\Balance;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Services\XenditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        $transactions = $team->transactions()
            ->latest()
            ->where('status', '!=', 'pending')
            ->take(10)
            ->get()
            ->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'created_at' => $transaction->created_at,
                    'amount' => $transaction->amount,
                    'type' => $transaction->type,
                    'description' => $transaction->description,
                    'status' => $transaction->status,
                    'payment_method' => $transaction->payment_method,
                    'payment_details' => $transaction->payment_details,
                    'formatted_status' => ucfirst($transaction->status),
                    'status_color' => $this->getStatusColor($transaction->status),
                    'formatted_type' => ucfirst($transaction->type),
                    'type_color' => $this->getTypeColor($transaction->type),
                ];
            });

        return Inertia::render('Billing/Index', [
            'balance' => $balance,
            'transactions' => $transactions,
            'flash' => [
                'success' => session('success'),
                'error' => session('error'),
            ],
        ]);
    }

    private function getStatusColor($status)
    {
        return match ($status) {
            'completed' => 'green',
            'pending' => 'yellow',
            'failed' => 'red',
            default => 'gray'
        };
    }

    private function getTypeColor($type)
    {
        return match ($type) {
            'topup' => 'green',
            'usage' => 'blue',
            'refund' => 'yellow',
            default => 'gray'
        };
    }

    public function topup(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:10000',
        ]);

        $team = $request->user()->currentTeam;
        $externalId = 'topup_' . $team->id . '_' . time();

        $transaction = Transaction::create([
            'team_id' => $team->id,
            'amount' => $request->amount,
            'type' => 'topup',
            'description' => 'Credit top-up',
            'status' => 'pending',
            'external_id' => $externalId,
        ]);

        $params = [
            'external_id' => $externalId,
            'amount' => $request->amount,
            'payer_email' => $request->user()->email,
            'customer_name' => $request->user()->name,
            'description' => 'Top up credits for ' . $team->name,
            'success_redirect_url' => route('billing.topup.success', ['transaction' => $transaction->id]),
            'failure_redirect_url' => route('billing.topup.failure', ['transaction' => $transaction->id]),
        ];

        try {
            $invoice = $this->xenditService->createInvoice($params);
            
            $transaction->update([
                'payment_id' => $invoice['id'],
                'payment_details' => json_encode($invoice),
            ]);

            return Inertia::location($invoice['invoice_url']);
        } catch (\Exception $e) {
            $transaction->update(['status' => 'failed']);
            Log::error('Xendit invoice creation failed', [
                'error' => $e->getMessage(),
                'transaction_id' => $transaction->id
            ]);
            return redirect()->back()->with('error', 'Unable to process payment. Please try again.');
        }
    }

    public function topupSuccess(Request $request)
    {
        $transaction = Transaction::findOrFail($request->transaction);
        
        if ($transaction->status !== 'completed') {
            return redirect()->route('billing.index')
                ->with('error', 'Payment is still being processed. Please wait for confirmation.');
        }

        return redirect()->route('billing.index')
            ->with('success', 'Payment successful! Your credits have been added.');
    }

    public function topupFailure(Request $request)
    {
        $transaction = Transaction::findOrFail($request->transaction);
        
        if ($transaction->status === 'completed') {
            return redirect()->route('billing.index')
                ->with('success', 'Payment has been completed successfully.');
        }

        $transaction->update(['status' => 'failed']);
        
        return redirect()->route('billing.index')
            ->with('error', 'Payment failed. Please try again or contact support if the issue persists.');
    }

    public function handleWebhook(Request $request)
    {
        $callbackToken = $request->header('x-callback-token');
        if ($callbackToken === null || !$this->xenditService->validateCallback($callbackToken)) {
            Log::warning('Invalid or missing Xendit webhook token');
            return response()->json(['error' => 'Invalid token', 'token' => $callbackToken], 401);
        }

        $payload = $request->all();
        Log::info('Xendit webhook received', $payload);

        // Handle direct invoice payment notification
        if (isset($payload['status']) && $payload['status'] === 'PAID') {
            try {
                DB::transaction(function () use ($payload) {
                    $transaction = Transaction::where('external_id', $payload['external_id'])
                        ->where('status', 'pending')
                        ->firstOrFail();

                    $transaction->update([
                        'status' => 'completed',
                        'payment_method' => $payload['payment_method'],
                        'payment_details' => json_encode($payload)
                    ]);

                    $balance = Balance::firstOrCreate(
                        ['team_id' => $transaction->team_id],
                        ['amount' => 0]
                    );
                    $balance->amount += $payload['amount'];
                    $balance->save();

                    Log::info('Successfully processed Xendit payment', [
                        'transaction_id' => $transaction->id,
                        'amount' => $payload['amount']
                    ]);
                });
            } catch (\Exception $e) {
                Log::error('Error processing Xendit webhook', [
                    'error' => $e->getMessage(),
                    'payload' => $payload
                ]);
                return response()->json(['error' => 'Processing error'], 500);
            }

            return response()->json(['success' => true]);
        }

        return response()->json(['success' => true]);
    }
}
