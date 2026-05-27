<?php

declare(strict_types=1);

namespace Koersa\Portfolio\Domain;

interface PortfolioRepository
{
    /** An unknown portfolio comes back empty, never null. */
    public function get(PortfolioId $id): Portfolio;

    public function save(Portfolio $portfolio): void;
}
