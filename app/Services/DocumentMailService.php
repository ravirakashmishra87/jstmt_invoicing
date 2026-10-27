<?php

namespace App\Services;

use App\Helpers\IndianFormat;
use App\Mail\DocumentMail;
use App\Models\CompanyProfile;
use App\Models\EmailLog;
use App\Models\EmailTemplate;
use App\Models\Invoice;
use App\Models\Proforma;
use App\Models\Quotation;
use App\Models\Receipt;
use App\Models\SmtpSetting;
use App\Support\DefaultEmailTemplates;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

class DocumentMailService
{
    public function __construct(private DocumentPdfService $pdfs) {}

    public function applySmtp(?SmtpSetting $smtp = null): SmtpSetting
    {
        $smtp ??= SmtpSetting::current();

        if (! $smtp->isConfigured()) {
            throw new RuntimeException('SMTP is not configured. Add host, from email, and activate it under Settings → Email.');
        }

        $host = trim((string) $smtp->host);
        $port = (int) $smtp->port;

        if (strcasecmp($host, 'smtp.google.com') === 0) {
            throw new RuntimeException('SMTP host looks wrong. For Gmail use smtp.gmail.com (not smtp.google.com), port 587, encryption TLS, and a Google App Password.');
        }

        $this->assertSmtpReachable($host, $port);

        // Port drives the wire scheme: 465 = implicit TLS (smtps), 587/25 = plain then STARTTLS.
        $scheme = ($port === 465 || $smtp->encryption === 'ssl') ? 'smtps' : 'smtp';
        if ($port === 587 && $smtp->encryption === 'ssl') {
            throw new RuntimeException('Port 587 requires TLS (STARTTLS), not SSL. Use encryption TLS with smtp.gmail.com, or SSL with port 465.');
        }

        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp.transport', 'smtp');
        Config::set('mail.mailers.smtp.host', $host);
        Config::set('mail.mailers.smtp.port', $port);
        Config::set('mail.mailers.smtp.username', $smtp->username);
        Config::set('mail.mailers.smtp.password', $smtp->plainPassword());
        Config::set('mail.mailers.smtp.timeout', 8);
        Config::set('mail.mailers.smtp.scheme', $scheme);
        Config::set('mail.from.address', $smtp->from_email);
        Config::set('mail.from.name', $smtp->from_name ?: ($smtp->from_email ?? 'JST'));

        // Force a fresh transport with the settings above.
        if (app()->bound('mail.manager')) {
            app('mail.manager')->purge('smtp');
        }

        return $smtp;
    }

    private function assertSmtpReachable(string $host, int $port): void
    {
        $errno = 0;
        $errstr = '';
        $socket = @stream_socket_client(
            sprintf('tcp://%s:%d', $host, $port),
            $errno,
            $errstr,
            5
        );

        if (is_resource($socket)) {
            fclose($socket);

            return;
        }

        $hint = 'Check host/port, firewall, and that outbound SMTP is allowed.';
        if (str_contains(strtolower($host), 'gmail') || str_contains(strtolower($host), 'google')) {
            $hint = 'For Gmail use host smtp.gmail.com, port 587, encryption TLS, and a Google App Password (not your normal Gmail password).';
        }

        throw new RuntimeException(
            "Cannot connect to SMTP server {$host}:{$port}".($errstr !== '' ? " ({$errstr})" : '').'. '.$hint
        );
    }

