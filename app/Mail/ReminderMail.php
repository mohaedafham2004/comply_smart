<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * ReminderMail
 *
 * A generic, reusable reminder email for renewals, tasks, and document expiries.
 *
 * Usage:
 *   Mail::to($user->email)->send(new ReminderMail(
 *       subject:    'Reminder: Your license expires soon',
 *       heading:    'License Renewal Reminder',
 *       bodyLines:  ['Hi John,', 'Your license expires on **31 Dec 2026**.'],
 *       actionUrl:  'https://yourapp.com/renewals',
 *       actionText: 'View Renewals',
 *   ));
 *
 * Driver config (in .env):
 *   MAIL_MAILER=log          ← local dev (writes to storage/logs/laravel.log)
 *   MAIL_MAILER=smtp         ← production (fill MAIL_HOST, MAIL_PORT, etc.)
 */
class ReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $subject,
        public readonly string $heading,
        /** @var string[] */
        public readonly array  $bodyLines,
        public readonly string $actionUrl,
        public readonly string $actionText = 'Take Action',
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subject);
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.reminder',
            with: [
                'heading'    => $this->heading,
                'bodyLines'  => $this->bodyLines,
                'actionUrl'  => $this->actionUrl,
                'actionText' => $this->actionText,
                'appName'    => config('app.name', 'ComplySmart'),
            ],
        );
    }
}
