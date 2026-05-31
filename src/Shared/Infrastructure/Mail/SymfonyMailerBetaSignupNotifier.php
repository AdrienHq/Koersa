<?php

declare(strict_types=1);

namespace Koersa\Shared\Infrastructure\Mail;

use Koersa\Shared\Application\BetaSignupNotifier;
use Koersa\Shared\Domain\Signup;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsAlias(BetaSignupNotifier::class)]
final class SymfonyMailerBetaSignupNotifier implements BetaSignupNotifier
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function notify(Signup $signup): void
    {
        $email = (new Email())
            ->from(new Address('beta@koersa.be', 'Koersa'))
            ->to($signup->email)
            ->subject($this->translator->trans('email.beta_signup.subject', [], 'messages', $signup->locale))
            ->text($this->translator->trans('email.beta_signup.body', [], 'messages', $signup->locale));

        $this->mailer->send($email);
    }
}
