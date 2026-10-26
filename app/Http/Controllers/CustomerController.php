<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\PaymentTerm;
use App\Models\State;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $customers = Customer::query()
            ->with(['addresses.state', 'paymentTerm'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('legal_name', 'like', "%{$search}%")
                        ->orWhere('trade_name', 'like', "%{$search}%")
                        ->orWhere('customer_code', 'like', "%{$search}%")
                        ->orWhere('gstin', 'like', "%{$search}%")
                        ->orWhere('mobile', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('legal_name')
            ->paginate(15)
            ->withQueryString();

        return view('customers.index', compact('customers', 'search'));
    }

    public function create(): View
    {
        return view('customers.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateCustomer($request);

        $customer = DB::transaction(function () use ($request, $data) {
            $customer = Customer::create([
                ...$data,
                'customer_code' => $this->nextCode($data['customer_code'] ?? null),
                'is_active' => $request->boolean('is_active'),
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);
            $this->syncAddresses($customer, $request->input('addresses', []));

            return $customer;
        });

        return redirect()
            ->route('customers.show', $customer)
            ->with('success', 'Customer '.$customer->customer_code.' created.');
    }

    public function show(Customer $customer): View
    {
        $customer->load([
            'addresses.state',
            'paymentTerm',
            'salesperson',
            'invoices' => fn ($query) => $query->latest('invoice_date')->limit(10),
        ]);

        $invoiceBase = $customer->invoices()->where('status', '!=', 'cancelled');

        $summary = [
            'invoice_count' => (clone $invoiceBase)->count(),
            'billed_total' => (float) (clone $invoiceBase)->whereIn('status', ['issued', 'partially_paid', 'paid'])->sum('grand_total'),
            'outstanding' => (float) (clone $invoiceBase)->whereIn('status', ['issued', 'partially_paid'])->sum('balance_due'),
            'paid_total' => (float) (clone $invoiceBase)->whereIn('status', ['issued', 'partially_paid', 'paid'])->sum('amount_paid'),
        ];

        return view('customers.show', compact('customer', 'summary'));
    }

    public function edit(Customer $customer): View
    {
        $customer->load('addresses');

        return view('customers.edit', array_merge($this->formData(), compact('customer')));
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $data = $this->validateCustomer($request, $customer);

        DB::transaction(function () use ($request, $customer, $data) {
            $customer->update([
                ...$data,
                'customer_code' => $data['customer_code'] ?: $customer->customer_code,
                'is_active' => $request->boolean('is_active'),
                'updated_by' => auth()->id(),
            ]);
            $this->syncAddresses($customer, $request->input('addresses', []));
        });

        return redirect()
            ->route('customers.show', $customer)
            ->with('success', 'Customer updated.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        try {
            $customer->delete();
        } catch (QueryException) {
            return back()->with('error', 'This customer cannot be deleted because documents still reference it.');
        }

        return redirect()->route('customers.index')->with('success', 'Customer archived.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateCustomer(Request $request, ?Customer $customer = null): array
    {
        $codeRule = 'nullable|string|max:50|unique:customers,customer_code';
        if ($customer) {
            $codeRule .= ','.$customer->id;
        }

        $data = $request->validate([
            'customer_code' => $codeRule,
            'legal_name' => ['required', 'string', 'max:255'],
            'trade_name' => ['nullable', 'string', 'max:255'],
            'gstin' => ['nullable', 'string', 'size:15'],
            'pan' => ['nullable', 'string', 'size:10'],
            'gst_registration_type' => ['required', 'in:regular,composition,unregistered,sez,export'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'mobile' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'payment_term_id' => ['nullable', 'exists:payment_terms,id'],
            'opening_balance' => ['nullable', 'numeric'],
            'salesperson_id' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string'],
            'addresses' => ['nullable', 'array'],
            'addresses.*.type' => ['nullable', 'in:billing,shipping'],
            'addresses.*.label' => ['nullable', 'string', 'max:100'],
            'addresses.*.address_line1' => ['nullable', 'string', 'max:255'],
            'addresses.*.address_line2' => ['nullable', 'string', 'max:255'],
            'addresses.*.city' => ['nullable', 'string', 'max:100'],
            'addresses.*.district' => ['nullable', 'string', 'max:100'],
            'addresses.*.state_id' => ['nullable', 'exists:states,id'],
            'addresses.*.pin' => ['nullable', 'string', 'max:10'],
            'addresses.*.country' => ['nullable', 'string', 'max:100'],
        ]);

        $data['gstin'] = $data['gstin'] ? strtoupper($data['gstin']) : null;
        $data['pan'] = $data['pan'] ? strtoupper($data['pan']) : null;
        $data['credit_limit'] = $data['credit_limit'] ?? 0;
        $data['opening_balance'] = $data['opening_balance'] ?? 0;

        return $data;
    }

    /**
     * @param  array<int, array<string, mixed>>  $addresses
     */
    private function syncAddresses(Customer $customer, array $addresses): void
    {
        $customer->addresses()->delete();
        $rows = [];

        foreach (array_values($addresses) as $row) {
            if (! is_array($row)) {
                continue;
            }
            if (empty($row['address_line1']) && empty($row['city']) && empty($row['state_id']) && empty($row['pin'])) {
                continue;
            }
            $state = ! empty($row['state_id']) ? State::find($row['state_id']) : null;
            $rows[] = [
                'type' => $row['type'] ?? 'billing',
                'label' => $row['label'] ?? null,
                'address_line1' => $row['address_line1'] ?? null,
                'address_line2' => $row['address_line2'] ?? null,
                'city' => $row['city'] ?? null,
                'district' => $row['district'] ?? null,
                'state_id' => $state?->id,
                'state_code' => $state?->code,
                'pin' => $row['pin'] ?? null,
                'country' => $row['country'] ?? 'India',
                'is_default' => ! empty($row['is_default']),
            ];
        }

        $seenDefault = ['billing' => false, 'shipping' => false];
        foreach ($rows as $index => $row) {
            if ($row['is_default']) {
                $seenDefault[$row['type']] = true;
            }
            $rows[$index] = $row;
        }
        foreach (['billing', 'shipping'] as $type) {
            if ($seenDefault[$type]) {
                continue;
            }
            foreach ($rows as $index => $row) {
                if ($row['type'] === $type) {
                    $rows[$index]['is_default'] = true;
                    break;
                }
            }
        }

        foreach ($rows as $row) {
            $customer->addresses()->create($row);
        }
    }

    private function nextCode(?string $requested): string
    {
        if ($requested) {
            return strtoupper($requested);
        }

        $last = Customer::withTrashed()->orderByDesc('id')->value('customer_code');
        $number = 1;
        if ($last && preg_match('/(\d+)$/', $last, $matches)) {
            $number = ((int) $matches[1]) + 1;
        }

        return 'CUST-'.str_pad((string) $number, 4, '0', STR_PAD_LEFT);
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'states' => State::orderBy('name')->get(),
            'paymentTerms' => PaymentTerm::where('is_active', true)->orderBy('name')->get(),
            'users' => User::where('is_active', true)->orderBy('name')->get(),
        ];
    }
}
