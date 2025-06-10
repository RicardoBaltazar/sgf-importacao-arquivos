<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TransactionsProcessedMail extends Mailable
{
    use Queueable, SerializesModels;

    protected $transactionCount;

    /**
     * Create a new message instance.
     */
    public function __construct(int $transactionCount)
    {
        $this->transactionCount = $transactionCount;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Processamento de Transações Concluído',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.transactions-processed',
            with: [
                'transactionCount' => $this->transactionCount,
            ],
        );
    }
}
