<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Models\VacationRequest;
use Illuminate\Support\Collection;

class VacationRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public $vacationRequest;
    public $employee;
    public $overlappingRequests;
    public $substitute;
    public $allRequests;

    /**
     * Create a new message instance.
     */
    public function __construct(
        VacationRequest $vacationRequest,
        User $employee,
        Collection $overlappingRequests = null,
        User $substitute = null,
                        $allRequests = null
    ) {
        $this->vacationRequest = $vacationRequest;
        $this->employee = $employee;
        $this->overlappingRequests = $overlappingRequests ?? collect();
        $this->substitute = $substitute;
        $this->allRequests = $allRequests ?? collect([$vacationRequest]);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Neuer Urlaubsantrag von ' . $this->employee->full_name,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.vacation.vacation-request',
        );
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Neuer Urlaubsantrag von ' . $this->employee->full_name)
            ->view('emails.vacation.vacation-request');
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
