<?php

declare(strict_types=1);

namespace Koersa\Portfolio\Domain;

interface PortfolioRepository
{
    /**
     * Reconstitutes the portfolio from its event stream. A portfolio that has
     * never recorded anything comes back empty (version 0), never null.
     */
    public function get(PortfolioId $id): Portfolio;

    public function save(Portfolio $portfolio): void;
}
