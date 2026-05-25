<?php

declare(strict_types=1);

namespace Koersa\Portfolio\UI\Form;

use DateTimeImmutable;
use Koersa\Portfolio\Domain\ValueObject\Side;

final class TransactionFormData
{
    public string $asset = '';
    public Side $side = Side::Buy;
    public string $quantity = '';
    public string $price = '';
    public string $fee = '0';
    public ?DateTimeImmutable $occurredAt = null;
}
