<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TaskNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $mailSubject,
        public string $mailBody,
        public string $eventLabel = '',
        public ?string $recipientName = null,
        public ?string $taskLink = null,
        public array $extra = [],
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[CLYX] ' . $this->mailSubject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.task-notification',
            with: [
                'mailSubject'    => $this->mailSubject,
                'mailBody'       => $this->mailBody,
                'eventLabel'     => $this->eventLabel,
                'recipientName'  => $this->recipientName,
                'taskLink'       => $this->taskLink,
            ],
        );
    }
}
