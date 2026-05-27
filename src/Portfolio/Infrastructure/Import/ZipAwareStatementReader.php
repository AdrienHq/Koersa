<?php

declare(strict_types=1);

namespace Koersa\Portfolio\Infrastructure\Import;

use Koersa\Portfolio\Application\StatementReader;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use ZipArchive;

/**
 * Returns the CSV text from an uploaded file. A plain CSV is read as-is; a zip
 * is opened and the CSV inside is extracted (preferring a "trades" file when an
 * archive holds several), capped so a malicious archive cannot exhaust memory.
 */
#[AsAlias(StatementReader::class)]
final class ZipAwareStatementReader implements StatementReader
{
    private const int MAX_BYTES = 50 * 1024 * 1024;

    public function read(string $path): string
    {
        $zip = new ZipArchive();

        if (true !== $zip->open($path)) {
            return (string) file_get_contents($path);
        }

        try {
            $entry = $this->csvEntry($zip);

            $stat = $zip->statName($entry);
            if (\is_array($stat) && $stat['size'] > self::MAX_BYTES) {
                throw new RuntimeException('The archived CSV is too large.');
            }

            $contents = $zip->getFromName($entry);
            if (false === $contents) {
                throw new RuntimeException('Could not read the CSV inside the archive.');
            }

            return $contents;
        } finally {
            $zip->close();
        }
    }

    private function csvEntry(ZipArchive $zip): string
    {
        $csvEntries = [];
        for ($i = 0; $i < $zip->numFiles; ++$i) {
            $name = $zip->getNameIndex($i);
            if (false !== $name && str_ends_with(strtolower($name), '.csv')) {
                $csvEntries[] = $name;
            }
        }

        if ([] === $csvEntries) {
            throw new RuntimeException('No CSV file was found in the archive.');
        }

        foreach ($csvEntries as $name) {
            if (str_contains(strtolower($name), 'trade')) {
                return $name;
            }
        }

        return $csvEntries[0];
    }
}
