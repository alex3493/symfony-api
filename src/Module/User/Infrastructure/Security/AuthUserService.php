<?php
declare(strict_types=1);

namespace App\Module\User\Infrastructure\Security;

use App\Module\Shared\Domain\Exception\AccessDeniedDomainException;
use App\Module\Shared\Domain\Exception\FormValidationException;
use App\Module\Shared\Domain\Exception\NotFoundDomainException;
use App\Module\Shared\Domain\Exception\UnauthorizedDomainException;
use App\Module\Shared\Domain\Exception\ValidationException;
use App\Module\Shared\Infrastructure\Persistence\Service\MercureUpdateCapableService;
use App\Module\User\Domain\Contract\AuthTokenServiceInterface;
use App\Module\User\Domain\Contract\AuthUserServiceInterface;
use App\Module\User\Domain\Contract\UserCommandServiceInterface;
use App\Module\User\Domain\Contract\UserQueryServiceInterface;
use App\Module\User\Domain\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

readonly class AuthUserService extends MercureUpdateCapableService implements AuthUserServiceInterface
{
    /**
     * @param \App\Module\User\Domain\Contract\UserCommandServiceInterface $userCommandService
     * @param \App\Module\User\Domain\Contract\UserQueryServiceInterface $userQueryService
     * @param \Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface $passwordHasher
     * @param \App\Module\User\Domain\Contract\AuthTokenServiceInterface $tokenService
     * @param \Symfony\Component\Validator\Validator\ValidatorInterface $validator
     * @param \Symfony\Component\Serializer\SerializerInterface $serializer
     * @param \Symfony\Component\Messenger\MessageBusInterface $bus
     * @param \Symfony\Bundle\SecurityBundle\Security $security
     */
    public function __construct(
        private UserCommandServiceInterface $userCommandService, private UserQueryServiceInterface $userQueryService,
        private UserPasswordHasherInterface $passwordHasher, private AuthTokenServiceInterface $tokenService,
        private ValidatorInterface $validator, private SerializerInterface $serializer, MessageBusInterface $bus,
        Security $security
    ) {
        parent::__construct($bus, $security);
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

        $this->publishUserUpdate($user);

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
        $user = $this->userQueryService->findById($userId);

        $user->removeAuthToken($token);
        $this->userCommandService->save($user);

        $this->tokenService->delete($token);

        $this->publishUserUpdate($user);

        return $user;
    }

    /**
     * @param string $userId
     * @return \App\Module\User\Domain\User
     * @throws \App\Module\Shared\Domain\Exception\AccessDeniedDomainException
     */
    public function signOut(string $userId): User
    {
        $user = $this->userQueryService->findById($userId);

        if (is_null($user)) {
            throw new AccessDeniedDomainException('User not found');
        }

        $user->removeAllAuthTokens();
        $this->userCommandService->save($user);

        $this->publishUserUpdate($user);

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
        $user = $this->userQueryService->findById($userId);

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
        $user = $this->userQueryService->findById($id);

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

    /**
     * @param \App\Module\User\Domain\User $user
     * @return void
     */
    private function publishUserUpdate(User $user): void
    {
        $data = $this->serializer->normalize($user, 'json', ['groups' => ['user']]);

        $this->publishMercureUpdate($data, 'update', false, true);
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
