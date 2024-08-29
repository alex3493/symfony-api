<?php
declare(strict_types=1);

namespace App\Module\User\Infrastructure\Persistence;

use App\Module\Shared\Domain\Exception\NotFoundDomainException;
use App\Module\Shared\Domain\Exception\ValidationException;
use App\Module\Shared\Domain\ValueObject\Email;
use App\Module\User\Domain\Contract\UserCommandServiceInterface;
use App\Module\User\Domain\RefreshToken;
use App\Module\User\Domain\User;
use App\Module\User\Domain\ValueObject\UserRole;
use App\Module\User\Infrastructure\Persistence\Doctrine\UserRepository;
use App\Module\User\Infrastructure\Security\AuthUser;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserCommandService implements UserCommandServiceInterface
{
    private UserRepository $repository;

    private UserPasswordHasherInterface $passwordHasher;

    private ValidatorInterface $validator;

    public function __construct(
        UserRepository $repository, UserPasswordHasherInterface $passwordHasher, ValidatorInterface $validator
    ) {
        $this->repository = $repository;
        $this->passwordHasher = $passwordHasher;
        $this->validator = $validator;
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

        return $this->validateAndSave($user, $password);
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

        return $this->validateAndSave($user, null);
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

        return $this->validateAndSave($user, $password);
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
    private function validateAndSave(User $user, ?string $password): User
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
}
