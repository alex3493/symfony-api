<?php
declare(strict_types=1);

namespace App\Module\User\Application\UpdateUserProfile;

use App\Module\Shared\Domain\Bus\Command\Command;
use App\Module\Shared\Domain\Bus\Command\ValidatedMessageInterface;

readonly class UpdateUserProfileCommand implements Command, ValidatedMessageInterface
{
    /**
     * @param string $id
     * @param string $email
     * @param string|null $firstName
     * @param string|null $lastName
     */
    public function __construct(
        private string $id, private string $email, private ?string $firstName, private ?string $lastName
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function firstName(): ?string
    {
        return $this->firstName;
    }

    public function lastName(): ?string
    {
        return $this->lastName;
    }

    public function validationContext(): string
    {
        return 'User';
    }
}
