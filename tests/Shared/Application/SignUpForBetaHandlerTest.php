<?php

declare(strict_types=1);

namespace Koersa\Tests\Shared\Application;

use Koersa\Shared\Application\SignUpForBeta;
use Koersa\Shared\Application\SignUpForBetaHandler;
use Koersa\Shared\Domain\Signup;
use Koersa\Shared\Domain\SignupRepository;
use PHPUnit\Framework\TestCase;

final class SignUpForBetaHandlerTest extends TestCase
{
    public function testSavesANewSignupWithANormalizedEmail(): void
    {
        $signups = $this->createMock(SignupRepository::class);
        $signups->method('existsByEmail')->willReturn(false);
        $signups->expects(self::once())->method('save')->with(self::callback(
            static fn (Signup $signup): bool => 'jane@example.be' === $signup->email && 'fr' === $signup->locale,
        ));

        (new SignUpForBetaHandler($signups))(new SignUpForBeta('  Jane@Example.BE ', 'fr'));
    }

    public function testIgnoresAnEmailAlreadyOnTheList(): void
    {
        $signups = $this->createMock(SignupRepository::class);
        $signups->method('existsByEmail')->willReturn(true);
        $signups->expects(self::never())->method('save');

        (new SignUpForBetaHandler($signups))(new SignUpForBeta('jane@example.be', 'nl'));
    }
}
