<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\VacationRequest;
use App\Models\User;

class HrVacationEntryMail extends Mailable
{
    use Queueable, SerializesModels;

    public $vacationRequest;
    public $employee;
    public $hrUser;
    public $reason;
    public $allRequests;

    /**
     * Create a new message instance.
     */
    public function __construct(VacationRequest $vacationRequest, User $employee, User $hrUser, string $reason, array $allRequests = [])
    {
        $this->vacationRequest = $vacationRequest;
        $this->employee = $employee;
        $this->hrUser = $hrUser;
        $this->reason = $reason;
        $this->allRequests = $allRequests;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Urlaub wurde für Sie eingetragen',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.hr-vacation-entry',
            with: [
                'employeeName' => $this->employee->full_name,
                'hrUserName' => $this->hrUser->full_name,
                'startDate' => $this->vacationRequest->start_date->format('d.m.Y'),
                'endDate' => $this->vacationRequest->end_date->format('d.m.Y'),
                'days' => $this->vacationRequest->days,
                'dayType' => $this->getDayTypeLabel($this->vacationRequest->day_type ?? 'full_day'),
                'reason' => $this->reason,
                'notes' => $this->vacationRequest->notes,
                'allRequests' => $this->allRequests,
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

    /**
     * Get day type label in German
     */
    private function getDayTypeLabel($dayType): string
    {
        return match($dayType) {
            'morning' => 'Vormittag',
            'afternoon' => 'Nachmittag',
            'full_day' => 'Ganztägig',
            default => 'Ganztägig'
        };
    }
}
