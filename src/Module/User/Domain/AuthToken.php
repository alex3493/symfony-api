<?php
declare(strict_types=1);

namespace App\Module\User\Domain;

use App\Module\Shared\Domain\ValueObject\EntityId;
use DateInterval;

class AuthToken
{
    /**
     * @param string $id
     * @param \App\Module\User\Domain\User $user
     * @param string $token
     * @param string $name
     * @param \DateTime $createdAt
     * @param \DateTime|null $lastUsedAt
     * @param \DateTime|null $expiresAt
     */
    public function __construct(
        private readonly string $id, private User $user, private string $token, private string $name,
        private readonly \DateTime $createdAt, private ?\DateTime $lastUsedAt, private ?\DateTime $expiresAt
    ) {
    }

    public static function create(User $user, string $token, string $name, ?int $expiresAfter = null): self
    {
        $expiresAt = $expiresAfter > 0 ? (new \DateTime())->add(new DateInterval("PT{$expiresAfter}M")) : null;

        return new self(EntityId::create()->getValue(), $user, $token, $name, new \DateTime(), new \DateTime(),
            $expiresAt);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): AuthToken
    {
        $this->user = $user;

        return $this;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function setToken(string $token): AuthToken
    {
        $this->token = $token;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): AuthToken
    {
        $this->name = $name;

        return $this;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function getLastUsedAt(): ?\DateTime
    {
        return $this->lastUsedAt;
    }

    public function setLastUsedAt(?\DateTime $lastUsedAt): AuthToken
    {
        $this->lastUsedAt = $lastUsedAt;

        return $this;
    }

    public function getExpiresAt(): ?\DateTime
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?\DateTime $expiresAt): AuthToken
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }

    public function isValid(): bool
    {
        return $this->expiresAt === null || $this->expiresAt > new \DateTime();
    }
}
