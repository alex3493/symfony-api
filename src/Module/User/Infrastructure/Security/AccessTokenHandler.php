<?php
declare(strict_types=1);

namespace App\Module\User\Infrastructure\Security;

use App\Module\Shared\Domain\Exception\UnauthorizedDomainException;
use App\Module\User\Infrastructure\Persistence\Doctrine\AuthTokenRepository;
use DateTime;
use SensitiveParameter;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

class AccessTokenHandler implements AccessTokenHandlerInterface
{
    private AuthTokenRepository $repository;

    public function __construct(AuthTokenRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param string $accessToken
     * @return \Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge
     * @throws \App\Module\Shared\Domain\Exception\UnauthorizedDomainException
     */
    public function getUserBadgeFrom(#[SensitiveParameter] string $accessToken): UserBadge
    {
        /** @var \App\Module\User\Domain\AuthToken $accessToken */
        $accessToken = $this->repository->findOneBy(['token' => $accessToken]);
        if (null === $accessToken || ! $accessToken->isValid()) {
            throw new UnauthorizedDomainException('Invalid credentials');
        }

        // Soft-deleted users cannot gain access event their access token is valid.
        if ($accessToken->getUser()->isSoftDeleted()) {
            throw new UnauthorizedDomainException('User is soft-deleted');
        }

        // Touch last-used timestamp.
        $accessToken->setLastUsedAt(new DateTime());
        $this->repository->save($accessToken);

        // We decouple domain user and auth-user, so have to provide user loader closure.
        return new UserBadge($accessToken->getUser()->getUserIdentifier(), function () use ($accessToken) {
            return new AuthUser($accessToken->getUser(), $accessToken->getName());
        });
    }
}
