<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\VacationCarryOverService;
use Carbon\Carbon;

class ProcessYearlyVacationCarryOver extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'vacation:carry-over 
                            {--from-year= : Das Jahr von dem übertragen werden soll}
                            {--to-year= : Das Jahr in das übertragen werden soll}
                            {--dry-run : Führt einen Test-Lauf durch ohne Änderungen}';

    /**
     * The console command description.
     */
    protected $description = 'Überträgt nicht genutzte Urlaubstage vom Vorjahr ins neue Jahr';

    /**
     * Execute the console command.
     */
    public function handle(VacationCarryOverService $carryOverService)
    {
        $fromYear = $this->option('from-year') ?? Carbon::now()->subYear()->year;
        $toYear = $this->option('to-year') ?? Carbon::now()->year;
        $dryRun = $this->option('dry-run');

        $this->info("Starte Urlaubsübertragung von {$fromYear} nach {$toYear}");
        
        if ($dryRun) {
            $this->warn("DRY RUN - Keine Änderungen werden gespeichert");
        }

        try {
            if (!$dryRun) {
                $results = $carryOverService->processYearlyCarryOver($fromYear, $toYear);
            } else {
                // Für Dry-Run: Zeige nur was passieren würde
                $this->info("Dry-Run Modus - zeige was passieren würde:");
                $results = ['processed' => 0, 'errors' => 0, 'total_carried_over' => 0, 'details' => []];
            }

            $this->info("Übertragung abgeschlossen:");
            $this->info("- Verarbeitete Benutzer: {$results['processed']}");
            $this->info("- Fehler: {$results['errors']}");
            $this->info("- Gesamt übertragene Tage: {$results['total_carried_over']}");

            if ($this->option('verbose')) {
                $this->table(
                    ['Benutzer', 'Übertragene Tage', 'Verfallsdatum', 'Status'],
                    collect($results['details'])->map(function ($detail) {
                        return [
                            $detail['user_name'] ?? 'Unbekannt',
                            $detail['carried_over_days'] ?? 0,
                            $detail['expiry_date'] ?? 'N/A',
                            $detail['success'] ? 'Erfolg' : 'Fehler: ' . ($detail['error'] ?? 'Unbekannt')
                        ];
                    })
                );
            }

        } catch (\Exception $e) {
            $this->error("Fehler bei der Urlaubsübertragung: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
