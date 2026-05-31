<?php

declare(strict_types=1);

namespace Koersa\Shared\Application;

use Koersa\Shared\Domain\Signup;

interface BetaSignupNotifier
{
    public function notify(Signup $signup): void;
}
