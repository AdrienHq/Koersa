<?php

declare(strict_types=1);

namespace Koersa\Portfolio\Application;

/**
 * Reads the CSV text from an uploaded statement, which may be a plain CSV or a
 * zip archive containing one (exchanges such as Kraken export a zip).
 */
interface StatementReader
{
    public function read(string $path): string;
}
