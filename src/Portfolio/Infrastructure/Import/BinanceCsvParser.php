<?php

declare(strict_types=1);

namespace Koersa\Portfolio\Infrastructure\Import;

use DateTimeImmutable;
use DateTimeZone;
use Koersa\Portfolio\Application\ParsedTrade;
use Koersa\Portfolio\Application\StatementParser;
use Koersa\Portfolio\Domain\ValueObject\Side;
use RuntimeException;

// Binance "Spot Trade History" export.
//
// Built against documented format (pre-2023 legacy):
//   Date(UTC),Pair,Side,Price,Executed,Amount,Fee
//
// Quirks that bite:
// - Pair is concatenated ("BTCUSDT") with no separator. We don't split it
//   ourselves — the Executed and Amount columns carry the base and quote
//   asset symbols as suffixes ("0.001BTC", "65.00USDT"), which is the
//   unambiguous source.
// - Side is uppercase ("BUY"/"SELL").
// - Fee column also carries an asset suffix ("0.05BNB") — Binance lets users
//   pay fees in BNB; we record that as the fee currency. The tax engine
//   handles non-fiat fee currencies separately (no ECB rate for BNB; that's
//   a follow-up).
// - Binance doesn't emit a row id; we synthesize one as
//   "{date}-{pair}-{side}-{executed}" so re-imports are still idempotent.
//
// **Untested against a real export** — first real file we ingest may surface
// a column-name rename or layout change (Binance reshuffled the export in
// January 2023). When that happens, tweak the COLUMNS list and the row id
// scheme accordingly; the rest of the pipeline downstream is unaffected.
final class BinanceCsvParser implements StatementParser
{
    private const array COLUMNS = ['Date(UTC)', 'Pair', 'Side', 'Price', 'Executed', 'Amount', 'Fee'];
    private const string AMOUNT_WITH_ASSET = '/^(-?[0-9]+(?:\.[0-9]+)?)([A-Z][A-Z0-9]{1,9})$/';

    public function exchange(): string
    {
        return 'binance';
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
            $side = strtolower((string) ($row[$column['Side']] ?? ''));
            if ('buy' !== $side && 'sell' !== $side) {
                continue;
            }

            $executedRaw = (string) ($row[$column['Executed']] ?? '');
            $amountRaw = (string) ($row[$column['Amount']] ?? '');
            $feeRaw = (string) ($row[$column['Fee']] ?? '');

            $executed = $this->splitAmount($executedRaw);
            $amount = $this->splitAmount($amountRaw);
            $fee = $this->splitAmount($feeRaw);
            if (null === $executed || null === $amount) {
                // Malformed Executed/Amount we can't classify safely — skip.
                continue;
            }

            $price = (string) ($row[$column['Price']] ?? '');
            $date = (string) ($row[$column['Date(UTC)']] ?? '');
            $pair = (string) ($row[$column['Pair']] ?? '');

            // Synthetic id: Binance doesn't emit a row id, but (date, pair,
            // side, quantity) is unique enough that re-imports dedup cleanly
            // via the aggregate's (source, externalId) check.
            $externalId = \sprintf('%s-%s-%s-%s', $date, $pair, $side, $executed['amount']);

            $trades[] = new ParsedTrade(
                $externalId,
                $executed['asset'],
                Side::from($side),
                $executed['amount'],
                $price,
                null !== $fee ? $fee['amount'] : '0',
                new DateTimeImmutable($date, new DateTimeZone('UTC')),
                $amount['asset'],
                null !== $fee ? $fee['asset'] : $amount['asset'],
            );
        }

        fclose($handle);

        return $trades;
    }

    /**
     * Splits "0.001BTC" -> ['amount' => '0.001', 'asset' => 'BTC']. Returns
     * null for malformed or empty input.
     *
     * @return ?array{amount: string, asset: string}
     */
    private function splitAmount(string $raw): ?array
    {
        if ('' === $raw) {
            return null;
        }
        if (1 !== preg_match(self::AMOUNT_WITH_ASSET, $raw, $matches)) {
            return null;
        }

        return ['amount' => $matches[1], 'asset' => strtoupper($matches[2])];
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
                throw new RuntimeException(\sprintf('The Binance export is missing the "%s" column.', $name));
            }
            $column[$name] = $index;
        }

        return $column;
    }
}
