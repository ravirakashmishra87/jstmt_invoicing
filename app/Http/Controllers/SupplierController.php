<?php

namespace App\Http\Controllers;

use App\Models\State;
use App\Models\Supplier;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $suppliers = Supplier::query()
            ->with('addresses')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('legal_name', 'like', "%{$search}%")
                        ->orWhere('supplier_code', 'like', "%{$search}%")
                        ->orWhere('gstin', 'like', "%{$search}%")
                        ->orWhere('mobile', 'like', "%{$search}%");
                });
            })
            ->orderBy('legal_name')
            ->paginate(15)
            ->withQueryString();

        return view('suppliers.index', compact('suppliers', 'search'));
    }

    public function create(): View
    {
        return view('suppliers.create', [
            'states' => State::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateSupplier($request);

        $supplier = DB::transaction(function () use ($request, $data) {
            $supplier = Supplier::create([
                ...$data,
                'supplier_code' => $this->nextCode($data['supplier_code'] ?? null),
                'is_active' => $request->boolean('is_active'),
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);
            $this->syncAddress($supplier, $request);

            return $supplier;
        });

        return redirect()
            ->route('suppliers.show', $supplier)
            ->with('success', 'Supplier '.$supplier->supplier_code.' created.');
    }

    public function show(Supplier $supplier): View
    {
        $supplier->load('addresses.state');

        return view('suppliers.show', compact('supplier'));
    }

    public function edit(Supplier $supplier): View
    {
        $supplier->load('addresses');

        return view('suppliers.edit', [
            'supplier' => $supplier,
            'states' => State::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $data = $this->validateSupplier($request, $supplier);

        DB::transaction(function () use ($request, $supplier, $data) {
            $supplier->update([
                ...$data,
                'supplier_code' => $data['supplier_code'] ?: $supplier->supplier_code,
                'is_active' => $request->boolean('is_active'),
                'updated_by' => auth()->id(),
            ]);
            $this->syncAddress($supplier, $request);
        });

        return redirect()
            ->route('suppliers.edit', $supplier)
            ->with('success', 'Supplier updated.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        try {
            $supplier->delete();
        } catch (QueryException) {
            return back()->with('error', 'This supplier cannot be deleted.');
        }

        return redirect()->route('suppliers.index')->with('success', 'Supplier archived.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateSupplier(Request $request, ?Supplier $supplier = null): array
    {
        $codeRule = 'nullable|string|max:50|unique:suppliers,supplier_code';
        if ($supplier) {
            $codeRule .= ','.$supplier->id;
        }

        $data = $request->validate([
            'supplier_code' => $codeRule,
            'legal_name' => ['required', 'string', 'max:255'],
            'trade_name' => ['nullable', 'string', 'max:255'],
            'gstin' => ['nullable', 'string', 'size:15'],
            'pan' => ['nullable', 'string', 'size:10'],
            'gst_registration_type' => ['required', 'in:regular,composition,unregistered,sez,export'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'mobile' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account' => ['nullable', 'string', 'max:50'],
            'bank_ifsc' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string'],
            'address_line1' => ['nullable', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'state_id' => ['nullable', 'exists:states,id'],
            'pin' => ['nullable', 'string', 'max:10'],
        ]);

        $data['gstin'] = $data['gstin'] ? strtoupper($data['gstin']) : null;
        $data['pan'] = $data['pan'] ? strtoupper($data['pan']) : null;
        $data['bank_ifsc'] = $data['bank_ifsc'] ? strtoupper($data['bank_ifsc']) : null;

        return $data;
    }

    private function syncAddress(Supplier $supplier, Request $request): void
    {
        $supplier->addresses()->delete();

        if (! $request->filled('address_line1') && ! $request->filled('city') && ! $request->filled('state_id')) {
            return;
        }

        $state = $request->filled('state_id') ? State::find($request->input('state_id')) : null;
        $supplier->addresses()->create([
            'type' => 'billing',
            'address_line1' => $request->input('address_line1'),
            'address_line2' => $request->input('address_line2'),
            'city' => $request->input('city'),
            'district' => $request->input('district'),
            'state_id' => $state?->id,
            'state_code' => $state?->code,
            'pin' => $request->input('pin'),
            'country' => 'India',
            'is_default' => true,
        ]);
    }

    private function nextCode(?string $requested): string
    {
        if ($requested) {
            return strtoupper($requested);
        }

        $last = Supplier::withTrashed()->orderByDesc('id')->value('supplier_code');
        $number = 1;
        if ($last && preg_match('/(\d+)$/', $last, $matches)) {
            $number = ((int) $matches[1]) + 1;
        }

        return 'SUP-'.str_pad((string) $number, 4, '0', STR_PAD_LEFT);
    }
}
