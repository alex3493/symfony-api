<?php
declare(strict_types=1);

namespace App\Tests\Seeder;

use App\Module\Shared\Domain\ValueObject\EntityId;
use App\Module\User\Domain\AuthToken;
use App\Module\User\Domain\RefreshToken;
use App\Module\User\Domain\ResetPasswordToken;
use App\Module\User\Domain\User;
use App\Module\User\Infrastructure\Security\AuthUser;
use DateInterval;
use DateTime;
use Doctrine\Persistence\ObjectManager;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserSeeder extends SeederBase
{
    private UserPasswordHasherInterface $passwordHasher;

    private JWTTokenManagerInterface $JWTManager;

    public function __construct(
        ObjectManager $manager, UserPasswordHasherInterface $passwordHasher, JWTTokenManagerInterface $JWTManager
    ) {
        parent::__construct($manager);

        $this->passwordHasher = $passwordHasher;
        $this->JWTManager = $JWTManager;
    }

    /**
     * @param array $options
     * @param array $withTokens - if not empty, create a user ready to access private pages.
     * @param bool $withJwt
     * @return array
     * @throws \Random\RandomException
     * @throws \App\Module\Shared\Domain\Exception\UnprocessableEntityDomainException
     */
    public function seedUser(array $options = [], array $withTokens = [], bool $withJwt = false): array
    {
        $options = array_merge([
            'email' => 'test@example.com',
            'password' => 'password',
            'firstName' => 'John',
            'lastName' => 'Doe',
            'roles' => ['ROLE_USER'],
            // false: not deleted (default), true: deleted right now, DateTime: exact deleted date.
            'deleted' => false,
        ], $options);

        $user = User::create($options['email'], $options['password'], $options['firstName'], $options['lastName'],
            $options['roles']);

        if ($options['deleted']) {
            if (true === $options['deleted']) {
                $user->setDeletedAt(new DateTime());
            } else {
                $user->setDeletedAt($options['deleted']);
            }
        }

        $authUser = new AuthUser($user);

        $hashedPassword = $this->passwordHasher->hashPassword($authUser, $options['password']);
        $user->setPassword($hashedPassword);

        /** @var \App\Module\User\Infrastructure\Persistence\Doctrine\UserRepository $repository */
        $repository = $this->objectManager->getRepository(User::class);

        $repository->save($user);

        // We have a user at this point. No tokens were created yet, so user must log in
        // prior to access private pages.

        // Create device tokens if need be. We can pass multiple devices, so that multiple
        // tokens are created.
        // When in test method we can get user tokens: $user->getTokens()
        if ($withTokens) {
            $tokenRepository = $this->objectManager->getRepository(AuthToken::class);
            foreach ($withTokens as $withToken) {
                $token = bin2hex(random_bytes(32));

                $withToken = array_merge([
                    'isExpired' => false,
                    'expiresAfter' => null,
                    'name' => 'web',
                ], $withToken);

                $expiresAfter = $withToken['expiresAfter'] ?? null;
                $expiresAt = $withToken['isExpired'] ? new DateTime('yesterday noon') : ($expiresAfter > 0 ? (new DateTime())->add(new DateInterval("PT{$expiresAfter}M")) : null);
                $authToken = new AuthToken(EntityId::create()->getValue(), $user, $token, $withToken['name'], new DateTime(), null,
                    $expiresAt);

                $tokenRepository->save($authToken);

                $user->addAuthToken($authToken);
            }

            $repository->save($user);
        }

        if ($withJwt) {
            $JWTToken = $this->JWTManager->create($authUser);
            $JWTRefreshToken = bin2hex(random_bytes(64));

            $refreshTokenRepository = $this->objectManager->getRepository(RefreshToken::class);
            $refreshToken = new RefreshToken();

            $refreshToken->setUsername($user->getEmail());
            $refreshToken->setRefreshToken($JWTRefreshToken);
            $refreshToken->setValid(new DateTime('+1 hours'));

            $refreshTokenRepository->save($refreshToken);
        }

        return [
            'user' => $user,
            'app_token' => isset($user->getAuthTokens()[0]) // first valid app token (if any)
                ? $user->getAuthTokens()[0]->getToken() : false,
            'jwt_token' => $JWTToken ?? false,
            'refresh_token' => $JWTRefreshToken ?? false,
        ];
    }

    public function seedPasswordResetToken(User $user, string $token, ?\DateTime $validUntil = null): ResetPasswordToken
    {
        $resetPasswordTokenRepository = $this->objectManager->getRepository(ResetPasswordToken::class);

        $validUntil = $validUntil ?? new DateTime('+1 hour');
        $token = new ResetPasswordToken($user->getEmail(), $token, $validUntil);

        $resetPasswordTokenRepository->save($token);

        return $token;
    }
}
