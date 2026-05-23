<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Ce qu'il fait : assemble le mail envoyé au support depuis l'app mobile
 * (Paramètres > Centre d'aide > Contacter le support).
 *
 * Pourquoi : pas de table tickets pour démarrer ; un simple mail suffit.
 */
class SupportContactMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $category,
        public readonly string $subjectLine,
        public readonly string $messageBody,
        public readonly string $senderName,
        public readonly string $senderEmail,
        public readonly int $senderId,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Support] '.$this->subjectLine,
            replyTo: [$this->senderEmail => $this->senderName],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.support-contact',
            with: [
                'category' => $this->category,
                'subjectLine' => $this->subjectLine,
                'messageBody' => $this->messageBody,
                'senderName' => $this->senderName,
                'senderEmail' => $this->senderEmail,
                'senderId' => $this->senderId,
            ],
        );
    }
}
