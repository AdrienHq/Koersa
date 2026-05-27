<?php

declare(strict_types=1);

namespace Koersa\Tests\Support;

use Generator;
use Koersa\Portfolio\Domain\Portfolio;
use Koersa\Portfolio\Domain\PortfolioId;
use Koersa\Shared\Domain\Uuid;

// An empty event stream gives a fresh portfolio at version 0.
final class Portfolios
{
    public static function empty(Uuid $organizationId): Portfolio
    {
        $noEvents = (static function (): Generator {
            yield from [];
        })();

        return Portfolio::reconstituteFromEvents(PortfolioId::forOrganization($organizationId), $noEvents);
    }
}
