<?php

declare(strict_types=1);

namespace Koersa\Portfolio\Infrastructure\Import;

use DateTimeImmutable;
use DateTimeZone;
use Koersa\Portfolio\Application\ParsedTrade;
use Koersa\Portfolio\Application\StatementParser;
use Koersa\Portfolio\Domain\ValueObject\Side;
use RuntimeException;

// Kraken "Trades" export: crypto buy/sell rows only (fiat pairs skipped), UTC times.
// Prices/fees stay in the pair's quote currency; EUR conversion is the tax engine's job.
final class KrakenCsvParser implements StatementParser
{
    private const array COLUMNS = ['txid', 'pair', 'subclass', 'time', 'type', 'price', 'fee', 'vol'];

    public function exchange(): string
    {
        return 'kraken';
    }

    public function parse(string $contents): array
    {
        $handle = fopen('php://temp', 'r+');
        if (false === $handle) {
            throw new RuntimeException('Unable to read the statement.');
        }

        fwrite($handle, $contents);
        rewind($handle);

        $header = fgetcsv($handle, escape: '');
        if (!\is_array($header)) {
            throw new RuntimeException('The statement is empty.');
        }

        $column = $this->columnIndexes($header);

        $trades = [];
        while (false !== ($row = fgetcsv($handle, escape: ''))) {
            if ('crypto' !== ($row[$column['subclass']] ?? null)) {
                continue;
            }

            $pair = (string) ($row[$column['pair']] ?? '');
            $type = strtolower((string) ($row[$column['type']] ?? ''));
            if (!str_contains($pair, '/') || ('buy' !== $type && 'sell' !== $type)) {
                continue;
            }

            [$base, $quote] = explode('/', $pair, 2);
            $quoteCurrency = strtoupper($quote);

            $trades[] = new ParsedTrade(
                (string) ($row[$column['txid']] ?? ''),
                strtoupper($base),
                Side::from($type),
                (string) ($row[$column['vol']] ?? ''),
                (string) ($row[$column['price']] ?? ''),
                (string) ($row[$column['fee']] ?? ''),
                new DateTimeImmutable((string) ($row[$column['time']] ?? ''), new DateTimeZone('UTC')),
                $quoteCurrency,
                $quoteCurrency,
            );
        }

        fclose($handle);

        return $trades;
    }

    /**
     * @param array<int, string|null> $header
     *
     * @return array<string, int>
     */
    private function columnIndexes(array $header): array
    {
        $column = [];
        foreach (self::COLUMNS as $name) {
            $index = array_search($name, $header, true);
            if (false === $index) {
                throw new RuntimeException(\sprintf('The Kraken export is missing the "%s" column.', $name));
            }
            $column[$name] = $index;
        }

        return $column;
    }
}
