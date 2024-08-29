<?php
declare(strict_types=1);

namespace App\Module\User\Infrastructure\Security;

use App\Module\User\Domain\AuthToken;
use App\Module\User\Domain\Contract\AuthTokenServiceInterface;
use App\Module\User\Domain\User;
use App\Module\User\Infrastructure\Persistence\Doctrine\AuthTokenRepository;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;

class AuthTokenService implements AuthTokenServiceInterface
{
    private AuthTokenRepository $repository;

    private ContainerBagInterface $params;

    public function __construct(AuthTokenRepository $repository, ContainerBagInterface $params)
    {
        $this->repository = $repository;
        $this->params = $params;
    }

    /**
     * @param \App\Module\User\Domain\User $user
     * @param string $deviceName
     * @return \App\Module\User\Domain\AuthToken
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Random\RandomException
     */
    public function generateAndSaveToken(User $user, string $deviceName): AuthToken
    {
        $token = bin2hex(random_bytes(32));

        $expiresAfter = $this->params->get('app.auth_token_expiration_minutes');
        $authToken = AuthToken::create($user, $token, $deviceName, $expiresAfter ?: null);
        $this->repository->save($authToken);

        return $authToken;
    }

    /**
     * @param string $tokenId
     * @return \App\Module\User\Domain\AuthToken|null
     */
    public function find(string $tokenId): ?AuthToken
    {
        return $this->repository->find($tokenId);
    }

    /**
     * @param \App\Module\User\Domain\AuthToken $authToken
     * @return void
     */
    public function delete(AuthToken $authToken): void
    {
        $this->repository->delete($authToken);
    }

    /**
     * @param \App\Module\User\Domain\User $user
     * @param string $deviceName
     * @return \App\Module\User\Domain\AuthToken|null
     */
    public function existing(User $user, string $deviceName): ?AuthToken
    {
        /** @var AuthToken|null $found */
        $found = $this->repository->findOneBy([
            'user' => $user->getId(),
            'name' => $deviceName,
        ]);

        return $found;
    }
}
