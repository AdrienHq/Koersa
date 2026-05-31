<?php

declare(strict_types=1);

namespace Koersa\Tests\Shared\Application;

use Koersa\Shared\Application\BetaSignupNotifier;
use Koersa\Shared\Application\SignUpForBeta;
use Koersa\Shared\Application\SignUpForBetaHandler;
use Koersa\Shared\Domain\Signup;
use Koersa\Shared\Domain\SignupRepository;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RuntimeException;

final class SignUpForBetaHandlerTest extends TestCase
{
    public function testSavesANewSignupAndNotifiesIt(): void
    {
        $signups = $this->createMock(SignupRepository::class);
        $signups->method('existsByEmail')->willReturn(false);
        $signups->expects(self::once())->method('save')->with(self::callback(
            static fn (Signup $signup): bool => 'jane@example.be' === $signup->email && 'fr' === $signup->locale,
        ));

        $notifier = $this->createMock(BetaSignupNotifier::class);
        $notifier->expects(self::once())->method('notify');

        (new SignUpForBetaHandler($signups, $notifier, new NullLogger()))(new SignUpForBeta('  Jane@Example.BE ', 'fr'));
    }

    public function testIgnoresAnEmailAlreadyOnTheListWithoutNotifying(): void
    {
        $signups = $this->createMock(SignupRepository::class);
        $signups->method('existsByEmail')->willReturn(true);
        $signups->expects(self::never())->method('save');

        $notifier = $this->createMock(BetaSignupNotifier::class);
        $notifier->expects(self::never())->method('notify');

        (new SignUpForBetaHandler($signups, $notifier, new NullLogger()))(new SignUpForBeta('jane@example.be', 'nl'));
    }

    public function testSignupStillPersistsIfTheEmailFailsToSend(): void
    {
        $signups = $this->createMock(SignupRepository::class);
        $signups->method('existsByEmail')->willReturn(false);
        $signups->expects(self::once())->method('save');

        $notifier = $this->createMock(BetaSignupNotifier::class);
        $notifier->expects(self::once())->method('notify')->willThrowException(new RuntimeException('SMTP down'));

        (new SignUpForBetaHandler($signups, $notifier, new NullLogger()))(new SignUpForBeta('jane@example.be', 'fr'));
    }
}