    /**
     * @return array{to: string, customer_name: string, document_number: string|null, document_date: string|null, amount: string|null, document_subject: string|null, vars: array<string, string|null>}
     */
    public function resolveDocument(string $type, ?int $documentId = null, ?string $message = null): array
    {
        $company = CompanyProfile::query()->first();
        $companyName = $company?->trade_name ?: ($company?->name ?: 'JST Machine Tools');

        $base = [
            'company_name' => $companyName,
            'message' => $message ?? '',
            'customer_name' => 'Sir / Madam',
            'document_number' => '',
            'document_date' => '',
            'amount' => '',
            'document_subject' => '',
        ];

        if ($type === 'general' || ! $documentId) {
            return [
                'to' => '',
                'customer_name' => $base['customer_name'],
                'document_number' => null,
                'document_date' => null,
                'amount' => null,
                'document_subject' => null,
                'vars' => $base,
            ];
        }

        if ($type === 'quotation') {
            $doc = Quotation::with('customer')->findOrFail($documentId);
            $customer = $doc->customer;
            $base['customer_name'] = $customer->legal_name ?? ($doc->customer_name ?: 'Sir / Madam');
            $base['document_number'] = $doc->quotation_number;
            $base['document_date'] = IndianFormat::date($doc->quotation_date);
            $base['amount'] = IndianFormat::money($doc->grand_total);
            $base['document_subject'] = $doc->subject ?: '';

            return [
                'to' => trim((string) ($customer->email ?? '')),
                'customer_name' => $base['customer_name'],
                'document_number' => $doc->quotation_number,
                'document_date' => $base['document_date'],
                'amount' => $base['amount'],
                'document_subject' => $base['document_subject'],
                'vars' => $base,
            ];
        }

        if ($type === 'proforma') {
            $doc = Proforma::with('customer')->findOrFail($documentId);
            $customer = $doc->customer;
            $base['customer_name'] = $customer->legal_name ?? ($doc->buyer_name ?: 'Sir / Madam');
            $base['document_number'] = $doc->proforma_number;
            $base['document_date'] = IndianFormat::date($doc->proforma_date);
            $base['amount'] = IndianFormat::money($doc->grand_total);

            return [
                'to' => trim((string) ($customer->email ?? '')),
                'customer_name' => $base['customer_name'],
                'document_number' => $doc->proforma_number,
                'document_date' => $base['document_date'],
                'amount' => $base['amount'],
                'document_subject' => null,
                'vars' => $base,
            ];
        }

        if ($type === 'invoice') {
            $doc = Invoice::with('customer')->findOrFail($documentId);
            $customer = $doc->customer;
            $base['customer_name'] = $customer->legal_name ?? ($doc->buyer_name ?: 'Sir / Madam');
            $base['document_number'] = $doc->invoice_number;
            $base['document_date'] = IndianFormat::date($doc->invoice_date);
            $base['amount'] = IndianFormat::money($doc->grand_total);

            return [
                'to' => trim((string) ($customer->email ?? '')),
                'customer_name' => $base['customer_name'],
                'document_number' => $doc->invoice_number,
                'document_date' => $base['document_date'],
                'amount' => $base['amount'],
                'document_subject' => null,
                'vars' => $base,
            ];
        }

        if ($type === 'receipt') {
            $doc = Receipt::with('customer')->findOrFail($documentId);
            $customer = $doc->customer;
            $base['customer_name'] = $customer->legal_name ?? 'Sir / Madam';
            $base['document_number'] = $doc->receipt_number;
            $base['document_date'] = IndianFormat::date($doc->receipt_date);
            $base['amount'] = IndianFormat::money($doc->amount);

            return [
                'to' => trim((string) ($customer->email ?? '')),
                'customer_name' => $base['customer_name'],
                'document_number' => $doc->receipt_number,
                'document_date' => $base['document_date'],
                'amount' => $base['amount'],
                'document_subject' => null,
                'vars' => $base,
            ];
        }

        throw new RuntimeException('Unsupported document type.');
    }

