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

    /**
     * Create a new message instance.
     */
    public function __construct(VacationRequest $vacationRequest, User $employee, Collection $overlappingRequests = null, User $substitute = null)
    {
        $this->vacationRequest = $vacationRequest;
        $this->employee = $employee;
        $this->overlappingRequests = $overlappingRequests ?? collect();
        $this->substitute = $substitute;
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
        // Verwende eine einfache Textansicht statt einer Blade-Datei
        return new Content(
            view: 'emails.vacation.vacation-request',
        // Alternativ: Verwende eine einfache Textansicht
        // text: 'emails.vacation-request-text'
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
     * Generate HTML content for the email.
     */
    private function generateHtmlContent()
    {
        $startDate = $this->vacationRequest->start_date->format('d.m.Y');
        $endDate = $this->vacationRequest->end_date->format('d.m.Y');
        $employeeName = $this->employee->full_name;
        $days = $this->vacationRequest->days;
        $notes = $this->vacationRequest->notes ?? '';

        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <title>Neuer Urlaubsantrag</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .header { background-color: #4a6fdc; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; border: 1px solid #ddd; }
                .footer { margin-top: 20px; font-size: 12px; color: #777; text-align: center; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>Neuer Urlaubsantrag</h1>
            </div>

            <div class='content'>
                <p>Hallo,</p>

                <p>ein neuer Urlaubsantrag wurde eingereicht und erfordert Ihre Genehmigung.</p>

                <h2>Details zum Antrag:</h2>

                <p><strong>Mitarbeiter:</strong> {$employeeName}</p>
                <p><strong>Zeitraum:</strong> Von {$startDate} bis {$endDate}</p>
                <p><strong>Anzahl der Tage:</strong> {$days} Arbeitstage</p>
        ";

        if (!empty($notes)) {
            $html .= "<p><strong>Anmerkungen:</strong> {$notes}</p>";
        }

        if ($this->substitute) {
            $html .= "<p><strong>Vertretung:</strong> {$this->substitute->full_name}</p>";
        }

        if ($this->overlappingRequests->isNotEmpty()) {
            $html .= "
                <h3>Überlappende Urlaubsanträge:</h3>
                <ul>
            ";

            foreach ($this->overlappingRequests as $request) {
                $html .= "<li>{$request['employee_name']}: Von {$request['start_date']} bis {$request['end_date']}</li>";
            }

            $html .= "</ul>";
        }

        $html .= "
                <p>Bitte loggen Sie sich in das System ein, um den Antrag zu genehmigen oder abzulehnen.</p>
            </div>

            <div class='footer'>
                <p>Dies ist eine automatisch generierte E-Mail. Bitte antworten Sie nicht auf diese Nachricht.</p>
            </div>
        </body>
        </html>
        ";

        return $html;
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
