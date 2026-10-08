<?php

declare(strict_types=1);

arch('core stays framework-free')
    ->expect('Phattarachai\OpsCore')
    ->not->toUse('Illuminate');

arch('every source file declares strict types')
    ->expect('Phattarachai\OpsCore')
    ->toUseStrictTypes();
