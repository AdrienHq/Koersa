<?php

declare(strict_types=1);

namespace Koersa\Shared\Market;

use DateTimeImmutable;

interface FxRateProvider
{
    /**
     * Multiplier such that an amount in `$from` times the rate yields the same
     * amount expressed in `$to` on `$date`. Weekends and bank holidays fall
     * back to the most recent prior publication.
     *
     * @return numeric-string
     */
    public function rateOn(DateTimeImmutable $date, string $from, string $to): string;
}
