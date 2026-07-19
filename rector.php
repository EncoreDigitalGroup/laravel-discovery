<?php

/*
 * Copyright (c) 2024. Encore Digital Group.
 * All Right Reserved.
 */

declare(strict_types=1);

use EncoreDigitalGroup\DevTools\Rector\Rector;

return Rector::configure()
    ->withPaths([
        __DIR__ . "/src",
        __DIR__ . "/tests",
    ]);