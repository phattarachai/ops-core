<?php

declare(strict_types=1);

namespace Phattarachai\OpsCore\Alerts;

enum IncidentStatus: string
{
    case Open = 'open';
    case Resolved = 'resolved';
}
