<?php
declare(strict_types=1);

namespace App\Module\User\Infrastructure\Security;

use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactory;

// TODO: this is currently reserved. We are using core UserPasswordHasherInterface.
class PasswordEncoder
{
    private PasswordHasherFactory $factory;

    public function __construct()
    {
        $this->factory = new PasswordHasherFactory([
            'common' => ['algorithm' => 'bcrypt'],
            'memory-hard' => ['algorithm' => 'sodium'],
        ]);
    }

    /**
     * @param string $plainPassword
     * @return string
     */
    public function encodePassword(string $plainPassword): string
    {
        $passwordHasher = $this->factory->getPasswordHasher('common');

        return $passwordHasher->hash($plainPassword);
    }

    /**
     * @param string $plainPassword
     * @param string $hashedPassword
     * @return bool
     */
    public function verifyPassword(string $plainPassword, string $hashedPassword): bool
    {
        $passwordHasher = $this->factory->getPasswordHasher('common');

        return $passwordHasher->verify($hashedPassword, $plainPassword);
    }
}

