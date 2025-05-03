<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\VacationRequestMail;
use App\Mail\VacationApprovedMail;
use App\Mail\VacationRejectedMail;
use App\Models\User;
use App\Models\VacationRequest;
use Carbon\Carbon;

class TestVacationEmailsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:test-vacation {type=all : Der Typ der E-Mail (request/approved/rejected/all)} {email? : Die E-Mail-Adresse für den Test}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sendet Test-E-Mails für den Urlaubsantragsprozess';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->argument('type');
        $email = $this->argument('email') ?: env('MAIL_TEST_RECIPIENT', 'test@example.com');

        $this->info("Teste E-Mail-Versand für Urlaubsanträge an: {$email}");

        try {
            // Zeige Mail-Konfiguration
            $this->info("Mail-Konfiguration:");
            $this->info("Driver: " . config('mail.default'));
            $this->info("Host: " . config('mail.mailers.smtp.host'));
            $this->info("Port: " . config('mail.mailers.smtp.port'));

            // Testdaten erstellen
            $employee = new User();
            $employee->id = 1;
            $employee->full_name = 'Max Mustermann';

            $approver = new User();
            $approver->id = 2;
            $approver->full_name = 'Chef Mustermann';

            $vacationRequest = new VacationRequest();
            $vacationRequest->id = 999;
            $vacationRequest->user_id = 1;
            $vacationRequest->start_date = Carbon::now()->addDays(5);
            $vacationRequest->end_date = Carbon::now()->addDays(10);
            $vacationRequest->days = 5;
            $vacationRequest->notes = 'Testnotiz für den Urlaubsantrag';

            $overlappingRequests = collect([
                [
                    'employee_name' => 'Erika Musterfrau',
                    'start_date' => Carbon::now()->addDays(7)->format('d.m.Y'),
                    'end_date' => Carbon::now()->addDays(12)->format('d.m.Y')
                ]
            ]);

            // E-Mails testen basierend auf Typ
            if ($type === 'all' || $type === 'request') {
                $this->info("Teste Urlaubsantrag-E-Mail...");
                Mail::to($email)->send(new VacationRequestMail(
                    $vacationRequest,
                    $employee,
                    $overlappingRequests
                ));
                $this->info("Urlaubsantrag-E-Mail gesendet!");
            }

            if ($type === 'all' || $type === 'approved') {
                $this->info("Teste Genehmigungs-E-Mail...");
                Mail::to($email)->send(new VacationApprovedMail(
                    $vacationRequest,
                    $employee,
                    $approver
                ));
                $this->info("Genehmigungs-E-Mail gesendet!");
            }

            if ($type === 'all' || $type === 'rejected') {
                $this->info("Teste Ablehnungs-E-Mail...");
                Mail::to($email)->send(new VacationRejectedMail(
                    $vacationRequest,
                    $employee,
                    'Zu viele Mitarbeiter sind bereits im Urlaub in diesem Zeitraum.',
                    $approver
                ));
                $this->info("Ablehnungs-E-Mail gesendet!");
            }

            $this->info("Test abgeschlossen! Bitte überprüfen Sie Ihren Posteingang ({$email}).");

        } catch (\Exception $e) {
            $this->error("Fehler beim Senden der E-Mail(s): " . $e->getMessage());
            Log::error("Fehler beim Testen der Urlaubs-E-Mails", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
