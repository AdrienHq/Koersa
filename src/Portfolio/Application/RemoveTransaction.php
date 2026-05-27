<?php

declare(strict_types=1);

namespace Koersa\Portfolio\Application;

use Koersa\Shared\Domain\Uuid;

final readonly class RemoveTransaction
{
    public function __construct(
        public Uuid $organizationId,
        public Uuid $transactionId,
    ) {
    }
}
