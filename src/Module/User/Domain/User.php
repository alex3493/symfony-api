<?php
declare(strict_types=1);

namespace App\Module\User\Domain;

use App\Module\Shared\Domain\Contract\SoftDeleteAwareEntityInterface;
use App\Module\Shared\Domain\Event\DomainEventAwareEntity;
use App\Module\Shared\Domain\Exception\UnprocessableEntityDomainException;
use App\Module\Shared\Domain\ValueObject\Email;
use App\Module\Shared\Domain\ValueObject\EntityId;
use App\Module\User\Domain\Event\UserEmailChangedDomainEvent;
use App\Module\User\Domain\Event\UserRestoredDomainEvent;
use App\Module\User\Domain\Event\UserSoftDeletedDomainEvent;
use App\Module\User\Domain\ValueObject\UserRole;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class User extends DomainEventAwareEntity implements SoftDeleteAwareEntityInterface
{
    private string $id;

    private string $email;

    private ?string $password;

    private ?string $firstName;

    private ?string $lastName;

    /**
     * @var array<string>
     */
    private array $roles;

    /**
     * @var Collection<AuthToken>
     */
    private Collection $authTokens;

    private DateTime $createdAt;

    private ?DateTime $updatedAt;

    private ?DateTime $deletedAt;

    public function __construct(
        EntityId $id, Email $email, string $password, ?string $firstName, ?string $lastName, array $roles,
        DateTime $createdAt, ?DateTime $updatedAt = null, ?DateTime $deletedAt = null
    ) {
        $this->id = $id->getValue();
        $this->email = $email->getValue();
        $this->password = $password;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->roles = $roles;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
        $this->deletedAt = $deletedAt;

        $this->authTokens = new ArrayCollection();
    }

    /**
     * @param string $email
     * @param string $password
     * @param string|null $firstName
     * @param string|null $lastName
     * @param array $roles
     * @return User
     * @throws \App\Module\Shared\Domain\Exception\UnprocessableEntityDomainException
     */
    public static function create(
        string $email, string $password, ?string $firstName, ?string $lastName, array $roles = []
    ): User {
        $user = new self(EntityId::create(), new Email($email), $password, $firstName, $lastName, [], new DateTime());

        return $user->setRoles($roles);
    }

    /**
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param Email $email
     * @return $this
     */
    public function setEmail(Email $email): User
    {
        if ($this->email && $email->getValue() !== $this->email) {
            // If we have current email and it is updated we record domain event.
            $oldEmail = $this->email;
            $newEmail = $email->getValue();

            $this->record(new UserEmailChangedDomainEvent($this, $oldEmail, $newEmail));
        }

        $this->email = $email->getValue();

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * @param string|null $password
     * @return $this
     */
    public function setPassword(?string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    /**
     * @param string|null $firstName
     * @return $this
     */
    public function setFirstName(?string $firstName): User
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    /**
     * @param string|null $lastName
     * @return $this
     */
    public function setLastName(?string $lastName): User
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * @return array<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // Guarantee every user at least has ROLE_USER.
        $roles[] = UserRole::ROLE_USER;

        return array_unique($roles);
    }

    /**
     * @param array<string> $roles
     * @return $this
     * @throws UnprocessableEntityDomainException
     */
    public function setRoles(array $roles): self
    {
        $this->roles = [];

        foreach ($roles as $role) {
            $this->roles[] = (string) new UserRole($role);
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getRole(): string
    {
        return $this->getRoles()[0];
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    /**
     * @return \DateTime|null
     */
    public function getDeletedAt(): ?DateTime
    {
        return $this->deletedAt;
    }

    /**
     * @param \DateTime|null $deletedAt
     * @return void
     */
    public function setDeletedAt(?DateTime $deletedAt): void
    {
        $this->deletedAt = $deletedAt;
    }

    /**
     * @return \DateTime|null
     */
    public function getUpdatedAt(): ?DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAtNow(): void
    {
        $this->updatedAt = new DateTime();
    }

    /**
     * @return string
     */
    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    /**
     * @return Collection
     */
    public function getAuthTokens(): Collection
    {
        // Regular getter.
        // return $this->authTokens;

        /** @link https://github.com/api-platform/core/issues/285 */

        // Workaround: make sure we always get a plain array collection.
        // ArrayCollection::removeElement() method may introduce wrong indices in resulting
        // normalized array.
        // We have to find a better solution and revert immediately if we find cases when
        // $this->authTokens is a PersistentCollection at this point. The code below may
        // totally break persistent collections...
        return new ArrayCollection(array_values($this->authTokens->toArray()));
    }

    // Reserved for cases when we have to reinit collection just before normalizing result.
    //public function importAuthTokens(array $authTokens): void
    //{
    //    $this->authTokens = new ArrayCollection(array_values($authTokens));
    //}

    /**
     * @param AuthToken $authToken
     * @return $this
     */
    public function addAuthToken(AuthToken $authToken): self
    {
        $this->authTokens->add($authToken);

        return $this;
    }

    /**
     * @param AuthToken $authToken
     * @return $this
     */
    public function removeAuthToken(AuthToken $authToken): self
    {
        $this->authTokens->removeElement($authToken);

        return $this;
    }

    /**
     * @return $this
     */
    public function removeAllAuthTokens(): self
    {
        $this->authTokens->clear();

        return $this;
    }

    /**
     * @return string
     */
    public function getDisplayName(): string
    {
        $name = $this->firstName.' '.$this->lastName;

        if (empty(trim($name))) {
            $name = $this->email;
        }

        return trim($name);
    }

    /**
     * @return void
     */
    public function softDelete(): void
    {
        $this->deletedAt = new DateTime();

        $this->record(new UserSoftDeletedDomainEvent($this));
    }

    /**
     * @return bool
     */
    public function isSoftDeleted(): bool
    {
        return ! is_null($this->deletedAt);
    }

    /**
     * @return void
     */
    public function restore(): void
    {
        $this->deletedAt = null;

        $this->record(new UserRestoredDomainEvent($this));
    }
}
