<?php

declare(strict_types=1);

namespace Phattarachai\OpsCore\Alerts;

final readonly class LifecycleResult
{
    /**
     * @param  list<Incident>  $changed
     * @param  list<IncidentNotice>  $notices
     */
    public function __construct(
        public array $changed = [],
        public array $notices = [],
    ) {}
}
