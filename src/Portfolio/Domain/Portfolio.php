<?php

declare(strict_types=1);

namespace Koersa\Portfolio\Domain;

use DateTimeImmutable;
use EventSauce\EventSourcing\AggregateRoot;
use EventSauce\EventSourcing\AggregateRootBehaviour;
use InvalidArgumentException;
use Koersa\Portfolio\Domain\Event\TransactionAmended;
use Koersa\Portfolio\Domain\Event\TransactionRecorded;
use Koersa\Portfolio\Domain\Event\TransactionRemoved;
use Koersa\Portfolio\Domain\ValueObject\Side;
use Koersa\Shared\Domain\Uuid;

/**
 * @implements AggregateRoot<PortfolioId>
 */
final class Portfolio implements AggregateRoot
{
    /** @use AggregateRootBehaviour<PortfolioId> */
    use AggregateRootBehaviour;

    /** @var array<string, true> */
    private array $transactions = [];

    /** @var array<string, true> imported "source:externalId" references, for dedup */
    private array $importedReferences = [];

    public function recordTransaction(
        Uuid $transactionId,
        Uuid $organizationId,
        string $asset,
        Side $side,
        string $quantity,
        string $price,
        string $fee,
        DateTimeImmutable $occurredAt,
        string $source = 'manual',
        ?string $externalId = null,
        string $priceCurrency = 'EUR',
        string $feeCurrency = 'EUR',
    ): void {
        if (null !== $externalId && isset($this->importedReferences[$source.':'.$externalId])) {
            return;
        }

        $asset = $this->validateTrade($asset, $quantity, $price, $fee);
        $priceCurrency = $this->normalizeCurrency($priceCurrency);
        $feeCurrency = $this->normalizeCurrency($feeCurrency);

        $this->recordThat(new TransactionRecorded($transactionId, $organizationId, $asset, $side, $quantity, $price, $fee, $occurredAt, $source, $externalId, $priceCurrency, $feeCurrency));
    }

    public function amendTransaction(
        Uuid $transactionId,
        Uuid $organizationId,
        string $asset,
        Side $side,
        string $quantity,
        string $price,
        string $fee,
        DateTimeImmutable $occurredAt,
        string $priceCurrency = 'EUR',
        string $feeCurrency = 'EUR',
    ): void {
        $this->guardKnownTransaction($transactionId);
        $asset = $this->validateTrade($asset, $quantity, $price, $fee);
        $priceCurrency = $this->normalizeCurrency($priceCurrency);
        $feeCurrency = $this->normalizeCurrency($feeCurrency);

        $this->recordThat(new TransactionAmended($transactionId, $organizationId, $asset, $side, $quantity, $price, $fee, $occurredAt, $priceCurrency, $feeCurrency));
    }

    public function removeTransaction(Uuid $transactionId): void
    {
        $this->guardKnownTransaction($transactionId);

        $this->recordThat(new TransactionRemoved($transactionId));
    }

    protected function applyTransactionRecorded(TransactionRecorded $event): void
    {
        $this->transactions[$event->transactionId->value] = true;

        if (null !== $event->externalId) {
            $this->importedReferences[$event->source.':'.$event->externalId] = true;
        }
    }

    protected function applyTransactionAmended(TransactionAmended $event): void
    {
        // an amendment changes values, not which transactions exist
    }

    protected function applyTransactionRemoved(TransactionRemoved $event): void
    {
        unset($this->transactions[$event->transactionId->value]);
    }

    private function validateTrade(string $asset, string $quantity, string $price, string $fee): string
    {
        $asset = strtoupper(trim($asset));

        if (1 !== preg_match('/^[A-Z0-9]{1,12}$/', $asset)) {
            throw new InvalidArgumentException(\sprintf('"%s" is not a valid asset symbol.', $asset));
        }
        if (!is_numeric($quantity) || (float) $quantity <= 0.0) {
            throw new InvalidArgumentException('Quantity must be a positive number.');
        }
        if (!is_numeric($price) || (float) $price < 0.0) {
            throw new InvalidArgumentException('Price must be zero or a positive number.');
        }
        if (!is_numeric($fee) || (float) $fee < 0.0) {
            throw new InvalidArgumentException('Fee must be zero or a positive number.');
        }

        return $asset;
    }

    private function normalizeCurrency(string $currency): string
    {
        $currency = strtoupper(trim($currency));

        if (1 !== preg_match('/^[A-Z]{3,8}$/', $currency)) {
            throw new InvalidArgumentException(\sprintf('"%s" is not a valid currency code.', $currency));
        }

        return $currency;
    }

    private function guardKnownTransaction(Uuid $transactionId): void
    {
        if (!isset($this->transactions[$transactionId->value])) {
            throw new InvalidArgumentException(\sprintf('Transaction "%s" is not part of this portfolio.', $transactionId->value));
        }
    }
}
