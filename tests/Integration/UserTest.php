<?php
declare(strict_types=1);

namespace App\Tests\Integration;

use App\Module\Shared\Domain\Bus\Command\CommandBus;
use App\Module\Shared\Domain\Exception\FormValidationException;
use App\Module\Shared\Domain\Exception\UnauthorizedDomainException;
use App\Module\Shared\Domain\Exception\ValidationException;
use App\Module\User\Application\ChangePassword\ChangePasswordCommand;
use App\Module\User\Application\DeleteAppUser\DeleteAppUserCommand;
use App\Module\User\Application\LoginAppUser\LoginAppUserCommand;
use App\Module\User\Application\LogoutAppUserDevice\LogoutAppUserDeviceCommand;
use App\Module\User\Application\LogoutWebUser\LogoutWebUserCommand;
use App\Module\User\Application\RegisterAppUser\RegisterAppUserCommand;
use App\Module\User\Application\RegisterWebUser\RegisterWebUserCommand;
use App\Module\User\Application\ResetPassword\PerformResetPassword\PerformResetPasswordCommand;
use App\Module\User\Application\ResetPassword\RequestResetPassword\RequestResetPasswordCommand;
use App\Module\User\Application\SignOutAppUser\SignOutAppUserCommand;
use App\Module\User\Application\UpdateUserProfile\UpdateUserProfileCommand;
use App\Module\User\Domain\AuthToken;
use App\Module\User\Domain\ResetPasswordToken;
use App\Module\User\Domain\User;
use App\Tests\DatabaseTestCase;
use Symfony\Component\Messenger\Exception\ValidationFailedException;

class UserTest extends DatabaseTestCase
{
    public function test_register_app_user_command(): void
    {
        $container = static::getContainer();

        $commandBus = $container->get(CommandBus::class);

        $command = new RegisterAppUserCommand('user@example.com', 'password', 'password', 'John', 'Doe', 'iPhone 15');

        $response = $commandBus->dispatch($command);

        $this->assertNotEmpty($response->token);
        $this->assertNotEmpty($response->user->getId());

        // Check for correct ROLE_USER.
        $connection = $this->getEntityManager()->getConnection();
        $sql = 'SELECT roles FROM user WHERE id = :id';
        $stmt = $connection->prepare($sql);
        $stmt->bindValue('id', $response->user->getId());

        $result = $stmt->executeQuery();
        $roles = json_decode($result->fetchOne());

        // Check that role is persisted in DB. We cannot use User::getRoles() because it
        // decorates DB data, injecting ROLE_USER if no roles are stored.
        $this->assertCount(1, $roles);
        $this->assertEquals('ROLE_USER', $roles[0]);
    }

    public function test_register_app_user_command_validation(): void
    {
        $container = static::getContainer();
        $commandBus = $container->get(CommandBus::class);

        // "Invalid password confirmation" case.
        $command = new RegisterAppUserCommand('user@example.com', 'password', 'wrong-password-confirmation', 'John',
            'Doe');

        try {
            $commandBus->dispatch($command);

            $this->fail('Validation exception not thrown');
        } catch (ValidationFailedException $e) {
            $violations = $e->getViolations();
            $this->assertCount(1, $violations);
            $this->assertEquals('passwordConfirmation', $violations[0]->getPropertyPath());
        }

        // "Invalid email" case.
        $command = new RegisterAppUserCommand('@invalid-email', 'password', 'password', 'John', 'Doe');

        try {
            $commandBus->dispatch($command);

            $this->fail('Validation exception not thrown');
        } catch (ValidationFailedException $e) {
            $violations = $e->getViolations();
            $this->assertCount(1, $violations);
            $this->assertEquals('email', $violations[0]->getPropertyPath());
        }
    }

    public function test_register_app_user_duplicate_email_validation(): void
    {
        $container = static::getContainer();

        self::$userSeeder->seedUser([
            'email' => 'user@example.com',
        ]);

        $commandBus = $container->get(CommandBus::class);

        $command = new RegisterAppUserCommand('user@example.com', 'password', 'password', 'John', 'Doe', 'iPhone 15');

        try {
            $commandBus->dispatch($command);

            $this->fail('Validation exception not thrown');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertCount(1, $errors);
            $this->assertEquals('Email is already taken', $errors[0]['errors'][0]);
            $this->assertEquals('email', $errors[0]['property']);
        }
    }

