<?php
declare(strict_types=1);

namespace App\Module\User\Infrastructure\Security;

use App\Module\User\Domain\Contract\UserCommandServiceInterface;
use App\Module\User\Domain\Contract\UserQueryServiceInterface;
use App\Module\User\Domain\User;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class UserProvider implements UserProviderInterface, PasswordUpgraderInterface
{
    /**
     * @param \App\Module\User\Domain\Contract\UserQueryServiceInterface $userQueryService
     * @param \App\Module\User\Domain\Contract\UserCommandServiceInterface $userCommandService
     */
    public function __construct(
        private UserQueryServiceInterface $userQueryService, private UserCommandServiceInterface $userCommandService
    ) {
    }

    /**
     * @inheritDoc
     */
    public function refreshUser(UserInterface $user): UserInterface
    {
        if (! $user instanceof User) {
            throw new UnsupportedUserException(sprintf('Invalid user class "%s".', get_class($user)));
        }

        return new AuthUser($this->userQueryService->freshUserById($user->getId()));
    }

    /**
     * @inheritDoc
     */
    public function supportsClass(string $class): bool
    {
        return AuthUser::class === $class || is_subclass_of($class, AuthUser::class);
    }

    /**
     * @inheritDoc
     */
    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        // TODO: check if findByEmail works in all scenarios.
        // return new AuthUser($this->userService->findById($identifier));
        $user = $this->userQueryService->findByEmail($identifier);
        if (is_null($user)) {
            throw new UserNotFoundException('Invalid credentials');
        }

        return new AuthUser($this->userQueryService->findByEmail($identifier));
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if ($user instanceof AuthUser) {
            $userEntity = $user->getUser();

            $userEntity->setPassword($newHashedPassword);
            $this->userCommandService->save($userEntity);

            $user->setUser($userEntity);
        }
    }
}
