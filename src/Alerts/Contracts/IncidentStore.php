<?php

declare(strict_types=1);

namespace Phattarachai\OpsCore\Alerts\Contracts;

use Phattarachai\OpsCore\Alerts\Incident;

interface IncidentStore
{
    /** @return list<Incident> */
    public function openFor(int|string $ruleId): array;

    public function save(Incident $incident): Incident;
}
