<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\ParkingReservation;
use App\Models\User;

class ParkingReservationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $reservation;
    public $user;
    public $type; // 'created', 'cancelled'

    /**
     * Create a new message instance.
     */
    public function __construct(ParkingReservation $reservation, User $user, string $type = 'created')
    {
        $this->reservation = $reservation;
        $this->user = $user;
        $this->type = $type;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subjects = [
            'created' => 'Parkplatz-Reservierung bestätigt',
            'cancelled' => 'Parkplatz-Reservierung storniert'
        ];

        return new Envelope(
            subject: $subjects[$this->type] ?? 'Parkplatz-Reservierung',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.parking-reservation',
            with: [
                'reservation' => $this->reservation,
                'user' => $this->user,
                'type' => $this->type
            ]
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
