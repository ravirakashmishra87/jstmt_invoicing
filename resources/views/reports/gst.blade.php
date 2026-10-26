@extends('layouts.app')
@section('title', 'GST summary')
@section('page', 'GST summary')
@section('content')
@include('reports._filters')
<div class="card"><div class="table-responsive">
    <table class="table table-sm mb-0">
        <thead><tr><th>HSN/SAC</th><th class="text-end">Rate</th><th class="text-end">Taxable</th><th class="text-end">CGST</th><th class="text-end">SGST</th><th class="text-end">IGST</th><th class="text-end">Tax</th></tr></thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row->hsn_sac }}</td>
                    <td class="text-end">{{ \App\Helpers\IndianFormat::number($row->tax_rate, 2) }}%</td>
                    <td class="text-end">{{ \App\Helpers\IndianFormat::money($row->taxable_value) }}</td>
                    <td class="text-end">{{ \App\Helpers\IndianFormat::money($row->cgst_amount) }}</td>
                    <td class="text-end">{{ \App\Helpers\IndianFormat::money($row->sgst_amount) }}</td>
                    <td class="text-end">{{ \App\Helpers\IndianFormat::money($row->igst_amount) }}</td>
                    <td class="text-end">{{ \App\Helpers\IndianFormat::money($row->tax_amount) }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-muted p-3">No issued invoices in this range.</td></tr>
            @endforelse
        </tbody>
    </table>
</div></div>
@endsection
