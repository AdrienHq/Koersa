<?php

declare(strict_types=1);

namespace Koersa\Shared\UI\Controller;

use const ENT_XML1;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class SitemapController extends AbstractController
{
    #[Route('/sitemap.xml', name: 'sitemap', methods: ['GET'])]
    public function __invoke(): Response
    {
        $locations = [
            $this->generateUrl('landing', ['_locale' => 'fr'], UrlGeneratorInterface::ABSOLUTE_URL),
            $this->generateUrl('landing', ['_locale' => 'nl'], UrlGeneratorInterface::ABSOLUTE_URL),
        ];

        $body = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $body .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach ($locations as $location) {
            $body .= '<url><loc>'.htmlspecialchars($location, ENT_XML1).'</loc></url>';
        }
        $body .= '</urlset>';

        return new Response($body, Response::HTTP_OK, ['Content-Type' => 'application/xml']);
    }
}
