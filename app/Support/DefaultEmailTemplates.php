<?php

namespace App\Support;

class DefaultEmailTemplates
{
    /**
     * @return list<array{code: string, name: string, document_type: string, subject: string, body: string}>
     */
    public static function catalog(): array
    {
        return [
            [
                'code' => 'general',
                'name' => 'General correspondence',
                'document_type' => 'general',
                'subject' => '{{company_name}} — a note for you',
                'body' => "Dear {{customer_name}},\n\n{{message}}\n\nShould you need any clarification, we are glad to assist.\n\nWith regards,\n{{company_name}}",
            ],
            [
                'code' => 'quotation',
                'name' => 'Quotation delivery',
                'document_type' => 'quotation',
                'subject' => 'Quotation {{document_number}} from {{company_name}}',
                'body' => "Dear {{customer_name}},\n\nThank you for your enquiry. Please find attached our formal quotation {{document_number}} dated {{document_date}}.\n\n{{document_subject}}\n\nQuoted value: {{amount}}\n\nWe would be pleased to discuss any detail and look forward to your confirmation.\n\nWith regards,\n{{company_name}}",
            ],
            [
                'code' => 'invoice',
                'name' => 'Invoice delivery',
                'document_type' => 'invoice',
                'subject' => 'Tax invoice {{document_number}} — {{company_name}}',
                'body' => "Dear {{customer_name}},\n\nPlease find attached tax invoice {{document_number}} dated {{document_date}} for {{amount}}.\n\nKindly arrange settlement as per the agreed commercial terms. For any billing query, reply to this email and our accounts team will assist you.\n\nWith regards,\n{{company_name}}",
            ],
            [
                'code' => 'receipt',
                'name' => 'Payment receipt',
                'document_type' => 'receipt',
                'subject' => 'Payment acknowledgement {{document_number}} — {{company_name}}',
                'body' => "Dear {{customer_name}},\n\nWe gratefully acknowledge your payment. Attached is receipt {{document_number}} dated {{document_date}} for {{amount}}.\n\nThank you for your continued trust in {{company_name}}.\n\nWith regards,\n{{company_name}}",
            ],
        ];
    }

    public static function typeLabel(string $documentType): string
    {
        return match ($documentType) {
            'quotation' => 'Quotation',
            'invoice' => 'Tax Invoice',
            'receipt' => 'Payment Receipt',
            default => 'Correspondence',
        };
    }
}
