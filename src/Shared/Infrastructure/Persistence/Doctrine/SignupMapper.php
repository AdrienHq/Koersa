<?php

declare(strict_types=1);

namespace Koersa\Shared\Infrastructure\Persistence\Doctrine;

use Koersa\Shared\Domain\Signup;
use Koersa\Shared\Domain\Uuid;
use Koersa\Shared\Infrastructure\Persistence\Doctrine\Entity\SignupEntity;

final class SignupMapper
{
    public function toDomain(SignupEntity $entity): Signup
    {
        return Signup::reconstitute(
            Uuid::fromString($entity->id),
            $entity->email,
            $entity->locale,
            $entity->signedUpAt,
        );
    }

    public function toEntity(Signup $signup, ?SignupEntity $entity = null): SignupEntity
    {
        $entity ??= new SignupEntity();
        $entity->id = (string) $signup->id;
        $entity->email = $signup->email;
        $entity->locale = $signup->locale;
        $entity->signedUpAt = $signup->signedUpAt;

        return $entity;
    }
}
