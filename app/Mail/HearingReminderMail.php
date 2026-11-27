<?php

namespace App\Mail;

use App\Models\Hearing;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class HearingReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Hearing $hearing)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Hearing reminder: '.$this->hearing->legalCase->case_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'mail.hearing-reminder',
        );
    }
}
