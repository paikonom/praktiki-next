<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WorkflowEmail extends Mailable
{
    use Queueable, SerializesModels;

    /** @param array<string, mixed> $data */
    public function __construct(public string $emailSubject, public array $data) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->emailSubject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.workflow', with: $this->data);
    }
}
