<?php
declare(strict_types=1);

namespace App\Module\User\Infrastructure\Security;

use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        //
    }

    public function checkPostAuth(UserInterface $user): void
    {
        if (! $user instanceof AuthUser) {
            return;
        }

        // Reserved: here we can check if the user is enabled.
        // if (! $user->isEnabled()) {
        //    throw new AccountExpiredException('Account Disabled');
        // }
    }
}
