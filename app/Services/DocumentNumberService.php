<?php

namespace App\Services;

use App\Models\NumberSeries;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DocumentNumberService
{
    public function next(string $documentType, array $tokens = []): string
    {
        return DB::transaction(function () use ($documentType, $tokens) {
            $series = NumberSeries::where('document_type', $documentType)
                ->lockForUpdate()
                ->first();

            if (! $series) {
                $series = NumberSeries::create([
                    'document_type' => $documentType,
                    'prefix' => '',
                    'suffix' => '',
                    'separator' => '/',
                    'format_pattern' => '{FY}{SEP}{SEQ}',
                    'starting_number' => 1,
                    'current_number' => 0,
                    'zero_padding' => 0,
                    'reset_rule' => 'financial_year',
                ]);
                $series = NumberSeries::where('id', $series->id)->lockForUpdate()->first();
            }

            $resetKey = $this->resetKey($series->reset_rule);
            if ($series->last_reset_key !== $resetKey) {
                $series->current_number = max(0, $series->starting_number - 1);
                $series->last_reset_key = $resetKey;
            }

            $series->current_number++;
            $series->save();

            return $this->format($series, $series->current_number, $tokens);
        });
    }

    public function preview(string $documentType, array $tokens = []): string
    {
        $series = NumberSeries::where('document_type', $documentType)->first();
        if (! $series) {
            return 'N/A';
        }

        $next = $series->current_number + 1;
        if ($series->last_reset_key !== $this->resetKey($series->reset_rule)) {
            $next = $series->starting_number;
        }

        return $this->format($series, $next, $tokens);
    }

    private function format(NumberSeries $series, int $number, array $tokens): string
    {
        $seq = $series->zero_padding > 0
            ? str_pad((string) $number, $series->zero_padding, '0', STR_PAD_LEFT)
            : (string) $number;

        $fy = $this->financialYearLabel();
        $now = Carbon::now();

        $replacements = array_merge([
            '{PREFIX}' => $series->prefix ?? '',
            '{SUFFIX}' => $series->suffix ?? '',
            '{SEP}' => $series->separator ?? '',
            '{SEQ}' => $seq,
            '{FY}' => $fy,
            '{YYYY}' => $now->format('Y'),
            '{YY}' => $now->format('y'),
            '{MM}' => $now->format('m'),
            '{MODELCODE}' => $tokens['MODELCODE'] ?? '',
            '{YEAR}' => $now->format('Y'),
        ], collect($tokens)->mapWithKeys(fn ($v, $k) => ['{'.$k.'}' => $v])->all());

        $pattern = $series->format_pattern ?: '{PREFIX}{FY}{SEP}{SEQ}{SUFFIX}';

        return strtr($pattern, $replacements);
    }

    private function resetKey(string $rule): string
    {
        $now = Carbon::now();

        return match ($rule) {
            'financial_year' => $this->financialYearLabel(),
            'calendar_year' => $now->format('Y'),
            'monthly' => $now->format('Y-m'),
            default => 'never',
        };
    }

    public function financialYearLabel(?Carbon $date = null): string
    {
        $date = $date ?? Carbon::now();
        $year = (int) $date->format('Y');
        $month = (int) $date->format('n');

        if ($month >= 4) {
            return $year.'-'.($year + 1);
        }

        return ($year - 1).'-'.$year;
    }
}
