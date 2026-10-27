<?php

namespace App\Http\Controllers;

use App\Models\CompanyProfile;
use App\Models\EmailLog;
use App\Models\EmailTemplate;
use App\Models\Invoice;
use App\Models\Proforma;
use App\Models\Quotation;
use App\Models\Receipt;
use App\Models\SmtpSetting;
use App\Services\DocumentMailService;
use App\Support\DefaultEmailTemplates;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CommunicationController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');

        $logs = EmailLog::query()
            ->with('creator')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('to_email', 'like', "%{$search}%")
                        ->orWhere('subject', 'like', "%{$search}%")
                        ->orWhere('document_type', 'like', "%{$search}%");
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('communication.index', [
            'logs' => $logs,
            'search' => $search,
            'status' => $status,
            'smtpReady' => SmtpSetting::current()->isConfigured(),
        ]);
    }

    public function compose(Request $request, DocumentMailService $mail): View
    {
        $type = (string) $request->query('type', 'general');
        if (! in_array($type, ['general', 'quotation', 'proforma', 'invoice', 'receipt'], true)) {
            $type = 'general';
        }

        $documentId = $request->query('document_id');
        $documentId = $documentId !== null && $documentId !== '' ? (int) $documentId : null;

        $preview = $mail->templatePreview($type, $documentId);
        $company = CompanyProfile::query()->first();

        return view('communication.compose', [
            'type' => $type,
            'documentId' => $documentId,
            'preview' => $preview,
            'companyName' => $company?->trade_name ?: ($company?->name ?: 'JST Machine Tools'),
            'templates' => EmailTemplate::where('is_active', true)->orderBy('name')->get(),
            'quotations' => Quotation::orderByDesc('id')->limit(100)->get(['id', 'quotation_number', 'customer_name', 'grand_total']),
            'proformas' => Proforma::orderByDesc('id')->limit(100)->get(['id', 'proforma_number', 'buyer_name', 'grand_total', 'status']),
            'invoices' => Invoice::orderByDesc('id')->limit(100)->get(['id', 'invoice_number', 'buyer_name', 'grand_total', 'status']),
            'receipts' => Receipt::with('customer:id,legal_name')->orderByDesc('id')->limit(100)->get(['id', 'receipt_number', 'customer_id', 'amount']),
            'smtpReady' => SmtpSetting::current()->isConfigured(),
        ]);
    }

    public function preview(Request $request, DocumentMailService $mail): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:general,quotation,proforma,invoice,receipt'],
            'document_id' => ['nullable', 'integer'],
            'template_id' => ['nullable', 'exists:email_templates,id'],
            'message' => ['nullable', 'string'],
        ]);

        $documentId = $data['document_id'] ?? null;
        $resolved = $mail->resolveDocument($data['type'], $documentId, $data['message'] ?? null);

        $template = null;
        if (! empty($data['template_id'])) {
            $template = EmailTemplate::find($data['template_id']);
        }
        $template ??= EmailTemplate::forType($data['type']) ?? EmailTemplate::forType('general');

        $preview = $mail->templatePreview($data['type'], $documentId, $data['message'] ?? null);

        return response()->json([
            'to' => $resolved['to'],
            'subject' => $template?->renderSubject($resolved['vars']) ?? '',
            'body' => $template?->renderBody($resolved['vars']) ?? '',
            'type_label' => DefaultEmailTemplates::typeLabel($data['type']),
            'meta' => $preview['meta'] ?? [],
        ]);
    }

    public function send(Request $request, DocumentMailService $mail): RedirectResponse
    {
        $data = $request->validate([
            'document_type' => ['required', 'in:general,quotation,proforma,invoice,receipt'],
            'document_id' => ['nullable', 'integer'],
            'to_email' => ['required', 'email', 'max:255'],
            'cc_email' => ['nullable', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
        ]);

        if (in_array($data['document_type'], ['quotation', 'proforma', 'invoice', 'receipt'], true) && empty($data['document_id'])) {
            return back()->withInput()->with('error', 'Select a document to attach.');
        }

        try {
            $mail->send([
                'to' => $data['to_email'],
                'cc' => $data['cc_email'] ?? null,
                'subject' => $data['subject'],
                'body' => $data['body'],
                'document_type' => $data['document_type'],
                'document_id' => $data['document_id'] ?? null,
            ], auth()->id());
        } catch (\Throwable $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('communication.index')
            ->with('success', 'Email sent to '.$data['to_email'].'.');
    }

    public function sendDocument(Request $request, string $type, int $id, DocumentMailService $mail): RedirectResponse
    {
        if (! in_array($type, ['quotation', 'proforma', 'invoice', 'receipt'], true)) {
            return back()->with('error', 'Unsupported document type.');
        }

        $preview = $mail->templatePreview($type, $id);
        $to = trim((string) ($preview['to'] ?? ''));

        if ($to === '' || ! filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return back()->with('error', 'This document has no valid client email. Add an email on the customer record, or use Communication → Compose.');
        }

        if (trim((string) ($preview['subject'] ?? '')) === '' || trim((string) ($preview['body'] ?? '')) === '') {
            return back()->with('error', 'Email template is empty. Update it under Communication → Templates.');
        }

        try {
            if (function_exists('set_time_limit')) {
                @set_time_limit(120);
            }

            $mail->send([
                'to' => $to,
                'cc' => null,
                'subject' => $preview['subject'],
                'body' => $preview['body'],
                'document_type' => $type,
                'document_id' => $id,
            ], auth()->id());
        } catch (\Throwable $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Email sent to '.$to.'.');
    }
}
