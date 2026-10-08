<?php

declare(strict_types=1);

namespace Phattarachai\OpsCore;

/**
 * Package marker. Gives PHPStan and the architecture tests a file to analyse until the first
 * real module (rollups, histograms, alert rules, incidents) lands.
 */
final class OpsCore
{
    public const string VERSION = '0.1.0-dev';
}