    public function test_login_app_user_command(): void
    {
        $container = static::getContainer();

        static::$userSeeder->seedUser([
            'email' => 'test@test.com',
            'password' => 'password',
            'firstName' => 'First',
            'lastName' => 'Last',
            'roles' => ['ROLE_USER'],
        ]);

        $commandBus = $container->get(CommandBus::class);

        $command = new LoginAppUserCommand('test@test.com', 'password', 'iPhone 15');

        $response = $commandBus->dispatch($command);

        $this->assertEquals('test@test.com', $response->user->getEmail());

        // Check that we have a token in response.
        $this->assertNotEmpty($response->token);

        // Check that user tokens were persisted in DB.
        $tokenRepository = $this->getRepository(AuthToken::class);
        $tokens = $tokenRepository->findAll();

        $this->assertCount(1, $tokens);

        $this->assertEquals('iPhone 15', $tokens[0]->getName());
        $this->assertEquals('test@test.com', $tokens[0]->getUser()->getEmail());

        // TODO: check for Mercure update message...
    }

    public function test_login_app_user_command_validation(): void
    {
        $container = static::getContainer();

        static::$userSeeder->seedUser([
            'email' => 'test@test.com',
            'password' => 'password',
            'firstName' => 'First',
            'lastName' => 'Last',
            'roles' => ['ROLE_USER'],
        ]);

        $commandBus = $container->get(CommandBus::class);

        // "User not found" case.
        $command = new LoginAppUserCommand('unknown@test.com', 'password', 'iPhone 15');

        $this->expectException(UnauthorizedDomainException::class);
        $commandBus->dispatch($command);

        // "Wrong password" case.
        $command = new LoginAppUserCommand('test@test.com', 'wrong_password', 'iPhone 15');

        $this->expectException(UnauthorizedDomainException::class);
        $commandBus->dispatch($command);
    }

    public function test_register_web_user_command(): void
    {
        $container = static::getContainer();

        $commandBus = $container->get(CommandBus::class);

        $command = new RegisterWebUserCommand('user@example.com', 'password', 'password', 'John', 'Doe');

        $response = $commandBus->dispatch($command);

        $this->assertNotEmpty($response->user->getId());
    }

    public function test_register_web_user_command_validation(): void
    {
        $container = static::getContainer();

        self::$userSeeder->seedUser([
            'email' => 'user@example.com',
        ]);

        $commandBus = $container->get(CommandBus::class);

        // "Passwords do not match" case.
        $command = new RegisterWebUserCommand('another@example.com', 'password', 'wrong_confirmation', 'John', 'Doe');

        try {
            $commandBus->dispatch($command);

            $this->fail('Validation exception not thrown');
        } catch (ValidationFailedException $e) {
            $violations = $e->getViolations();
            $this->assertCount(1, $violations);
            $this->assertEquals('passwordConfirmation', $violations[0]->getPropertyPath());
        }

        // "Email already taken" case.
        $command = new RegisterWebUserCommand('user@example.com', 'password', 'password', 'John', 'Doe');

        try {
            // Validation should fail when we dispatch command.
            $commandBus->dispatch($command);

            $this->fail('Validation exception not thrown');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertCount(1, $errors);
            $this->assertEquals('Email is already taken', $errors[0]['errors'][0]);
            $this->assertEquals('email', $errors[0]['property']);
        }
    }

    public function test_update_user_profile_command(): void
    {
        $container = static::getContainer();

        $user = self::$userSeeder->seedUser([
            'email' => 'user@example.com',
        ])['user'];

        $commandBus = $container->get(CommandBus::class);

        $command = new UpdateUserProfileCommand($user->getId(), 'updated@example.com', 'Jane', 'Doe');

        $response = $commandBus->dispatch($command);

        $this->assertEquals($user->getId(), $response->user->getId());
        $this->assertEquals('updated@example.com', $response->user->getEmail());
        $this->assertEquals('Jane', $response->user->getFirstName());
        $this->assertEquals('Doe', $response->user->getLastName());
    }

