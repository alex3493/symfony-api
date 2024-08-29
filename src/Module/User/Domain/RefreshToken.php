<?php
declare(strict_types=1);

namespace App\Module\User\Domain;

use DateTime;
use DateTimeInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenInterface;
use InvalidArgumentException;
use Symfony\Component\Security\Core\User\UserInterface;

class RefreshToken implements RefreshTokenInterface
{
    /**
     * @var int|string|null
     */
    private string|int|null $id;

    /**
     * @var string|null
     */
    private ?string $refreshToken;

    /**
     * @var string|null
     */
    private ?string $username;

    /**
     * @var \DateTimeInterface|null
     */
    private ?DateTimeInterface $valid;

    /**
     * @param string $refreshToken
     * @param \Symfony\Component\Security\Core\User\UserInterface $user
     * @param int $ttl
     * @return \Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenInterface
     */
    public static function createForUserWithTtl(
        string $refreshToken, UserInterface $user, int $ttl
    ): RefreshTokenInterface {
        $valid = new DateTime();

        // Explicitly check for a negative number based on a behavior change in PHP 8.2, see https://github.com/php/php-src/issues/9950
        if ($ttl > 0) {
            $valid->modify('+'.$ttl.' seconds');
        } elseif ($ttl < 0) {
            $valid->modify($ttl.' seconds');
        }

        $model = new static();
        $model->setRefreshToken($refreshToken);
        $model->setUsername(method_exists($user,
            'getUserIdentifier') ? $user->getUserIdentifier() : $user->getUsername());
        $model->setValid($valid);

        return $model;
    }

    /**
     * @return string Refresh Token
     */
    public function __toString()
    {
        return $this->getRefreshToken() ?: '';
    }

    /**
     * @return int|string|null
     */
    public function getId(): int|string|null
    {
        return $this->id;
    }

    /**
     * @param $refreshToken
     * @return \Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenInterface
     */
    public function setRefreshToken($refreshToken = null): RefreshTokenInterface
    {
        if (null === $refreshToken || '' === $refreshToken) {
            throw new InvalidArgumentException('Refresh token cannot be empty');
        }

        $this->refreshToken = $refreshToken;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }

    /**
     * @param $valid
     * @return \Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenInterface
     */
    public function setValid($valid): RefreshTokenInterface
    {
        $this->valid = $valid;

        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getValid(): ?DateTimeInterface
    {
        return $this->valid;
    }

    /**
     * @param $username
     * @return \Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenInterface
     */
    public function setUsername($username): RefreshTokenInterface
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getUsername(): ?string
    {
        return $this->username;
    }

    /**
     * @return bool
     */
    public function isValid(): bool
    {
        return null !== $this->valid && $this->valid >= new DateTime();
    }
}
