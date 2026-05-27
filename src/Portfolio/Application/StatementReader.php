<?php

declare(strict_types=1);

namespace Koersa\Portfolio\Application;

// Reads CSV text from an uploaded statement (plain CSV or a zip holding one).
interface StatementReader
{
    public function read(string $path): string;
}