    public function test_update_user_profile_command_validation(): void
    {
        $container = static::getContainer();

        $user = self::$userSeeder->seedUser([
            'email' => 'user@example.com',
        ])['user'];

        $user2 = self::$userSeeder->seedUser([
            'email' => 'user2@example.com',
        ])['user'];

        $commandBus = $container->get(CommandBus::class);

        // "Email already taken" case.
        $command = new UpdateUserProfileCommand($user->getId(), $user2->getEmail(), 'Jane', 'Doe');

        try {
            $commandBus->dispatch($command);

            $this->fail('Validation exception not thrown');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertCount(1, $errors);
            $this->assertEquals('Email is already taken', $errors[0]['errors'][0]);
            $this->assertEquals('email', $errors[0]['property']);
        }

        // "Invalid email" case.
        $command = new UpdateUserProfileCommand($user->getId(), '@invalid-email', 'Jane', 'Doe');

        try {
            $commandBus->dispatch($command);

            $this->fail('Validation exception not thrown');
        } catch (ValidationFailedException $e) {
            $violations = $e->getViolations();
            $this->assertCount(1, $violations);
            $this->assertEquals('email', $violations[0]->getPropertyPath());
        }
    }

    public function test_change_password_command(): void
    {
        $container = static::getContainer();

        $commandBus = $container->get(CommandBus::class);

        $user = self::$userSeeder->seedUser()['user'];

        $oldPassword = $user->getPassword();

        $command = new ChangePasswordCommand($user->getId(), 'password', 'new_password', 'new_password');

        $response = $commandBus->dispatch($command);

        $this->assertEquals($user->getId(), $response->user->getId());

        $this->assertNotEquals($oldPassword, $response->user->getPassword());
    }

    public function test_change_password_command_validation(): void
    {
        $container = static::getContainer();

        $commandBus = $container->get(CommandBus::class);

        $user = self::$userSeeder->seedUser()['user'];

        // "Invalid password confirmation" case.
        $command = new ChangePasswordCommand($user->getId(), 'password', 'new_password', 'wrong_confirmation');

        try {
            $commandBus->dispatch($command);

            $this->fail('Validation exception not thrown');
        } catch (ValidationFailedException $e) {
            $violations = $e->getViolations();
            $this->assertCount(1, $violations);
            $this->assertEquals('passwordConfirmation', $violations[0]->getPropertyPath());
        }

        // "Wrong current password" case.
        $command = new ChangePasswordCommand($user->getId(), 'wrong_current_password', 'new_password', 'new_password');

        try {
            // Validation should fail when we dispatch command.
            $commandBus->dispatch($command);

            $this->fail('Validation exception not thrown');
        } catch (FormValidationException $e) {
            $errors = $e->getErrors();
            $this->assertCount(1, $errors);
            $this->assertEquals('Wrong value for your current password.', $errors[0]['errors'][0]);
            $this->assertEquals('currentPassword', $errors[0]['property']);
        }
    }

    public function test_logout_web_user_command(): void
    {
        $container = static::getContainer();

        $commandBus = $container->get(CommandBus::class);

        $user = self::$userSeeder->seedUser([], [], true);

        $command = new LogoutWebUserCommand($user['user']->getId());

        $response = $commandBus->dispatch($command);

        $this->assertEquals('You have successfully logged out', $response->message);
    }

    public function test_logout_app_user_device_command(): void
    {
        $container = static::getContainer();

        $commandBus = $container->get(CommandBus::class);

        // Seed an app user with two registered devices.
        $user = self::$userSeeder->seedUser([], [
            ['name' => 'iPhone 15', 'expiresAfter' => null],
            ['name' => 'iPad', 'expiresAfter' => null],
        ]);

        $tokens = $user['user']->getAuthTokens();

        $command = new LogoutAppUserDeviceCommand($tokens[1]->getId());

        $response = $commandBus->dispatch($command);

        $this->assertEquals($user['user']->getId(), $response->user->getId());

        // Check that we have removed second device token.
        $this->assertCount(1, $response->user->getAuthTokens());
        $this->assertEquals('iPhone 15', $response->user->getAuthTokens()[0]->getName());

        // TODO: check for Mercure update message...
    }

    public function test_sign_out_app_user_command(): void
    {
        $container = static::getContainer();
        $commandBus = $container->get(CommandBus::class);

        // Seed an app user with two registered devices.
        $user = self::$userSeeder->seedUser([], [
            ['name' => 'iPhone 15', 'expiresAfter' => null],
            ['name' => 'iPad', 'expiresAfter' => null],
        ]);

        $command = new SignOutAppUserCommand($user['user']->getId());

        $response = $commandBus->dispatch($command);
        $this->assertEquals($user['user']->getId(), $response->user->getId());

        // Check that we have removed all device tokens.
        $this->assertEmpty($response->user->getAuthTokens());

        // TODO: check for Mercure update message...
    }

