<?php

declare(strict_types=1);

namespace Phattarachai\OpsCore\Alerts\Contracts;

use Phattarachai\OpsCore\Alerts\IncidentNotice;

interface IncidentChannel
{
    public function name(): string;

    public function send(IncidentNotice $notice): void;
}
