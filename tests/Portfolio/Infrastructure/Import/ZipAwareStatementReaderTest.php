<?php

declare(strict_types=1);

namespace Koersa\Tests\Portfolio\Infrastructure\Import;

use Koersa\Portfolio\Infrastructure\Import\ZipAwareStatementReader;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class ZipAwareStatementReaderTest extends TestCase
{
    public function testReadsAPlainCsv(): void
    {
        $path = sys_get_temp_dir().'/koersa_'.uniqid().'.csv';
        file_put_contents($path, "a,b\n1,2\n");

        self::assertSame("a,b\n1,2\n", (new ZipAwareStatementReader())->read($path));

        unlink($path);
    }

    public function testExtractsTheCsvFromAZip(): void
    {
        $path = sys_get_temp_dir().'/koersa_'.uniqid().'.zip';
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('trades.csv', "a,b\n1,2\n");
        $zip->close();

        self::assertSame("a,b\n1,2\n", (new ZipAwareStatementReader())->read($path));

        unlink($path);
    }

    public function testPrefersATradesCsvWhenSeveralArePresent(): void
    {
        $path = sys_get_temp_dir().'/koersa_'.uniqid().'.zip';
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('ledgers.csv', 'ledgers-content');
        $zip->addFromString('trades.csv', 'trades-content');
        $zip->close();

        self::assertSame('trades-content', (new ZipAwareStatementReader())->read($path));

        unlink($path);
    }
}