    public function test_delete_app_user_command(): void
    {
        $container = static::getContainer();
        $commandBus = $container->get(CommandBus::class);

        $user = static::$userSeeder->seedUser([], [
            ['name' => 'iPhone 15', 'expiresAfter' => null],
        ]);

        // Delete user and auth tokens.
        $command = new DeleteAppUserCommand($user['user']->getId(), 'password');

        $response = $commandBus->dispatch($command);

        $this->assertEquals('User account deleted successfully', $response->message);

        // Check that both user and token repositories were updated.
        $userRepository = $this->getRepository(User::class);
        $users = $userRepository->findAll();
        $this->assertCount(0, $users);

        $authTokenRepository = $this->getRepository(AuthToken::class);
        $authTokens = $authTokenRepository->findAll();
        $this->assertCount(0, $authTokens);
    }

    public function test_delete_app_user_command_validation(): void
    {
        $container = static::getContainer();
        $commandBus = $container->get(CommandBus::class);

        $user = static::$userSeeder->seedUser([], [
            ['name' => 'iPhone 15', 'expiresAfter' => null],
        ]);

        // "Wrong current password" case.
        $command = new DeleteAppUserCommand($user['user']->getId(), 'wrong_password');

        try {
            $commandBus->dispatch($command);

            $this->fail('Validation exception not thrown');
        } catch (FormValidationException $e) {
            $errors = $e->getErrors();
            $this->assertCount(1, $errors);
            $this->assertEquals('Wrong value for your current password.', $errors[0]['errors'][0]);
            $this->assertEquals('password', $errors[0]['property']);
        }
    }

    public function test_request_reset_password_command(): void
    {
        $container = static::getContainer();
        $commandBus = $container->get(CommandBus::class);

        $user = static::$userSeeder->seedUser([], [], true);

        $command = new RequestResetPasswordCommand($user['user']->getEmail());

        $commandBus->dispatch($command);

        $this->transport('async')->queue()->assertNotEmpty();

        $messages = $this->transport('async')->queue()->messages();

        $this->assertInstanceOf(RequestResetPasswordCommand::class, $messages[0]);

        // Check that queued message carries correct payload.
        $this->assertEquals('test@example.com', $messages[0]->email());

        $this->transport('async')->process(1);

        $this->transport('async')->rejected()->assertEmpty();
        $this->transport('async')->queue()->assertEmpty();

        // Check that we have persisted reset token.
        $resetPasswordTokenRepository = $this->getRepository(ResetPasswordToken::class);
        $tokens = $resetPasswordTokenRepository->findAll();

        $this->assertCount(1, $tokens);

        $this->assertEmailCount(1);
        $message = $this->getMailerMessage();

        $this->assertEmailAddressContains($message, 'to', $user['user']->getEmail());
        $this->assertEmailHtmlBodyContains($message, $tokens[0]->getResetToken());
    }

    public function test_request_reset_password_command_validation(): void
    {
        $container = static::getContainer();
        $commandBus = $container->get(CommandBus::class);

        // "Invalid email" case.
        $command = new RequestResetPasswordCommand('@invalid-email');

        try {
            $commandBus->dispatch($command);

            $this->fail('Validation exception not thrown');
        } catch (ValidationFailedException $e) {
            $violations = $e->getViolations();
            $this->assertCount(1, $violations);
            $this->assertEquals('email', $violations[0]->getPropertyPath());
        }

        // Seed a soft-deleted user.
        static::$userSeeder->seedUser([
            'email' => 'deleted@example.com',
            'deleted' => true,
        ], [], true);

        $resetPasswordTokenRepository = $this->getRepository(ResetPasswordToken::class);

        // "User soft-deleted" case.
        $command = new RequestResetPasswordCommand('deleted@example.com');

        $commandBus->dispatch($command);

        $this->transport('async')->queue()->assertNotEmpty();

        $messages = $this->transport('async')->queue()->messages();

        $this->assertInstanceOf(RequestResetPasswordCommand::class, $messages[0]);

        // Check that queued message carries correct payload.
        $this->assertEquals('deleted@example.com', $messages[0]->email());

        $this->transport('async')->process(1);

        $this->transport('async')->rejected()->assertEmpty();
        $this->transport('async')->queue()->assertEmpty();

        // Check that we haven't created a reset token.
        $tokens = $resetPasswordTokenRepository->findAll();

        $this->assertCount(0, $tokens);

        // Check that no emails were sent.
        $this->assertEmailCount(0);

        // "Email not registered" case.
        $command = new RequestResetPasswordCommand('not-existing@example.com');

        $commandBus->dispatch($command);

        $this->transport('async')->queue()->assertNotEmpty();

        $messages = $this->transport('async')->queue()->messages();

        $this->assertInstanceOf(RequestResetPasswordCommand::class, $messages[0]);

        // Check that queued message carries correct payload.
        $this->assertEquals('not-existing@example.com', $messages[0]->email());

        $this->transport('async')->process(1);

        $this->transport('async')->rejected()->assertEmpty();
        $this->transport('async')->queue()->assertEmpty();

        // Check that we haven't created a reset token.
        $tokens = $resetPasswordTokenRepository->findAll();

        $this->assertCount(0, $tokens);

        // Check that no emails were sent.
        $this->assertEmailCount(0);
    }

