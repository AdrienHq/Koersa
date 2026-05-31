<?php

declare(strict_types=1);

namespace Koersa\Shared\Application;

use DateTimeImmutable;
use Koersa\Shared\Domain\Signup;
use Koersa\Shared\Domain\SignupRepository;
use Koersa\Shared\Domain\Uuid;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Throwable;

#[AsMessageHandler]
final class SignUpForBetaHandler
{
    public function __construct(
        private readonly SignupRepository $signups,
        private readonly BetaSignupNotifier $notifier,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(SignUpForBeta $command): void
    {
        $email = strtolower(trim($command->email));

        // Idempotent: signing up twice with the same address is a no-op, not an error.
        if ($this->signups->existsByEmail($email)) {
            return;
        }

        $signup = Signup::register(Uuid::generate(), $email, $command->locale, new DateTimeImmutable());
        $this->signups->save($signup);

        try {
            $this->notifier->notify($signup);
        } catch (Throwable $e) {
            // Don't fail the signup if the email can't be sent.
            $this->logger->error('Beta signup confirmation email failed', ['email' => $email, 'error' => $e->getMessage()]);
        }
    }
}
