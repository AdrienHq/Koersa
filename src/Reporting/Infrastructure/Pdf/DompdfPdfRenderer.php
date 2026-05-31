<?php

declare(strict_types=1);

namespace Koersa\Reporting\Infrastructure\Pdf;

use Dompdf\Dompdf;
use Dompdf\Options;
use Koersa\Reporting\Application\PdfRenderer;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Throwable;

#[AsAlias(PdfRenderer::class)]
final class DompdfPdfRenderer implements PdfRenderer
{
    public function render(string $html): string
    {
        $options = new Options();
        $options->setDefaultFont('DejaVu Sans');
        // No remote loads — accountant reports stream from the user's own
        // server, and Dompdf's remote fetcher is a foot-gun.
        $options->setIsRemoteEnabled(false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');

        try {
            $dompdf->render();

            return $dompdf->output();
        } catch (Throwable $e) {
            throw new RuntimeException('PDF rendering failed.', 0, $e);
        }
    }
}
