<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Models\VacationRequest;

class VacationRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $vacationRequest;
    public $employee;
    public $approver;
    public $rejectionReason;

    /**
     * Create a new message instance.
     */
    public function __construct(VacationRequest $vacationRequest, User $employee, ?string $rejectionReason = null, ?User $approver = null)
    {
        $this->vacationRequest = $vacationRequest;
        $this->employee = $employee;
        $this->approver = $approver;
        $this->rejectionReason = $rejectionReason;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Ihr Urlaubsantrag wurde abgelehnt')
            ->view('emails.vacation.vacation-rejected');
    }
}
