<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Models\VacationRequest;

class VacationApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $vacationRequest;
    public $employee;
    public $approver;

    /**
     * Create a new message instance.
     */
    public function __construct(VacationRequest $vacationRequest, User $employee, User $approver = null)
    {
        $this->vacationRequest = $vacationRequest;
        $this->employee = $employee;
        $this->approver = $approver;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Ihr Urlaubsantrag wurde genehmigt')
            ->view('emails.vacation.vacation-approved');
    }
}
