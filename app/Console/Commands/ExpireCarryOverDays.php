<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\VacationCarryOverService;

class ExpireCarryOverDays extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'vacation:expire-carry-over {--dry-run : Führt einen Test-Lauf durch}';

    /**
     * The console command description.
     */
    protected $description = 'Lässt übertragene Urlaubstage verfallen, die das Verfallsdatum überschritten haben';

    /**
     * Execute the console command.
     */
    public function handle(VacationCarryOverService $carryOverService)
    {
        $dryRun = $this->option('dry-run');

        $this->info("Prüfe verfallene übertragene Urlaubstage...");
        
        if ($dryRun) {
            $this->warn("DRY RUN - Keine Änderungen werden gespeichert");
        }

        try {
            if (!$dryRun) {
                $results = $carryOverService->expireCarryOverDays();
            } else {
                $results = ['expired_balances' => 0, 'total_expired_days' => 0, 'details' => []];
                $this->info("Dry-Run Modus aktiviert");
            }

            $this->info("Verfall abgeschlossen:");
            $this->info("- Betroffene Urlaubssalden: {$results['expired_balances']}");
            $this->info("- Gesamt verfallene Tage: {$results['total_expired_days']}");

            if (!empty($results['details'])) {
                $this->table(
                    ['Benutzer', 'Verfallene Tage', 'Verfallsdatum'],
                    collect($results['details'])->map(function ($detail) {
                        return [
                            $detail['user_name'],
                            $detail['expired_days'],
                            $detail['expiry_date']
                        ];
                    })
                );
            }

        } catch (\Exception $e) {
            $this->error("Fehler beim Verfallen lassen: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
