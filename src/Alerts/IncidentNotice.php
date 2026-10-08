<?php

declare(strict_types=1);

namespace Phattarachai\OpsCore\Alerts;

final readonly class IncidentNotice
{
    public function __construct(
        public NoticeKind $kind,
        public Incident $incident,
        public RuleSpec $rule,
    ) {}
}
