<?php

declare(strict_types=1);

namespace Phattarachai\OpsCore\Alerts;

enum NoticeKind: string
{
    case Opened = 'opened';
    case Escalated = 'escalated';
    case Reminder = 'reminder';
    case Resolved = 'resolved';
    case Test = 'test';
}
