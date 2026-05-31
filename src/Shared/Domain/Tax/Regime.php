<?php

declare(strict_types=1);

namespace Koersa\Shared\Domain\Tax;

// Belgian tax regimes for a natural person's crypto gains. See ADR 0007.
enum Regime: string
{
    case NormalManagement = 'normal_management';
    case Speculative = 'speculative';
    case Professional = 'professional';
}
