<?php

declare(strict_types=1);

namespace Koersa\Portfolio\Domain\ValueObject;

enum Side: string
{
    case Buy = 'buy';
    case Sell = 'sell';
}