    /**
     * @param  array{to: string, cc?: string|null, subject: string, body: string, document_type: string, document_id?: int|null}  $payload
     */
    public function send(array $payload, ?int $userId = null): EmailLog
    {
        // PDF + SMTP can exceed default 30s on Windows; avoid fatal timeout while waiting on QUIT.
        if (function_exists('set_time_limit')) {
            @set_time_limit(120);
        }

        $type = $payload['document_type'];
        $documentId = $payload['document_id'] ?? null;

        $attachment = null;
        $attachmentName = null;
        if (in_array($type, ['quotation', 'proforma', 'invoice', 'receipt'], true) && $documentId) {
            $pdf = $this->pdfs->forType($type, (int) $documentId);
            $attachment = $pdf['binary'];
            $attachmentName = $pdf['filename'];
        }

        $smtp = $this->applySmtp();

        $log = EmailLog::create([
            'to_email' => $payload['to'],
            'cc_email' => $payload['cc'] ?? null,
            'subject' => $payload['subject'],
            'body' => $payload['body'],
            'document_type' => $type,
            'document_id' => $documentId,
            'attachment_name' => $attachmentName,
            'status' => 'queued',
            'created_by' => $userId,
        ]);

        $previousSocketTimeout = ini_get('default_socket_timeout');
        ini_set('default_socket_timeout', '8');

        try {
            $company = CompanyProfile::query()->first();
            $companyName = $smtp->from_name
                ?: ($company?->trade_name ?: ($company?->name ?: 'JST Machine Tools'));
            $resolved = $this->resolveDocument($type, $documentId ? (int) $documentId : null);
            $meta = $this->emailMeta($type, $resolved);

            $mailable = new DocumentMail(
                subjectLine: $payload['subject'],
                bodyText: $payload['body'],
                companyName: $companyName,
                attachmentBinary: $attachment,
                attachmentName: $attachmentName,
                documentType: $type,
                meta: $meta,
                companyTagline: $company?->trade_name && $company?->name && $company->trade_name !== $company->name
                    ? $company->name
                    : null,
                companyContact: $this->companyContactLine($company, $smtp->from_email),
                footerNote: $company?->footer_text,
            );

            $pending = Mail::mailer('smtp')->to($payload['to']);
            if (! empty($payload['cc'])) {
                $pending->cc($payload['cc']);
            }
            if ($smtp->reply_to_email) {
                $mailable->replyTo($smtp->reply_to_email);
            }
            $pending->send($mailable);

            $log->update(['status' => 'sent']);
        } catch (\Throwable $exception) {
            $message = $this->friendlyMailError($exception->getMessage(), (string) $smtp->host);
            $log->update([
                'status' => 'failed',
                'error' => $message,
            ]);
            throw new RuntimeException('Email could not be sent: '.$message, 0, $exception);
        } finally {
            if ($previousSocketTimeout !== false) {
                ini_set('default_socket_timeout', (string) $previousSocketTimeout);
            }
            if (app()->bound('mail.manager')) {
                app('mail.manager')->purge('smtp');
            }
        }

        return $log->fresh();
    }

    private function friendlyMailError(string $message, string $host): string
    {
        $lower = strtolower($message.' '.$host);
        if (str_contains($lower, 'smtp.google.com')) {
            return 'Wrong Gmail host. Use smtp.gmail.com with port 587 and TLS.';
        }
        if (
            str_contains($lower, 'maximum execution time')
            || str_contains($lower, 'timed out')
            || str_contains($lower, 'timeout')
            || str_contains($lower, 'failed to connect')
            || str_contains($lower, 'unable to read')
            || str_contains($lower, 'closed unexpectedly')
        ) {
            return trim($message.' Verify SMTP: smtp.gmail.com, port 587, encryption TLS, and a Google App Password (not your normal password).');
        }

        return $message;
    }

    public function templatePreview(string $documentType, ?int $documentId = null, ?string $message = null): array
    {
        $template = EmailTemplate::forType($documentType) ?? EmailTemplate::forType('general');
        $resolved = $this->resolveDocument($documentType, $documentId, $message);

        return [
            'to' => $resolved['to'],
            'subject' => $template?->renderSubject($resolved['vars']) ?? '',
            'body' => $template?->renderBody($resolved['vars']) ?? '',
            'template_id' => $template?->id,
            'vars' => $resolved['vars'],
            'type_label' => DefaultEmailTemplates::typeLabel($documentType),
            'meta' => $this->emailMeta($documentType, $resolved),
        ];
    }

    /**
     * @param  array{document_number: string|null, document_date: string|null, amount: string|null, document_subject: string|null}  $resolved
     * @return array<string, string>
     */
    private function emailMeta(string $type, array $resolved): array
    {
        if ($type === 'general') {
            return [];
        }

        $meta = [];
        if (! empty($resolved['document_number'])) {
            $meta['Number'] = (string) $resolved['document_number'];
        }
        if (! empty($resolved['document_date'])) {
            $meta['Date'] = (string) $resolved['document_date'];
        }
        if (! empty($resolved['amount'])) {
            $meta['Amount'] = (string) $resolved['amount'];
        }

        return $meta;
    }

    private function companyContactLine(?CompanyProfile $company, ?string $fallbackEmail): ?string
    {
        if (! $company) {
            return $fallbackEmail;
        }

        $parts = array_values(array_filter([
            $company->phone,
            $company->email ?: $fallbackEmail,
            $company->website,
        ]));

        return $parts === [] ? null : implode(' · ', $parts);
    }
}
