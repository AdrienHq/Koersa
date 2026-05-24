<?php

declare(strict_types=1);

namespace Koersa\IAM\Domain;

use DateTimeImmutable;
use InvalidArgumentException;
use Koersa\Shared\Domain\Uuid;

final class Organization
{
    private function __construct(
        private readonly Uuid $id,
        private string $name,
        private string $slug,
        private readonly DateTimeImmutable $createdAt,
    ) {
    }

    public static function create(Uuid $id, string $name, DateTimeImmutable $createdAt): self
    {
        return new self($id, $name, self::slugify($name), $createdAt);
    }

    /**
     * Rebuild an organization from stored state. Used by the persistence
     * mapper only.
     */
    public static function reconstitute(Uuid $id, string $name, string $slug, DateTimeImmutable $createdAt): self
    {
        return new self($id, $name, $slug, $createdAt);
    }

    public function rename(string $name): void
    {
        $this->name = $name;
        $this->slug = self::slugify($name);
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function slug(): string
    {
        return $this->slug;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    private static function slugify(string $name): string
    {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
        $slug = trim($slug, '-');

        if ('' === $slug) {
            throw new InvalidArgumentException(\sprintf('Organization name "%s" produces an empty slug.', $name));
        }

        return $slug;
    }
}
