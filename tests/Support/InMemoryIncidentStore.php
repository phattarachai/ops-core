<?php

declare(strict_types=1);

namespace Phattarachai\OpsCore\Tests\Support;

use Phattarachai\OpsCore\Alerts\Contracts\IncidentStore;
use Phattarachai\OpsCore\Alerts\Incident;

final class InMemoryIncidentStore implements IncidentStore
{
    /** @var array<int, Incident> */
    public array $incidents = [];

    public function openFor(int|string $ruleId): array
    {
        return array_values(array_filter($this->incidents, static fn (Incident $incident): bool => $incident->ruleId === $ruleId && $incident->isOpen()));
    }

    public function save(Incident $incident): Incident
    {
        $saved = $incident->id === null ? $incident->withId(count($this->incidents) + 1) : $incident;
        $this->incidents[(int) $saved->id] = $saved;

        return $saved;
    }
}
