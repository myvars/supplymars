<?php

declare(strict_types=1);

namespace App\Customer\Domain\Model\User;

use Symfony\Component\Security\Core\Exception\AuthenticationException;

class AccountNotVerifiedAuthenticationException extends AuthenticationException
{
}
