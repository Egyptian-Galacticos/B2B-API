<?php

namespace App\Mail;

use App\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContractUpdatedByBuyerMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Contract $contract,
        public string $updateType
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            replyTo: [
                new Address($this->contract->buyer->email, $this->contract->buyer->name),
            ],
            subject: 'Contract Updated by Buyer - '.$this->contract->contract_number.' (from '.$this->contract->buyer->name.')',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.contract-updated-by-buyer',
            with: [
                'contract'   => $this->contract,
                'updateType' => $this->updateType,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
