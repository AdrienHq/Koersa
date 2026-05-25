<?php

declare(strict_types=1);

namespace Koersa\IAM\UI\Form;

/**
 * Mutable backing object for the registration form. Non-nullable defaults keep
 * the controller free of null checks after the form validates.
 */
final class RegistrationFormData
{
    public string $email = '';
    public string $organizationName = '';
    public string $plainPassword = '';
}
