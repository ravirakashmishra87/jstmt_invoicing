@extends('layouts.app')
@section('title', 'Settings')
@section('page', 'Settings')
@section('content')
<ul class="nav nav-tabs mb-3">
    @foreach(['company' => 'Company', 'banks' => 'Banks', 'series' => 'Number series', 'terms' => 'Terms', 'email' => 'Email / SMTP'] as $key => $label)
        <li class="nav-item"><a class="nav-link {{ $tab === $key ? 'active' : '' }}" href="{{ route('settings.index', ['tab' => $key]) }}">{{ $label }}</a></li>
    @endforeach
</ul>

@if($tab === 'company')
    <div class="card"><div class="card-body">
        <form method="POST" action="{{ route('settings.company') }}">
            @csrf @method('PUT')
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Legal name</label><input name="name" required class="form-control" value="{{ old('name', $company?->name ?? '') }}"></div>
                <div class="col-md-6"><label class="form-label">Trade name</label><input name="trade_name" class="form-control" value="{{ old('trade_name', $company?->trade_name ?? '') }}"></div>
                <div class="col-md-6"><label class="form-label">Address line 1</label><input name="address_line1" class="form-control" value="{{ old('address_line1', $company?->address_line1 ?? '') }}"></div>
                <div class="col-md-6"><label class="form-label">Address line 2</label><input name="address_line2" class="form-control" value="{{ old('address_line2', $company?->address_line2 ?? '') }}"></div>
                <div class="col-md-3"><label class="form-label">City</label><input name="city" class="form-control" value="{{ old('city', $company?->city ?? '') }}"></div>
                <div class="col-md-3"><label class="form-label">District</label><input name="district" class="form-control" value="{{ old('district', $company?->district ?? '') }}"></div>
                <div class="col-md-4">
                    <label class="form-label">State</label>
                    <select name="state_id" class="form-select">
                        <option value="">—</option>
                        @foreach($states as $state)
                            <option value="{{ $state->id }}" @selected((string) old('state_id', $company?->state_id ?? '') === (string) $state->id)>{{ $state->name }} ({{ $state->code }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2"><label class="form-label">PIN</label><input name="pin" class="form-control" value="{{ old('pin', $company?->pin ?? '') }}"></div>
                <div class="col-md-3"><label class="form-label">GSTIN</label><input name="gstin" maxlength="15" class="form-control" value="{{ old('gstin', $company?->gstin ?? '') }}"></div>
                <div class="col-md-3"><label class="form-label">PAN</label><input name="pan" maxlength="10" class="form-control" value="{{ old('pan', $company?->pan ?? '') }}"></div>
                <div class="col-md-3"><label class="form-label">Phone</label><input name="phone" class="form-control" value="{{ old('phone', $company?->phone ?? '') }}"></div>
                <div class="col-md-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="{{ old('email', $company?->email ?? '') }}"></div>
                <div class="col-md-4"><label class="form-label">Website</label><input name="website" class="form-control" value="{{ old('website', $company?->website ?? '') }}"></div>
                <div class="col-md-4"><label class="form-label">Udyam</label><input name="udyam_no" class="form-control" value="{{ old('udyam_no', $company?->udyam_no ?? '') }}"></div>
                <div class="col-md-4"><label class="form-label">Jurisdiction</label><input name="jurisdiction" class="form-control" value="{{ old('jurisdiction', $company?->jurisdiction ?? '') }}"></div>
                <div class="col-md-3"><label class="form-label">Quotation validity (days)</label><input type="number" name="quotation_validity_days" class="form-control" value="{{ old('quotation_validity_days', $company?->quotation_validity_days ?? 7) }}"></div>
                <div class="col-md-3">
                    <label class="form-label">Round off</label>
                    <select name="round_off_rule" class="form-select">
                        @foreach(['nearest' => 'Nearest rupee', 'up' => 'Round up', 'down' => 'Round down'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('round_off_rule', $company?->round_off_rule ?? 'nearest') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3"><label class="form-label">E-way threshold</label><input type="number" step="0.01" name="eway_bill_threshold" class="form-control" value="{{ old('eway_bill_threshold', $company?->eway_bill_threshold ?? 50000) }}"></div>
                <div class="col-md-3"><label class="form-label">Books locked up to</label><input type="date" name="books_locked_upto" class="form-control" value="{{ old('books_locked_upto', $company?->books_locked_upto?->toDateString()) }}"></div>
                <div class="col-12"><label class="form-label">Declaration</label><textarea name="declaration" class="form-control" rows="3">{{ old('declaration', $company?->declaration ?? '') }}</textarea></div>
                <div class="col-12"><label class="form-label">Footer</label><textarea name="footer_text" class="form-control" rows="2">{{ old('footer_text', $company?->footer_text ?? '') }}</textarea></div>
                <div class="col-md-6">
                    <input type="hidden" name="prevent_negative_stock" value="0">
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="prevent_negative_stock" value="1" @checked(in_array((string) old('prevent_negative_stock', ($company?->prevent_negative_stock ?? true) ? '1' : '0'), ['1'], true))><label class="form-check-label">Prevent negative stock</label></div>
                </div>
                <div class="col-md-6">
                    <input type="hidden" name="credit_limit_hard_block" value="0">
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="credit_limit_hard_block" value="1" @checked(in_array((string) old('credit_limit_hard_block', ($company?->credit_limit_hard_block ?? false) ? '1' : '0'), ['1'], true))><label class="form-check-label">Hard block on credit limit</label></div>
                </div>
            </div>
            @if(auth()->user()->hasPermission('settings.edit'))
                <button class="btn btn-primary mt-3">Save company</button>
            @endif
        </form>
    </div></div>
@endif

@if($tab === 'banks')
    <div class="card mb-3"><div class="card-body">
        <h2 class="h6">Add bank account</h2>
        <form method="POST" action="{{ route('settings.banks.store') }}" class="row g-2">
            @csrf
            <div class="col-md-3"><input name="account_holder" class="form-control" placeholder="Account holder" required value="{{ old('account_holder') }}"></div>
            <div class="col-md-3"><input name="bank_name" class="form-control" placeholder="Bank" required value="{{ old('bank_name') }}"></div>
            <div class="col-md-2"><input name="branch" class="form-control" placeholder="Branch" value="{{ old('branch') }}"></div>
            <div class="col-md-2"><input name="account_number" class="form-control" placeholder="Account no." required value="{{ old('account_number') }}"></div>
            <div class="col-md-2"><input name="ifsc" class="form-control" placeholder="IFSC" value="{{ old('ifsc') }}"></div>
            <div class="col-12">
                <input type="hidden" name="is_default" value="0"><input type="hidden" name="is_active" value="0">
                <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="is_default" value="1" checked><label class="form-check-label">Default</label></div>
                <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="is_active" value="1" checked><label class="form-check-label">Active</label></div>
                <button class="btn btn-primary btn-sm">Add</button>
            </div>
        </form>
    </div></div>
    @foreach($banks as $bank)
        <div class="card mb-2"><div class="card-body">
            <form method="POST" action="{{ route('settings.banks.update', $bank) }}" class="row g-2">
                @csrf @method('PUT')
                <div class="col-md-3"><input name="account_holder" class="form-control" value="{{ $bank->account_holder }}" required></div>
                <div class="col-md-2"><input name="bank_name" class="form-control" value="{{ $bank->bank_name }}" required></div>
                <div class="col-md-2"><input name="branch" class="form-control" value="{{ $bank->branch }}"></div>
                <div class="col-md-2"><input name="account_number" class="form-control" value="{{ $bank->account_number }}" required></div>
                <div class="col-md-2"><input name="ifsc" class="form-control" value="{{ $bank->ifsc }}"></div>
                <div class="col-md-1 d-flex align-items-center gap-2">
                    <input type="hidden" name="is_default" value="0">
                    <input type="checkbox" name="is_default" value="1" title="Default" @checked($bank->is_default)>
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" title="Active" @checked($bank->is_active)>
                </div>
                <div class="col-12"><button class="btn btn-sm btn-outline-primary">Update</button></div>
            </form>
            <form method="POST" action="{{ route('settings.banks.destroy', $bank) }}" class="mt-2" onsubmit="return confirm('Remove this bank account?')">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-outline-danger">Delete</button>
            </form>
        </div></div>
    @endforeach
@endif

@if($tab === 'series')
    @foreach($series as $row)
        <div class="card mb-2"><div class="card-body">
            <form method="POST" action="{{ route('settings.series.update', $row) }}" class="row g-2">
                @csrf @method('PUT')
                <div class="col-md-2"><label class="form-label">Document</label><input class="form-control" value="{{ $row->document_type }}" disabled></div>
                <div class="col-md-4"><label class="form-label">Pattern</label><input name="format_pattern" class="form-control" required value="{{ $row->format_pattern }}"></div>
                <div class="col-md-2"><label class="form-label">Prefix</label><input name="prefix" class="form-control" value="{{ $row->prefix }}"></div>
                <div class="col-md-1"><label class="form-label">Sep</label><input name="separator" class="form-control" value="{{ $row->separator }}"></div>
                <div class="col-md-1"><label class="form-label">Pad</label><input type="number" name="zero_padding" class="form-control" value="{{ $row->zero_padding }}"></div>
                <div class="col-md-2">
                    <label class="form-label">Reset</label>
                    <select name="reset_rule" class="form-select">
                        @foreach(['financial_year', 'calendar_year', 'monthly', 'never'] as $rule)
                            <option value="{{ $rule }}" @selected($row->reset_rule === $rule)>{{ \Illuminate\Support\Str::headline($rule) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2"><label class="form-label">Start</label><input type="number" name="starting_number" class="form-control" value="{{ $row->starting_number }}"></div>
                <div class="col-md-2"><label class="form-label">Current</label><input type="number" name="current_number" class="form-control" value="{{ $row->current_number }}"></div>
                <div class="col-md-2"><label class="form-label">Suffix</label><input name="suffix" class="form-control" value="{{ $row->suffix }}"></div>
                <div class="col-12"><button class="btn btn-sm btn-primary">Save series</button></div>
            </form>
        </div></div>
    @endforeach
    @if($series->isEmpty())
        <div class="alert alert-secondary">Number series are created when the database is seeded.</div>
    @endif
@endif

@if($tab === 'terms')
    <div class="card mb-3"><div class="card-body">
        <h2 class="h6">New template</h2>
        <form method="POST" action="{{ route('settings.terms.store') }}">
            @csrf
            <div class="row g-2">
                <div class="col-md-4"><input name="name" class="form-control" placeholder="Name" required></div>
                <div class="col-md-3">
                    <select name="document_type" class="form-select">
                        <option value="quotation">Quotation</option>
                        <option value="invoice">Invoice</option>
                    </select>
                </div>
                <div class="col-12"><textarea name="content" class="form-control" rows="4" required placeholder="Terms"></textarea></div>
                <div class="col-12">
                    <input type="hidden" name="is_default" value="0"><input type="hidden" name="is_active" value="0">
                    <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="is_default" value="1"><label class="form-check-label">Default</label></div>
                    <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="is_active" value="1" checked><label class="form-check-label">Active</label></div>
                    <button class="btn btn-primary btn-sm">Add template</button>
                </div>
            </div>
        </form>
    </div></div>
    @foreach($terms as $template)
        <div class="card mb-2"><div class="card-body">
            <form method="POST" action="{{ route('settings.terms.update', $template) }}">
                @csrf @method('PUT')
                <div class="row g-2">
                    <div class="col-md-4"><input name="name" class="form-control" value="{{ $template->name }}" required></div>
                    <div class="col-md-3">
                        <select name="document_type" class="form-select">
                            <option value="quotation" @selected($template->document_type === 'quotation')>Quotation</option>
                            <option value="invoice" @selected($template->document_type === 'invoice')>Invoice</option>
                        </select>
                    </div>
                    <div class="col-12"><textarea name="content" class="form-control" rows="4" required>{{ $template->content }}</textarea></div>
                    <div class="col-12">
                        <input type="hidden" name="is_default" value="0"><input type="hidden" name="is_active" value="0">
                        <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="is_default" value="1" @checked($template->is_default)><label class="form-check-label">Default</label></div>
                        <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="is_active" value="1" @checked($template->is_active)><label class="form-check-label">Active</label></div>
                        <button class="btn btn-sm btn-outline-primary">Update</button>
                    </div>
                </div>
            </form>
        </div></div>
    @endforeach
@endif

@if($tab === 'email')
    <div class="list-hero mb-3">
        <div class="kicker">Outbound mail</div>
        <h1>SMTP settings</h1>
        <p>Configure the mail server used by Communication to send quotations, invoices, receipts, and general messages.</p>
    </div>
    <div class="card"><div class="card-body">
        <form method="POST" action="{{ route('settings.smtp') }}">
            @csrf @method('PUT')
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">SMTP host</label>
                    <input name="host" class="form-control" placeholder="smtp.gmail.com" value="{{ old('host', $smtp->host) }}">
                    <div class="form-text">Gmail: <code>smtp.gmail.com</code> (not smtp.google.com). Use an App Password with 2FA enabled.</div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Port</label>
                    <input type="number" name="port" class="form-control" value="{{ old('port', $smtp->port ?: 587) }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Encryption</label>
                    <select name="encryption" class="form-select">
                        @foreach(['tls' => 'TLS', 'ssl' => 'SSL', 'none' => 'None'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('encryption', $smtp->encryption ?: 'tls') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Username</label>
                    <input name="username" class="form-control" autocomplete="off" value="{{ old('username', $smtp->username) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" autocomplete="new-password" placeholder="{{ $smtp->password ? 'Leave blank to keep current password' : '' }}">
                    <div class="form-text">Stored encrypted. Leave blank if you do not want to change it.</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">From name</label>
                    <input name="from_name" class="form-control" value="{{ old('from_name', $smtp->from_name) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">From email</label>
                    <input type="email" name="from_email" class="form-control" value="{{ old('from_email', $smtp->from_email) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Reply-to email</label>
                    <input type="email" name="reply_to_email" class="form-control" value="{{ old('reply_to_email', $smtp->reply_to_email) }}">
                </div>
                <div class="col-12">
                    <input type="hidden" name="is_active" value="0">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="smtp_active" @checked(old('is_active', $smtp->is_active ? '1' : '0') === '1')>
                        <label class="form-check-label" for="smtp_active">Activate SMTP for outbound email</label>
                    </div>
                </div>
            </div>
            @if(auth()->user()->hasPermission('settings.edit'))
                <button class="btn btn-primary mt-3" type="submit"><i class="bi bi-check2-circle"></i> Save SMTP settings</button>
            @endif
        </form>
    </div></div>
@endif
@endsection
