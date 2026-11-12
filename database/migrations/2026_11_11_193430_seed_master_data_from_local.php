<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

return new class extends Migration
{
    public function up(): void
    {
        $path = database_path('data/masters_from_local.json');
        if (! File::exists($path)) {
            throw new RuntimeException('Missing database/data/masters_from_local.json');
        }

        /** @var array<string, list<array<string, mixed>>> $data */
        $data = json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);
        $now = now();

        $stateIdMap = $this->upsertByKey($data['states'] ?? [], 'states', 'code', ['name', 'code', 'is_active'], $now);
        $taxIdMap = $this->upsertByKey($data['tax_rates'] ?? [], 'tax_rates', 'rate', ['name', 'rate', 'is_active'], $now);
        $this->upsertByKey($data['uoms'] ?? [], 'uoms', 'code', ['name', 'code', 'is_active'], $now);
        $this->upsertByKey($data['payment_modes'] ?? [], 'payment_modes', 'name', ['name', 'is_active'], $now);
        $this->upsertByKey($data['payment_terms'] ?? [], 'payment_terms', 'name', [
            'name', 'advance_percent', 'balance_percent', 'due_days', 'milestone', 'description', 'is_active',
        ], $now);
        $this->upsertByKey($data['brands'] ?? [], 'brands', 'name', ['name', 'is_active'], $now);
        $this->upsertByKey($data['warehouses'] ?? [], 'warehouses', 'code', [
            'name', 'code', 'address', 'is_default', 'is_active',
        ], $now);
        $this->upsertByKey($data['bank_accounts'] ?? [], 'bank_accounts', 'account_number', [
            'account_holder', 'bank_name', 'branch', 'account_number', 'ifsc', 'is_default', 'is_active',
        ], $now);
        $this->upsertByKey($data['financial_years'] ?? [], 'financial_years', 'name', [
            'name', 'start_date', 'end_date', 'is_current',
        ], $now);
        $this->upsertByKey($data['number_series'] ?? [], 'number_series', 'document_type', [
            'document_type', 'prefix', 'suffix', 'separator', 'format_pattern',
            'starting_number', 'current_number', 'zero_padding', 'reset_rule', 'last_reset_key',
        ], $now);
        $this->upsertByKey($data['terms_templates'] ?? [], 'terms_templates', 'name', [
            'name', 'document_type', 'content', 'is_default', 'is_active',
        ], $now);
        $this->upsertByKey($data['transporters'] ?? [], 'transporters', 'name', [
            'name', 'gstin', 'transporter_id', 'phone', 'is_active',
        ], $now);

        foreach ($data['product_categories'] ?? [] as $row) {
            $payload = $this->only($row, ['name', 'slug', 'sort_order', 'is_active']);
            $payload['parent_id'] = null;
            $payload['created_at'] = $now;
            $payload['updated_at'] = $now;
            DB::table('product_categories')->updateOrInsert(
                ['slug' => $row['slug']],
                $payload
            );
        }

        foreach ($data['hsn_sac_codes'] ?? [] as $row) {
            $payload = $this->only($row, ['code', 'description', 'type', 'is_active']);
            $payload['tax_rate_id'] = isset($row['tax_rate_id']) ? ($taxIdMap[(int) $row['tax_rate_id']] ?? null) : null;
            $payload['created_at'] = $now;
            $payload['updated_at'] = $now;
            DB::table('hsn_sac_codes')->updateOrInsert(
                ['code' => $row['code']],
                $payload
            );
        }

        $hsnIdMap = [];
        foreach ($data['hsn_sac_codes'] ?? [] as $row) {
            $hsnIdMap[(int) $row['id']] = DB::table('hsn_sac_codes')->where('code', $row['code'])->value('id');
        }

        foreach ($data['charge_types'] ?? [] as $row) {
            $payload = $this->only($row, ['name', 'code', 'is_taxable', 'is_active']);
            $payload['tax_rate_id'] = isset($row['tax_rate_id']) ? ($taxIdMap[(int) $row['tax_rate_id']] ?? null) : null;
            $payload['hsn_sac_id'] = isset($row['hsn_sac_id']) ? ($hsnIdMap[(int) $row['hsn_sac_id']] ?? null) : null;
            $payload['created_at'] = $now;
            $payload['updated_at'] = $now;
            DB::table('charge_types')->updateOrInsert(
                ['code' => $row['code']],
                $payload
            );
        }

        foreach ($data['company_profile'] ?? [] as $row) {
            $payload = $this->only($row, [
                'name', 'trade_name', 'address_line1', 'address_line2', 'city', 'district',
                'state_code', 'pin', 'country', 'gstin', 'pan', 'udyam_no', 'phone', 'email',
                'website', 'logo_path', 'brand_logo_path', 'signature_path', 'stamp_path',
                'declaration', 'jurisdiction', 'footer_text', 'quotation_validity_days',
                'stock_valuation', 'prevent_negative_stock', 'credit_limit_hard_block',
                'overdue_interest_enabled', 'overdue_interest_rate', 'eway_bill_threshold',
                'round_off_rule', 'books_locked_upto',
            ]);
            $payload['state_id'] = isset($row['state_id']) ? ($stateIdMap[(int) $row['state_id']] ?? null) : null;
            $payload['updated_at'] = $now;

            $existingId = DB::table('company_profile')->value('id');
            if ($existingId) {
                DB::table('company_profile')->where('id', $existingId)->update($payload);
            } else {
                $payload['created_at'] = $now;
                DB::table('company_profile')->insert($payload);
            }
        }
    }

    public function down(): void
    {
        // Master seed is additive; do not wipe production masters on rollback.
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  list<string>  $columns
     * @return array<int, int|string|null>
     */
    private function upsertByKey(array $rows, string $table, string $key, array $columns, mixed $now): array
    {
        $idMap = [];

        foreach ($rows as $row) {
            $oldId = isset($row['id']) ? (int) $row['id'] : null;
            $payload = $this->only($row, $columns);
            $payload['created_at'] = $now;
            $payload['updated_at'] = $now;

            DB::table($table)->updateOrInsert(
                [$key => $row[$key]],
                $payload
            );

            if ($oldId !== null) {
                $idMap[$oldId] = DB::table($table)->where($key, $row[$key])->value('id');
            }
        }

        return $idMap;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  list<string>  $columns
     * @return array<string, mixed>
     */
    private function only(array $row, array $columns): array
    {
        $payload = [];
        foreach ($columns as $column) {
            if (array_key_exists($column, $row)) {
                $payload[$column] = $row[$column];
            }
        }

        return $payload;
    }
};
