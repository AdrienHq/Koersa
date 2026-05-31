<?php

declare(strict_types=1);

namespace Koersa\Shared\Domain;

use InvalidArgumentException;
use Stringable;

// Decimal amount paired with a currency. Arithmetic goes through bcmath at
// scale 18 to keep tax math out of float territory. Cross-currency ops throw;
// EUR conversion is an explicit hop through FxRateProvider.
final readonly class Money implements Stringable
{
    private const int SCALE = 18;
    private const string AMOUNT_PATTERN = '/^-?(0|[1-9]\d*)(\.\d+)?$/';
    private const string CURRENCY_PATTERN = '/^[A-Z]{3,8}$/';

    /**
     * @param numeric-string $amount pre-validated by of()/bcmath
     */
    private function __construct(public string $amount, public string $currency)
    {
    }

    public static function of(string $amount, string $currency): self
    {
        $amount = self::canonical($amount);
        if (1 !== preg_match(self::AMOUNT_PATTERN, $amount)) {
            throw new InvalidArgumentException(\sprintf('"%s" is not a valid decimal amount.', $amount));
        }

        $currency = strtoupper($currency);
        if (1 !== preg_match(self::CURRENCY_PATTERN, $currency)) {
            throw new InvalidArgumentException(\sprintf('"%s" is not a valid currency code.', $currency));
        }

        \assert(is_numeric($amount));

        return new self($amount, $currency);
    }

    public static function zero(string $currency): self
    {
        return self::of('0', $currency);
    }

    public function add(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self(self::canonicalNumeric(bcadd($this->amount, $other->amount, self::SCALE)), $this->currency);
    }

    public function subtract(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self(self::canonicalNumeric(bcsub($this->amount, $other->amount, self::SCALE)), $this->currency);
    }

    public function multiply(string $factor): self
    {
        if (1 !== preg_match(self::AMOUNT_PATTERN, $factor)) {
            throw new InvalidArgumentException(\sprintf('"%s" is not a valid decimal factor.', $factor));
        }
        \assert(is_numeric($factor));

        return new self(self::canonicalNumeric(bcmul($this->amount, $factor, self::SCALE)), $this->currency);
    }

    public function convertedTo(string $currency, string $rate): self
    {
        if (1 !== preg_match(self::AMOUNT_PATTERN, $rate)) {
            throw new InvalidArgumentException(\sprintf('"%s" is not a valid FX rate.', $rate));
        }
        \assert(is_numeric($rate));

        return self::of(bcmul($this->amount, $rate, self::SCALE), $currency);
    }

    public function isZero(): bool
    {
        return 0 === bccomp($this->amount, '0', self::SCALE);
    }

    public function isPositive(): bool
    {
        return 1 === bccomp($this->amount, '0', self::SCALE);
    }

    public function isNegative(): bool
    {
        return -1 === bccomp($this->amount, '0', self::SCALE);
    }

    public function equals(self $other): bool
    {
        return $this->currency === $other->currency
            && 0 === bccomp($this->amount, $other->amount, self::SCALE);
    }

    public function __toString(): string
    {
        return $this->amount.' '.$this->currency;
    }

    private function assertSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException(\sprintf('Cannot operate on %s and %s; convert through FxRateProvider first.', $this->currency, $other->currency));
        }
    }

    // Strip trailing zeros so equal amounts compare byte-equal. "1.500" → "1.5",
    // "10.000" → "10", "-0" → "0".
    private static function canonical(string $amount): string
    {
        if (str_contains($amount, '.')) {
            $amount = rtrim(rtrim($amount, '0'), '.');
        }
        if ('-0' === $amount || '' === $amount) {
            $amount = '0';
        }

        return $amount;
    }

    /**
     * @param numeric-string $amount always numeric (output of bc* or pre-validated)
     *
     * @return numeric-string
     */
    private static function canonicalNumeric(string $amount): string
    {
        $result = self::canonical($amount);
        \assert(is_numeric($result));

        return $result;
    }
}
