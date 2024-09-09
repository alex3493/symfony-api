<?php
declare(strict_types=1);

namespace App\Module\User\Infrastructure\Security;

use App\Module\Shared\Domain\Exception\AccessDeniedDomainException;
use App\Module\Shared\Domain\Exception\FormValidationException;
use App\Module\Shared\Domain\Exception\NotFoundDomainException;
use App\Module\Shared\Domain\Exception\UnauthorizedDomainException;
use App\Module\Shared\Domain\Exception\ValidationException;
use App\Module\User\Domain\Contract\AuthTokenServiceInterface;
use App\Module\User\Domain\Contract\AuthUserServiceInterface;
use App\Module\User\Domain\Contract\UserCommandServiceInterface;
use App\Module\User\Domain\Contract\UserQueryServiceInterface;
use App\Module\User\Domain\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

readonly class AuthUserService implements AuthUserServiceInterface
{
    /**
     * @param \App\Module\User\Domain\Contract\UserCommandServiceInterface $userCommandService
     * @param \App\Module\User\Domain\Contract\UserQueryServiceInterface $userQueryService
     * @param \Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface $passwordHasher
     * @param \App\Module\User\Domain\Contract\AuthTokenServiceInterface $tokenService
     * @param \Symfony\Component\Validator\Validator\ValidatorInterface $validator
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        private UserCommandServiceInterface $userCommandService, private UserQueryServiceInterface $userQueryService,
        private UserPasswordHasherInterface $passwordHasher, private AuthTokenServiceInterface $tokenService,
        private ValidatorInterface $validator, private LoggerInterface $logger
    ) {
    }

    /**
     * @param string $email
     * @param string $password
     * @param string|null $firstName
     * @param string|null $lastName
     * @param string|null $deviceName
     * @return array (User, string)
     * @throws \App\Module\Shared\Domain\Exception\UnprocessableEntityDomainException
     * @throws \App\Module\Shared\Domain\Exception\ValidationException
     */
    public function register(
        string $email, string $password, ?string $firstName, ?string $lastName, ?string $deviceName = null
    ): array {
        $user = User::create($email, $password, $firstName, $lastName, ['ROLE_USER']);

        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            throw new ValidationException($errors);
        }

        $authUser = new AuthUser($user);

        $hashedPassword = $this->passwordHasher->hashPassword($authUser, $password);
        $user->setPassword($hashedPassword);

        $deviceName = $deviceName ?? 'web';
        $authToken = $this->tokenService->generateAndSaveToken($user, $deviceName);

        $user->addAuthToken($authToken);

        $this->userCommandService->save($user);

        return [$user, $authToken->getToken()];
    }

    /**
     * @param string $email
     * @param string $password
     * @param string|null $deviceName
     * @return array
     * @throws \App\Module\Shared\Domain\Exception\UnauthorizedDomainException
     */
    public function login(string $email, string $password, ?string $deviceName = null): array
    {
        $user = $this->userQueryService->findByEmail($email);

        if (is_null($user)) {
            throw new UnauthorizedDomainException('Invalid credentials');
        }

        $authUser = new AuthUser($user);

        if (! $this->passwordHasher->isPasswordValid($authUser, $password)) {
            throw new UnauthorizedDomainException('Invalid credentials');
        }

        $deviceName = $deviceName ?? 'web';

        $existing = $this->tokenService->existing($user, $deviceName);

        if (! is_null($existing)) {
            $this->tokenService->delete($existing);
        }

        $authToken = $this->tokenService->generateAndSaveToken($user, $deviceName);

        $user->addAuthToken($authToken);
        $this->userCommandService->save($user);

        return [$user, $authToken->getToken()];
    }

    /**
     * @param string $tokenId
     * @return \App\Module\User\Domain\User
     * @throws \App\Module\Shared\Domain\Exception\NotFoundDomainException
     */
    public function logout(string $tokenId): User
    {
        /** @var ?\App\Module\User\Domain\AuthToken $token */
        $token = $this->tokenService->find($tokenId);
        if (is_null($token)) {
            throw new NotFoundDomainException('Token not found');
        }

        $userId = $token->getUser()->getId();
        $user = $this->userQueryService->freshUserById($userId);

        $user->removeAuthToken($token);
        $this->userCommandService->save($user);

        $this->tokenService->delete($token);

        return $user;
    }

    /**
     * @param string $userId
     * @return \App\Module\User\Domain\User
     * @throws \App\Module\Shared\Domain\Exception\AccessDeniedDomainException
     */
    public function signOut(string $userId): User
    {
        $user = $this->userQueryService->freshUserById($userId);

        if (is_null($user)) {
            throw new AccessDeniedDomainException('User not found');
        }

        $user->removeAllAuthTokens();
        $this->userCommandService->save($user);

        return $user;
    }

    /**
     * @param string $userId
     * @param string $currentPassword
     * @param string $password
     * @return \App\Module\User\Domain\User
     * @throws \App\Module\Shared\Domain\Exception\AccessDeniedDomainException
     * @throws \App\Module\Shared\Domain\Exception\FormValidationException
     */
    public function changePassword(string $userId, string $currentPassword, string $password): User
    {
        // When we change password we check that the current password provided in request is valid.
        // We must get fresh user here because user password was already erased in
        // authentication manager.
        $user = $this->userQueryService->freshUserById($userId);

        if (is_null($user)) {
            throw new AccessDeniedDomainException('User not found');
        }

        $authUser = new AuthUser($user);

        if (! $this->passwordHasher->isPasswordValid($authUser, $currentPassword)) {
            throw new FormValidationException('Invalid credentials', [
                [
                    'property' => 'currentPassword',
                    'errors' => ['Wrong value for your current password.'],
                    'context' => 'User',
                ],
            ]);
        }

        $hashedPassword = $this->passwordHasher->hashPassword($authUser, $password);
        $user->setPassword($hashedPassword);

        $this->userCommandService->save($user);

        return $user;
    }

    /**
     * @param string $id
     * @param string $password
     * @return void
     * @throws \App\Module\Shared\Domain\Exception\AccessDeniedDomainException
     * @throws \App\Module\Shared\Domain\Exception\FormValidationException
     */
    public function deleteAccount(string $id, string $password): void
    {
        // When we delete account we check that the password provided in request is valid.
        // We must get fresh user here because user password was already erased in
        // authentication manager.
        $user = $this->userQueryService->freshUserById($id);

        if (is_null($user)) {
            throw new AccessDeniedDomainException('User not found');
        }

        $authUser = new AuthUser($user);

        if (! $this->passwordHasher->isPasswordValid($authUser, $password)) {
            throw new FormValidationException('Invalid credentials', [
                [
                    'property' => 'password',
                    'errors' => ['Wrong value for your current password.'],
                    'context' => 'User',
                ],
            ]);
        }

        $this->userCommandService->forceDelete($id);
    }
}
