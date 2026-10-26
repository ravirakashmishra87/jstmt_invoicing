<?php

namespace App\Mail;

use App\Support\DefaultEmailTemplates;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DocumentMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<string, string>  $meta
     */
    public function __construct(
        public string $subjectLine,
        public string $bodyText,
        public string $companyName,
        public ?string $attachmentBinary = null,
        public ?string $attachmentName = null,
        public string $documentType = 'general',
        public array $meta = [],
        public ?string $companyTagline = null,
        public ?string $companyContact = null,
        public ?string $footerNote = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectLine,
        );
    }

    public function content(): Content
    {
        return new Content(
            html: 'emails.document',
            text: 'emails.document-text',
            with: [
                'subjectLine' => $this->subjectLine,
                'bodyText' => $this->bodyText,
                'companyName' => $this->companyName,
                'attachmentName' => $this->attachmentName,
                'typeLabel' => DefaultEmailTemplates::typeLabel($this->documentType),
                'meta' => $this->meta,
                'companyTagline' => $this->companyTagline,
                'companyContact' => $this->companyContact,
                'footerNote' => $this->footerNote,
                'preheader' => $this->subjectLine,
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        if (! $this->attachmentBinary || ! $this->attachmentName) {
            return [];
        }

        return [
            Attachment::fromData(fn () => $this->attachmentBinary, $this->attachmentName)
                ->withMime('application/pdf'),
        ];
    }
}
