<?php

declare(strict_types=1);

namespace Phattarachai\OpsCore\Alerts;

enum RuleType: string
{
    case Threshold = 'threshold';
    case Rate = 'rate';
    case Absence = 'absence';
    case State = 'state';
}
