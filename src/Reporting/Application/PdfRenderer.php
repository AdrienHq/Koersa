<?php

declare(strict_types=1);

namespace Koersa\Reporting\Application;

interface PdfRenderer
{
    // Renders HTML into a PDF binary. The HTML must be self-contained
    // (inline CSS, absolute paths or data URLs for images) — Dompdf and most
    // alternatives don't follow relative asset references reliably.
    public function render(string $html): string;
}
