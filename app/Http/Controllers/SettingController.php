<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\CompanyProfile;
use App\Models\NumberSeries;
use App\Models\SmtpSetting;
use App\Models\State;
use App\Models\TermsTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(Request $request): View
    {
        $tab = $request->query('tab', 'company');
        if (! in_array($tab, ['company', 'banks', 'series', 'terms', 'email'], true)) {
            $tab = 'company';
        }

        return view('settings.index', [
            'tab' => $tab,
            'company' => CompanyProfile::with('state')->first(),
            'states' => State::orderBy('name')->get(),
            'banks' => BankAccount::orderByDesc('is_default')->orderBy('bank_name')->get(),
            'series' => NumberSeries::orderBy('document_type')->get(),
            'terms' => TermsTemplate::orderBy('document_type')->orderBy('name')->get(),
            'smtp' => SmtpSetting::current(),
        ]);
    }

    public function updateSmtp(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'host' => ['nullable', 'string', 'max:255'],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'encryption' => ['required', 'in:tls,ssl,none'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
            'from_name' => ['nullable', 'string', 'max:255'],
            'from_email' => ['nullable', 'email', 'max:255'],
            'reply_to_email' => ['nullable', 'email', 'max:255'],
        ]);

        $smtp = SmtpSetting::current();
        $payload = [
            'host' => $data['host'] ?? null,
            'port' => $data['port'],
            'encryption' => $data['encryption'] === 'none' ? 'none' : $data['encryption'],
            'username' => $data['username'] ?? null,
            'from_name' => $data['from_name'] ?? null,
            'from_email' => $data['from_email'] ?? null,
            'reply_to_email' => $data['reply_to_email'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ];

        if (! empty($data['password'])) {
            $payload['password'] = $data['password'];
        }

        $smtp->fill($payload);
        $smtp->save();

        return redirect()->route('settings.index', ['tab' => 'email'])->with('success', 'SMTP settings saved.');
    }

    public function updateCompany(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'trade_name' => ['nullable', 'string', 'max:255'],
            'address_line1' => ['nullable', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'state_id' => ['nullable', 'exists:states,id'],
            'pin' => ['nullable', 'string', 'max:10'],
            'gstin' => ['nullable', 'string', 'size:15'],
            'pan' => ['nullable', 'string', 'size:10'],
            'udyam_no' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'declaration' => ['nullable', 'string'],
            'jurisdiction' => ['nullable', 'string', 'max:255'],
            'footer_text' => ['nullable', 'string'],
            'quotation_validity_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'round_off_rule' => ['required', 'in:nearest,up,down'],
            'eway_bill_threshold' => ['nullable', 'numeric', 'min:0'],
            'books_locked_upto' => ['nullable', 'date'],
        ]);

        $state = ! empty($data['state_id']) ? State::find($data['state_id']) : null;
        $data['state_code'] = $state?->code;
        $data['gstin'] = isset($data['gstin']) ? strtoupper((string) $data['gstin']) : null;
        $data['pan'] = isset($data['pan']) ? strtoupper((string) $data['pan']) : null;
        $data['prevent_negative_stock'] = $request->boolean('prevent_negative_stock');
        $data['credit_limit_hard_block'] = $request->boolean('credit_limit_hard_block');
        $data['quotation_validity_days'] = $data['quotation_validity_days'] ?? 7;
        $data['eway_bill_threshold'] = $data['eway_bill_threshold'] ?? 50000;

        $company = CompanyProfile::query()->first();
        if ($company) {
            $company->update($data);
        } else {
            CompanyProfile::create($data);
        }

        return redirect()->route('settings.index', ['tab' => 'company'])->with('success', 'Company profile saved.');
    }

    public function storeBank(Request $request): RedirectResponse
    {
        $data = $this->validateBank($request);
        DB::transaction(function () use ($request, $data) {
            if ($request->boolean('is_default')) {
                BankAccount::query()->update(['is_default' => false]);
            }
            BankAccount::create([
                ...$data,
                'is_default' => $request->boolean('is_default'),
                'is_active' => $request->boolean('is_active', true),
            ]);
        });

        return redirect()->route('settings.index', ['tab' => 'banks'])->with('success', 'Bank account added.');
    }

    public function updateBank(Request $request, BankAccount $bank): RedirectResponse
    {
        $data = $this->validateBank($request);
        DB::transaction(function () use ($request, $bank, $data) {
            if ($request->boolean('is_default')) {
                BankAccount::where('id', '!=', $bank->id)->update(['is_default' => false]);
            }
            $bank->update([
                ...$data,
                'is_default' => $request->boolean('is_default'),
                'is_active' => $request->boolean('is_active'),
            ]);
        });

        return redirect()->route('settings.index', ['tab' => 'banks'])->with('success', 'Bank account updated.');
    }

    public function destroyBank(BankAccount $bank): RedirectResponse
    {
        $bank->delete();

        return redirect()->route('settings.index', ['tab' => 'banks'])->with('success', 'Bank account removed.');
    }

    public function updateSeries(Request $request, NumberSeries $series): RedirectResponse
    {
        $data = $request->validate([
            'prefix' => ['nullable', 'string', 'max:30'],
            'suffix' => ['nullable', 'string', 'max:30'],
            'separator' => ['nullable', 'string', 'max:5'],
            'format_pattern' => ['required', 'string', 'max:100'],
            'starting_number' => ['required', 'integer', 'min:1'],
            'current_number' => ['required', 'integer', 'min:0'],
            'zero_padding' => ['required', 'integer', 'min:0', 'max:8'],
            'reset_rule' => ['required', 'in:financial_year,calendar_year,monthly,never'],
        ]);

        $series->update($data);

        return redirect()->route('settings.index', ['tab' => 'series'])->with('success', 'Number series updated.');
    }

    public function storeTerms(Request $request): RedirectResponse
    {
        $data = $this->validateTerms($request);
        DB::transaction(function () use ($request, $data) {
            if ($request->boolean('is_default')) {
                TermsTemplate::where('document_type', $data['document_type'])->update(['is_default' => false]);
            }
            TermsTemplate::create([
                ...$data,
                'is_default' => $request->boolean('is_default'),
                'is_active' => $request->boolean('is_active', true),
            ]);
        });

        return redirect()->route('settings.index', ['tab' => 'terms'])->with('success', 'Terms template added.');
    }

    public function updateTerms(Request $request, TermsTemplate $template): RedirectResponse
    {
        $data = $this->validateTerms($request);
        DB::transaction(function () use ($request, $template, $data) {
            if ($request->boolean('is_default')) {
                TermsTemplate::where('document_type', $data['document_type'])
                    ->where('id', '!=', $template->id)
                    ->update(['is_default' => false]);
            }
            $template->update([
                ...$data,
                'is_default' => $request->boolean('is_default'),
                'is_active' => $request->boolean('is_active'),
            ]);
        });

        return redirect()->route('settings.index', ['tab' => 'terms'])->with('success', 'Terms template updated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateBank(Request $request): array
    {
        $data = $request->validate([
            'account_holder' => ['required', 'string', 'max:255'],
            'bank_name' => ['required', 'string', 'max:255'],
            'branch' => ['nullable', 'string', 'max:255'],
            'account_number' => ['required', 'string', 'max:50'],
            'ifsc' => ['nullable', 'string', 'max:20'],
        ]);
        $data['ifsc'] = isset($data['ifsc']) ? strtoupper($data['ifsc']) : null;

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function validateTerms(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'document_type' => ['required', 'in:quotation,invoice'],
            'content' => ['required', 'string'],
        ]);
    }
}
