<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\PaymentMode;
use App\Models\Receipt;
use App\Models\ReceiptAllocation;
use App\Services\DocumentNumberService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ReceiptController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $receipts = Receipt::query()
            ->with(['customer', 'paymentMode'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('receipt_number', 'like', "%{$search}%")
                        ->orWhere('reference_no', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($customer) => $customer->where('legal_name', 'like', "%{$search}%"));
                });
            })
            ->latest('receipt_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('receipts.index', compact('receipts', 'search'));
    }

    public function create(Request $request): View
    {
        $customerId = $request->query('customer_id');
        $customer = $customerId ? Customer::find($customerId) : null;

        $openInvoices = collect();
        if ($customer) {
            $openInvoices = Invoice::query()
                ->where('customer_id', $customer->id)
                ->whereIn('status', ['issued', 'partially_paid'])
                ->where('balance_due', '>', 0)
                ->orderBy('invoice_date')
                ->get();
        }

        return view('receipts.create', [
            'customers' => Customer::where('is_active', true)->orderBy('legal_name')->get(),
            'paymentModes' => PaymentMode::where('is_active', true)->orderBy('name')->get(),
            'banks' => BankAccount::where('is_active', true)->orderByDesc('is_default')->orderBy('bank_name')->get(),
            'customer' => $customer,
            'openInvoices' => $openInvoices,
        ]);
    }

    public function store(Request $request, DocumentNumberService $numbers): RedirectResponse
    {
        $data = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'receipt_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_mode_id' => ['nullable', 'exists:payment_modes,id'],
            'bank_account_id' => ['nullable', 'exists:bank_accounts,id'],
            'reference_no' => ['nullable', 'string', 'max:255'],
            'remarks' => ['nullable', 'string'],
            'allocations' => ['nullable', 'array'],
            'allocations.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        try {
            $receipt = DB::transaction(function () use ($data, $numbers) {
                $amount = round((float) $data['amount'], 2);
                $allocated = 0.0;
                $rows = [];

                foreach ($data['allocations'] ?? [] as $invoiceId => $raw) {
                    $line = round((float) $raw, 2);
                    if ($line <= 0) {
                        continue;
                    }

                    $invoice = Invoice::lockForUpdate()->find($invoiceId);
                    if (! $invoice || (int) $invoice->customer_id !== (int) $data['customer_id']) {
                        throw ValidationException::withMessages([
                            'allocations' => 'An allocation does not belong to the selected customer.',
                        ]);
                    }
                    if (! in_array($invoice->status, ['issued', 'partially_paid'], true)) {
                        throw ValidationException::withMessages([
                            'allocations' => $invoice->invoice_number.' is not open for receipt.',
                        ]);
                    }
                    if ($line > (float) $invoice->balance_due + 0.009) {
                        throw ValidationException::withMessages([
                            'allocations' => 'Allocation for '.$invoice->invoice_number.' exceeds the balance due.',
                        ]);
                    }

                    $rows[] = [$invoice, $line];
                    $allocated += $line;
                }

                $allocated = round($allocated, 2);
                if ($allocated - $amount > 0.009) {
                    throw ValidationException::withMessages([
                        'amount' => 'Allocations cannot exceed the receipt amount.',
                    ]);
                }

                $receipt = Receipt::create([
                    'receipt_number' => $numbers->next('receipt'),
                    'receipt_date' => $data['receipt_date'],
                    'customer_id' => $data['customer_id'],
                    'amount' => $amount,
                    'payment_mode_id' => $data['payment_mode_id'] ?? null,
                    'reference_no' => $data['reference_no'] ?? null,
                    'bank_account_id' => $data['bank_account_id'] ?? null,
                    'remarks' => $data['remarks'] ?? null,
                    'unallocated_amount' => round($amount - $allocated, 2),
                    'status' => 'posted',
                    'created_by' => auth()->id(),
                ]);

                foreach ($rows as [$invoice, $line]) {
                    ReceiptAllocation::create([
                        'receipt_id' => $receipt->id,
                        'invoice_id' => $invoice->id,
                        'allocation_type' => 'invoice',
                        'amount' => $line,
                    ]);

                    $invoice->amount_paid = round((float) $invoice->amount_paid + $line, 2);
                    $invoice->balance_due = round((float) $invoice->grand_total - (float) $invoice->amount_paid, 2);
                    if ($invoice->balance_due <= 0.009) {
                        $invoice->balance_due = 0;
                        $invoice->status = 'paid';
                    } else {
                        $invoice->status = 'partially_paid';
                    }
                    $invoice->updated_by = auth()->id();
                    $invoice->save();
                }

                return $receipt;
            });
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())->withInput();
        }

        return redirect()
            ->route('receipts.show', $receipt)
            ->with('success', 'Receipt '.$receipt->receipt_number.' posted.');
    }

    public function show(Receipt $receipt): View
    {
        $receipt->load(['customer', 'paymentMode', 'bankAccount', 'allocations.invoice']);

        return view('receipts.show', compact('receipt'));
    }
}
