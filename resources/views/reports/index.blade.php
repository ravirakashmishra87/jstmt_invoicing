@extends('layouts.app')
@section('title', 'Reports')
@section('page', 'Reports')
@section('content')
<div class="row g-3">
    @foreach([
        ['reports.sales', 'Sales register', 'Issued invoices with taxable value and tax.'],
        ['reports.stock', 'Stock summary', 'On-hand quantity and stock value.'],
        ['reports.outstanding', 'Outstanding', 'Open invoices and overdue days.'],
        ['reports.gst', 'GST summary', 'HSN-wise taxable value and tax.'],
    ] as [$route, $title, $text])
        <div class="col-md-6">
            <a class="card text-decoration-none text-body h-100" href="{{ route($route) }}">
                <div class="card-body">
                    <div class="h5">{{ $title }}</div>
                    <div class="text-muted">{{ $text }}</div>
                </div>
            </a>
        </div>
    @endforeach
</div>
@endsection