    public function test_repeated_request_reset_password_command(): void
    {
        $container = static::getContainer();
        $commandBus = $container->get(CommandBus::class);

        $user = static::$userSeeder->seedUser([], [], true);

        $command = new RequestResetPasswordCommand($user['user']->getEmail());

        $commandBus->dispatch($command);

        $this->transport('async')->queue()->assertNotEmpty();

        $messages = $this->transport('async')->queue()->messages();

        $this->assertInstanceOf(RequestResetPasswordCommand::class, $messages[0]);

        // Check that queued message carries correct payload.
        $this->assertEquals('test@example.com', $messages[0]->email());

        $this->transport('async')->process(1);

        $this->transport('async')->rejected()->assertEmpty();
        $this->transport('async')->queue()->assertEmpty();

        // Check that we have persisted reset token.
        $resetPasswordTokenRepository = $this->getRepository(ResetPasswordToken::class);
        $tokens = $resetPasswordTokenRepository->findAll();

        $this->assertCount(1, $tokens);

        // Repeat the command (user makes another attempt to request reset token).
        $commandBus->dispatch($command);

        $this->transport('async')->queue()->assertNotEmpty();

        $this->transport('async')->process(1);

        $this->transport('async')->rejected()->assertEmpty();
        $this->transport('async')->queue()->assertEmpty();

        // Check that we have removed existing token before saving the new one.
        $tokens = $resetPasswordTokenRepository->findAll();

        $this->assertCount(1, $tokens);
    }

    public function test_perform_reset_password_command(): void
    {
        $container = static::getContainer();
        $commandBus = $container->get(CommandBus::class);

        $user = static::$userSeeder->seedUser([], [], true);
        $passwordResetToken = static::$userSeeder->seedPasswordResetToken($user['user'], 'test-token');

        $command = new PerformResetPasswordCommand($user['user']->getEmail(), $passwordResetToken->getResetToken(),
            'password', 'password');

        /** @var \App\Module\Shared\Application\UserResponse $response */
        $response = $commandBus->dispatch($command);

        $this->assertEquals($user['user']->getId(), $response->user->getId());

        // Check that we have removed reset token after single use.
        $resetPasswordTokenRepository = $this->getRepository(ResetPasswordToken::class);
        $tokens = $resetPasswordTokenRepository->findAll();

        $this->assertCount(0, $tokens);
    }

    public function test_perform_reset_password_command_validation(): void
    {
        $container = static::getContainer();
        $commandBus = $container->get(CommandBus::class);

        // "Empty email" case.
        $command = new PerformResetPasswordCommand('', 'test-token', 'password', 'password');

        try {
            $commandBus->dispatch($command);

            $this->fail('Validation exception not thrown');
        } catch (ValidationFailedException $e) {
            $violations = $e->getViolations();
            $this->assertCount(1, $violations);
            $this->assertEquals('email', $violations[0]->getPropertyPath());
        }

        // "Invalid email" case.
        $command = new PerformResetPasswordCommand('@invalid-email', 'test-token', 'password', 'password');
        try {
            $commandBus->dispatch($command);

            $this->fail('Validation exception not thrown');
        } catch (ValidationFailedException $e) {
            $violations = $e->getViolations();
            $this->assertCount(1, $violations);
            $this->assertEquals('email', $violations[0]->getPropertyPath());
        }

        // "Empty reset token" case.
        try {
            new PerformResetPasswordCommand('valid@email.com', '', 'password', 'password');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertCount(1, $errors);
            $this->assertEquals('This value should not be blank.', $errors[0]['errors'][0]);
            $this->assertEquals('resetToken', $errors[0]['property']);
        }
    }
}
