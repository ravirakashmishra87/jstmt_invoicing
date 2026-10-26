@extends('layouts.app')
@section('title', 'New receipt')
@section('page', 'New receipt')
@section('content')
<div class="card mb-3"><div class="card-body">
    <form method="GET" class="row g-2">
        <div class="col-md-6">
            <label class="form-label">Customer</label>
            <select name="customer_id" class="form-select" required>
                <option value="">Select customer</option>
                @foreach($customers as $row)
                    <option value="{{ $row->id }}" @selected((string) optional($customer)->id === (string) $row->id)>{{ $row->customer_code }} — {{ $row->legal_name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2 d-flex align-items-end"><button class="btn btn-outline-primary">Load invoices</button></div>
    </form>
</div></div>

@if($customer)
<form method="POST" action="{{ route('receipts.store') }}">
    @csrf
    <input type="hidden" name="customer_id" value="{{ $customer->id }}">
    <div class="card mb-3"><div class="card-body row g-3">
        <div class="col-md-3"><label class="form-label">Date</label><input type="date" name="receipt_date" class="form-control" required value="{{ old('receipt_date', now()->toDateString()) }}"></div>
        <div class="col-md-3"><label class="form-label">Amount</label><input type="number" step="0.01" name="amount" class="form-control" required value="{{ old('amount') }}"></div>
        <div class="col-md-3">
            <label class="form-label">Mode</label>
            <select name="payment_mode_id" class="form-select">
                <option value="">—</option>
                @foreach($paymentModes as $mode)
                    <option value="{{ $mode->id }}" @selected((string) old('payment_mode_id') === (string) $mode->id)>{{ $mode->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Bank</label>
            <select name="bank_account_id" class="form-select">
                <option value="">—</option>
                @foreach($banks as $bank)
                    <option value="{{ $bank->id }}">{{ $bank->bank_name }} · {{ $bank->account_number }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4"><label class="form-label">Reference</label><input name="reference_no" class="form-control" value="{{ old('reference_no') }}"></div>
        <div class="col-md-8"><label class="form-label">Remarks</label><input name="remarks" class="form-control" value="{{ old('remarks') }}"></div>
    </div></div>
    <div class="card mb-3">
        <div class="card-header bg-white">Allocate to open invoices</div>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead><tr><th>Invoice</th><th>Date</th><th class="text-end">Balance</th><th style="width: 160px;">Allocate</th></tr></thead>
                <tbody>
                    @forelse($openInvoices as $invoice)
                        <tr>
                            <td><a href="{{ route('invoices.show', $invoice) }}">{{ $invoice->invoice_number }}</a></td>
                            <td>{{ \App\Helpers\IndianFormat::date($invoice->invoice_date) }}</td>
                            <td class="text-end">{{ \App\Helpers\IndianFormat::money($invoice->balance_due) }}</td>
                            <td><input type="number" step="0.01" min="0" name="allocations[{{ $invoice->id }}]" class="form-control form-control-sm" value="{{ old('allocations.'.$invoice->id) }}"></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-muted p-3">No open invoices. The receipt will stay unallocated.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <button class="btn btn-primary">Post receipt</button>
</form>
@endif
@endsection
