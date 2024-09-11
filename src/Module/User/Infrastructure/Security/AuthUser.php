<?php
declare(strict_types=1);

namespace App\Module\User\Infrastructure\Security;

use App\Module\Shared\Domain\Exception\UnprocessableEntityDomainException;
use App\Module\User\Domain\User;
use App\Module\User\Domain\ValueObject\UserRole;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class AuthUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    /**
     * @param \App\Module\User\Domain\User $user
     * @param string|null $deviceId
     */
    public function __construct(private User $user, private readonly ?string $deviceId = null)
    {
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->user->getId();
    }

    /**
     * @return \App\Module\User\Domain\User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @param \App\Module\User\Domain\User $user
     * @return $this
     */
    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return array
     */
    public function getRoles(): array
    {
        $roles = $this->user->getRoles();
        // Guarantee every user at least has ROLE_USER.
        $roles[] = UserRole::ROLE_USER;

        return array_unique($roles);
    }

    /**
     * @param array $roles
     * @return $this
     * @throws UnprocessableEntityDomainException
     */
    public function setRoles(array $roles): self
    {
        $this->user->setRoles($roles);

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->user->getEmail();
    }

    public function getPassword(): ?string
    {
        return $this->user->getPassword();
    }

    public function getDeviceId(): ?string
    {
        return $this->deviceId;
    }

    public function eraseCredentials(): void
    {
        $this->user->setPassword(null);
    }
}
