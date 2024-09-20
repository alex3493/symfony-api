<?php
declare(strict_types=1);

namespace App\Module\User\Infrastructure\Persistence;

use App\Module\Shared\Domain\Exception\NotFoundDomainException;
use App\Module\Shared\Domain\Exception\ValidationException;
use App\Module\Shared\Domain\ValueObject\Email;
use App\Module\Shared\Infrastructure\Persistence\Service\MercureUpdateCapableService;
use App\Module\User\Domain\Contract\UserCommandServiceInterface;
use App\Module\User\Domain\RefreshToken;
use App\Module\User\Domain\User;
use App\Module\User\Domain\ValueObject\UserRole;
use App\Module\User\Infrastructure\Persistence\Doctrine\UserRepository;
use App\Module\User\Infrastructure\Security\AuthUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

readonly class UserCommandService extends MercureUpdateCapableService implements UserCommandServiceInterface
{
    /**
     * @param \App\Module\User\Infrastructure\Persistence\Doctrine\UserRepository $repository
     * @param \Symfony\Component\Serializer\SerializerInterface $serializer
     * @param \Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface $passwordHasher
     * @param \Symfony\Component\Validator\Validator\ValidatorInterface $validator
     * @param \Symfony\Component\Messenger\MessageBusInterface $bus
     * @param \Symfony\Bundle\SecurityBundle\Security $security
     */
    public function __construct(
        private UserRepository $repository, private SerializerInterface $serializer,
        private UserPasswordHasherInterface $passwordHasher, private ValidatorInterface $validator,
        MessageBusInterface $bus, Security $security
    ) {
        parent::__construct($bus, $security);
    }

    /**
     * @param string $email
     * @param string $password
     * @param string|null $firstName
     * @param string|null $lastName
     * @param array $roles
     * @return \App\Module\User\Domain\User
     * @throws \App\Module\Shared\Domain\Exception\UnprocessableEntityDomainException
     * @throws \App\Module\Shared\Domain\Exception\ValidationException
     */
    public function create(
        string $email, string $password, ?string $firstName, ?string $lastName, array $roles = []
    ): User {
        // If no user roles provided we always add default user role.
        if (empty($roles)) {
            $roles = [UserRole::ROLE_USER];
        }

        $user = User::create($email, $password, $firstName, $lastName, $roles);

        $user = $this->validateAndSave($user, $password);

        $this->publishUserUpdate($user, 'user_create');

        return $user;
    }

    /**
     * @param string $id
     * @param string $email
     * @param string|null $firstName
     * @param string|null $lastName
     * @return \App\Module\User\Domain\User
     * @throws \App\Module\Shared\Domain\Exception\NotFoundDomainException
     * @throws \App\Module\Shared\Domain\Exception\UnprocessableEntityDomainException
     * @throws \App\Module\Shared\Domain\Exception\ValidationException
     */
    public function updateProfile(string $id, string $email, ?string $firstName, ?string $lastName): User
    {
        /** @var User|null $user */
        $user = $this->repository->find($id);

        if (is_null($user)) {
            throw new NotFoundDomainException('User not found');
        }

        $user->setEmail(new Email($email));
        $user->setFirstName($firstName);
        $user->setLastName($lastName);

        $user = $this->validateAndSave($user);

        $this->publishUserUpdate($user, 'user_update', true);

        return $user;
    }

    /**
     * @param string $id
     * @param string $email
     * @param string|null $password
     * @param string|null $firstName
     * @param string|null $lastName
     * @param array $roles
     * @return \App\Module\User\Domain\User
     * @throws \App\Module\Shared\Domain\Exception\NotFoundDomainException
     * @throws \App\Module\Shared\Domain\Exception\UnprocessableEntityDomainException
     * @throws \App\Module\Shared\Domain\Exception\ValidationException
     */
    public function adminUpdate(
        string $id, string $email, ?string $password, ?string $firstName, ?string $lastName, array $roles
    ): User {
        /** @var User|null $user */
        $user = $this->repository->find($id);

        if (is_null($user)) {
            throw new NotFoundDomainException('User not found');
        }

        $user->setEmail(new Email($email));
        $user->setFirstName($firstName);
        $user->setLastName($lastName);

        $user->setRoles($roles);

        $user = $this->validateAndSave($user, $password);

        $this->publishUserUpdate($user, 'user_update', true);

        return $user;
    }

    /**
     * @param string $id
     * @return void
     * @throws \App\Module\Shared\Domain\Exception\NotFoundDomainException
     * @throws \Doctrine\ORM\Exception\NotSupported
     */
    public function logout(string $id): void
    {
        /** @var User|null $user */
        $user = $this->repository->find($id);

        if (is_null($user)) {
            throw new NotFoundDomainException('User not found');
        }

        /** @var \App\Module\User\Domain\Contract\RefreshTokenRepositoryInterface $refreshTokenRepository */
        $refreshTokenRepository = $this->repository->getRelatedRepository(RefreshToken::class);

        $tokens = $refreshTokenRepository->findByUser($user->getUserIdentifier());
        foreach ($tokens as $token) {
            $refreshTokenRepository->delete($token);
        }
    }

    /**
     * @param string $id
     * @return void
     * @throws \App\Module\Shared\Domain\Exception\NotFoundDomainException
     */
    public function forceDelete(string $id): void
    {
        /** @var User|null $user */
        $user = $this->repository->findByIdWithoutFilter($id);

        if (is_null($user)) {
            throw new NotFoundDomainException('User not found');
        }

        // Delete from repository.
        $this->repository->delete($user);

        $this->publishUserUpdate($user, 'user_force_delete', true);
    }

    /**
     * @param string $id
     * @return \App\Module\User\Domain\User
     * @throws \App\Module\Shared\Domain\Exception\NotFoundDomainException
     */
    public function softDelete(string $id): User
    {
        /** @var User|null $user */
        $user = $this->repository->find($id);

        if (is_null($user)) {
            throw new NotFoundDomainException('User not found');
        }

        $user->softDelete();

        $this->repository->save($user);

        $this->publishUserUpdate($user, 'user_soft_delete', true);

        return $user;
    }

    /**
     * @param string $id
     * @return \App\Module\User\Domain\User
     * @throws \App\Module\Shared\Domain\Exception\NotFoundDomainException
     */
    public function restore(string $id): User
    {
        /** @var User|null $user */
        $user = $this->repository->findByIdWithoutFilter($id);

        if (is_null($user)) {
            throw new NotFoundDomainException('User not found');
        }

        $user->restore();

        $this->repository->save($user);

        $this->publishUserUpdate($user, 'user_restore');

        return $user;
    }

    public function save(User $user): User
    {
        $this->repository->save($user);

        return $user;
    }

    /**
     * @param \App\Module\User\Domain\User $user
     * @param string|null $password
     * @return \App\Module\User\Domain\User
     * @throws \App\Module\Shared\Domain\Exception\ValidationException
     */
    private function validateAndSave(User $user, ?string $password = null): User
    {
        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            throw new ValidationException($errors);
        }

        if (! is_null($password)) {
            $authUser = new AuthUser($user);

            $hashedPassword = $this->passwordHasher->hashPassword($authUser, $password);
            $user->setPassword($hashedPassword);
        }

        $this->repository->save($user);

        return $user;
    }

    /**
     * @param \App\Module\User\Domain\User $user
     * @param string $action
     * @param bool $duplicateToItemTopic
     * @return void
     */
    private function publishUserUpdate(User $user, string $action, bool $duplicateToItemTopic = false): void
    {
        $data = $this->serializer->normalize($user, 'json', ['groups' => ['user']]);

        $this->publishMercureUpdate($data, $action, true, $duplicateToItemTopic);
    }

    protected function listTopic(): string
    {
        return 'users::update';
    }

    protected function singleItemTopic(): string
    {
        return 'user::update::';
    }
}
