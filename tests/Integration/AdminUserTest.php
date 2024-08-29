<?php
declare(strict_types=1);

namespace App\Tests\Integration;

use App\Module\Shared\Domain\Bus\Command\CommandBus;
use App\Module\Shared\Domain\Bus\Query\QueryBus;
use App\Module\Shared\Domain\Exception\ValidationException;
use App\Module\User\Application\Admin\AdminCreateUser\AdminCreateUserCommand;
use App\Module\User\Application\Admin\AdminForceDeleteUser\AdminForceDeleteUserCommand;
use App\Module\User\Application\Admin\AdminRestoreUser\AdminRestoreUserCommand;
use App\Module\User\Application\Admin\AdminSoftDeleteUser\AdminSoftDeleteUserCommand;
use App\Module\User\Application\Admin\AdminUpdateUser\AdminUpdateUserCommand;
use App\Module\User\Application\Admin\AdminUserList\AdminUserListQuery;
use App\Module\User\Domain\Event\UserEmailChangedDomainEvent;
use App\Module\User\Domain\Event\UserRestoredDomainEvent;
use App\Module\User\Domain\Event\UserSoftDeletedDomainEvent;
use App\Module\User\Domain\User;
use App\Tests\DatabaseTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Messenger\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AdminUserTest extends DatabaseTestCase
{
    public function test_admin_user_list_use_case(): void
    {
        $container = static::getContainer();
        $queryBus = $container->get(QueryBus::class);

        // We now restrict access to /api/admin routes in security config.
        // Use case test bypasses global security check, so there is no need to mock security here.
//        $security = $this->createMock(Security::class);
//        $security->method('isGranted')->willReturn(true);
//        $container->set(Security::class, $security);

        for ($i = 0; $i < 20; $i++) {
            self::$userSeeder->seedUser([
                'email' => 'user'.$i.'@example.com',
            ]);
        }

        for ($i = 20; $i < 30; $i++) {
            self::$userSeeder->seedUser([
                'email' => 'user'.$i.'@example.com',
                'deleted' => true,
            ]);
        }

        // Default user list.
        $query = new AdminUserListQuery(1, 10, 'id', 'ASC');

        $response = $queryBus->ask($query);

        $this->assertCount(10, $response->items);
        $this->assertEquals(20, $response->totalItems);
        $this->assertEquals(2, $response->totalPages);

        // User list including soft-deleted users.
        $query = new AdminUserListQuery(1, 10, 'id', 'ASC', true);

        $response = $queryBus->ask($query);

        $this->assertCount(10, $response->items);
        $this->assertEquals(30, $response->totalItems);
        $this->assertEquals(3, $response->totalPages);
    }

    public function test_admin_create_user_command(): void
    {
        $container = static::getContainer();
        $commandBus = $container->get(CommandBus::class);

        $command = new AdminCreateUserCommand('test@test.com', 'password', 'password', 'Name', ['ROLE_ADMIN']);

        $response = $commandBus->dispatch($command);

        $this->assertEquals('test@test.com', $response->user->getEmail());

        // Check that we hash user password.
        $this->assertNotEquals('password', $response->user->getPassword());

        $this->assertCount(2, $response->user->getRoles());
        $this->assertEquals('ROLE_ADMIN', $response->user->getRole());

        $connection = $this->getEntityManager()->getConnection();
        $sql = 'SELECT roles FROM user WHERE id = :id';
        $stmt = $connection->prepare($sql);
        $stmt->bindValue('id', $response->user->getId());

        /** @var \Doctrine\DBAL\Result $result */
        $result = $stmt->executeQuery();
        $roles = json_decode($result->fetchOne());

        // Check that role is persisted in DB. We cannot use User::getRoles() because it
        // decorates DB data, always injecting ROLE_USER.
        $this->assertCount(1, $roles);
        $this->assertEquals('ROLE_ADMIN', $roles[0]);
    }

    public function test_admin_update_user_command(): void
    {
        $container = static::getContainer();
        $userRepository = $this->getRepository(User::class);
        $commandBus = $container->get(CommandBus::class);
        $validator = $container->get(ValidatorInterface::class);

        $user = self::$userSeeder->seedUser()['user'];
        $originalPassword = $user->getPassword();

        // Admin updates user profile not providing new password.
        $command = new AdminUpdateUserCommand($user->getId(), 'updated@example.com', null, 'Jane', 'Doe',
            ['ROLE_ADMIN']);

        $response = $commandBus->dispatch($command);

        $this->assertEquals('updated@example.com', $response->user->getEmail());
        $this->assertEquals('Jane', $response->user->getFirstName());
        $this->assertEquals('Doe', $response->user->getLastName());

        $this->assertCount(2, $response->user->getRoles());
        $this->assertContains('ROLE_ADMIN', $response->user->getRoles());
        $this->assertEquals('ROLE_ADMIN', $response->user->getRole());

        $user = $userRepository->find($user->getId());

        // Check that password didn't change.
        $updatedPassword = $user->getPassword();
        $this->assertEquals($originalPassword, $updatedPassword);

        // We have updated user email, so we expect messages in queue.
        $this->transport('async')->queue()->assertNotEmpty();

        $messages = $this->transport('async')->queue()->messages();

        $this->assertInstanceOf(UserEmailChangedDomainEvent::class, $messages[0]);

        // Check that queued message carries correct payload.
        $this->assertEquals('test@example.com', $messages[0]->getOldEmail());
        $this->assertEquals('updated@example.com', $messages[0]->getNewEmail());

        $this->transport('async')->process(1);

        $this->transport('async')->rejected()->assertEmpty();
        $this->transport('async')->queue()->assertEmpty();

        // Admin updates user profile and password.
        $command = new AdminUpdateUserCommand($user->getId(), 'updated@example.com', 'new_password', 'Jane', 'Doe',
            ['ROLE_USER']);

        $response = $commandBus->dispatch($command);

        // Check that email was not updated.
        $this->assertEquals('updated@example.com', $response->user->getEmail());
        $this->assertEquals('Jane', $response->user->getFirstName());
        $this->assertEquals('Doe', $response->user->getLastName());

        // Check that have reverted user role to ROLE_USER.
        $this->assertCount(1, $response->user->getRoles());
        $this->assertContains('ROLE_USER', $response->user->getRoles());
        $this->assertEquals('ROLE_USER', $response->user->getRole());

        $user = $userRepository->find($user->getId());

        // Check that we did update the password.
        $updatedPassword = $user->getPassword();
        $this->assertNotEquals($originalPassword, $updatedPassword);

        // We didn't update user email, so we don't expect messages in queue.
        $this->transport('async')->queue()->assertEmpty();
    }

    public function test_admin_update_user_command_validation(): void
    {
        $container = static::getContainer();
        $commandBus = $container->get(CommandBus::class);

        $user = self::$userSeeder->seedUser()['user'];

        $command = new AdminUpdateUserCommand($user->getId(), '@invalid-email', null, 'Jane', 'Doe');

        try {
            $commandBus->dispatch($command);

            $this->fail('Validation exception not thrown');
        } catch (ValidationFailedException $e) {
            $violations = $e->getViolations();
            $this->assertCount(1, $violations);
            $this->assertEquals('email', $violations[0]->getPropertyPath());
        }
    }

    public function test_admin_soft_delete_user_command(): void
    {
        $container = static::getContainer();
        $commandBus = $container->get(CommandBus::class);

        $user = self::$userSeeder->seedUser();

        $command = new AdminSoftDeleteUserCommand($user['user']->getId());

        $response = $commandBus->dispatch($command);

        $this->assertNotEmpty($response->user->getDeletedAt());

        // We have soft-deleted user, so we expect messages in queue.
        $this->transport('async')->queue()->assertNotEmpty();

        $messages = $this->transport('async')->queue()->messages();

        $this->assertInstanceOf(UserSoftDeletedDomainEvent::class, $messages[0]);

        $this->transport('async')->process(1);

        $this->transport('async')->rejected()->assertEmpty();
        $this->transport('async')->queue()->assertEmpty();
    }

    public function test_admin_restore_user_command(): void
    {
        $container = static::getContainer();
        $commandBus = $container->get(CommandBus::class);

        $user = self::$userSeeder->seedUser([
            'deleted' => true,
        ]);

        $command = new AdminRestoreUserCommand($user['user']->getId());

        $response = $commandBus->dispatch($command);

        $this->assertEmpty($response->user->getDeletedAt());

        // We have restored soft-deleted user, so we expect messages in queue.
        $this->transport('async')->queue()->assertNotEmpty();

        $messages = $this->transport('async')->queue()->messages();

        $this->assertInstanceOf(UserRestoredDomainEvent::class, $messages[0]);

        $this->transport('async')->process(1);

        $this->transport('async')->rejected()->assertEmpty();
        $this->transport('async')->queue()->assertEmpty();
    }

    public function test_admin_force_delete_user_command(): void
    {
        $container = static::getContainer();
        $commandBus = $container->get(CommandBus::class);

        $user = self::$userSeeder->seedUser();

        $command = new AdminForceDeleteUserCommand($user['user']->getId());

        $response = $commandBus->dispatch($command);

        $this->assertEquals('User successfully deleted.', $response->message);
    }

    public function test_create_user_console_command(): void
    {
        static::getContainer();
        $application = new Application(self::$kernel);

        $command = $application->find('app:add-user');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            // Pass arguments to the helper.
            'email' => 'admin@example.com',
            'password' => 'password',
            'first-name' => 'First',
            'last-name' => 'Last',
            '--admin' => true,
        ]);

        $commandTester->assertCommandIsSuccessful();

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('[OK] Administrator user was successfully created: admin@example.com',
            $output);

        $commandTester->execute([
            // Pass arguments to the helper.
            'email' => 'user@example.com',
            'password' => 'password',
            'first-name' => 'First',
            'last-name' => 'Last',
            // '--admin' => true,
        ]);

        $commandTester->assertCommandIsSuccessful();

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('[OK] User was successfully created: user@example.com', $output);
    }
}
