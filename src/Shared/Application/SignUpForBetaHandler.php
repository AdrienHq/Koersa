<?php

declare(strict_types=1);

namespace Koersa\Shared\Application;

use DateTimeImmutable;
use Koersa\Shared\Domain\Signup;
use Koersa\Shared\Domain\SignupRepository;
use Koersa\Shared\Domain\Uuid;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class SignUpForBetaHandler
{
    public function __construct(private readonly SignupRepository $signups)
    {
    }

    public function __invoke(SignUpForBeta $command): void
    {
        $email = strtolower(trim($command->email));

        // Idempotent: signing up twice with the same address is a no-op, not an error.
        if ($this->signups->existsByEmail($email)) {
            return;
        }

        $this->signups->save(Signup::register(Uuid::generate(), $email, $command->locale, new DateTimeImmutable()));
    }
}
