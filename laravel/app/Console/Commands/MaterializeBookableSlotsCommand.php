<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Application\Scheduling\Command\MaterializeBookableSlotsForAllDoctors;
use Illuminate\Console\Command;

final class MaterializeBookableSlotsCommand extends Command
{
    protected $signature = 'hexagon:materialize-bookable-slots {--days= : Override horizon days}';

    protected $description = 'Materialize bookable slots for all doctors (rolling horizon).';

    public function handle(MaterializeBookableSlotsForAllDoctors $materialize): int
    {
        $daysOption  = $this->option('days');
        $horizonDays = is_string($daysOption) && $daysOption !== ''
            ? (int) $daysOption
            : null;

        $results = $materialize->execute($horizonDays);

        foreach ($results as $row) {
            $this->line(sprintf(
                'practitioner %d: deleted %d, inserted %d (%d generated, horizon %d days)',
                $row['practitioner_id'],
                $row['deleted'],
                $row['inserted'],
                $row['generated'],
                $row['horizon_days'],
            ));
        }

        $this->info(sprintf('Materialized slots for %d doctor(s).', count($results)));

        return self::SUCCESS;
    }
}
