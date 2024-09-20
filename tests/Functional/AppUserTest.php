<?php
declare(strict_types=1);

namespace App\Tests\Functional;

use App\Module\User\Domain\AuthToken;
use App\Module\User\Domain\User;
use App\Tests\DatabaseTestCase;

class AppUserTest extends DatabaseTestCase
{
    public function test_we_can_register_a_user(): void
    {
        $client = $this->getAnonymousClient();

        $client->jsonRequest('POST', '/api/app/register', [
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'first_name' => 'First',
            'last_name' => 'Last',
            'device_name' => 'iPhone 15',
        ]);

        $response = json_decode($client->getResponse()->getContent());

        $this->assertResponseIsSuccessful();

        $this->assertEquals('test@example.com', $response->user->email);

        $userRepository = $this->getRepository(User::class);
        $users = $userRepository->findAll();

        $this->assertCount(1, $users);
        $this->assertEquals('test@example.com', $users[0]->getEmail());

        $roles = $users[0]->getRoles();
        $this->assertCount(1, $roles);

        $tokenRepository = $this->getRepository(AuthToken::class);
        $tokens = $tokenRepository->findAll();

        $this->assertCount(1, $tokens);

        $this->assertEquals('iPhone 15', $tokens[0]->getName());
        $this->assertEquals('test@example.com', $tokens[0]->getUser()->getEmail());
    }

    public function test_register_a_user_error_duplicate_email(): void
    {
        $client = $this->getAnonymousClient();

        static::$userSeeder->seedUser([
            'email' => 'test@example.com',
            'password' => 'password',
            'first_name' => 'First',
            'last_name' => 'Last',
            'roles' => ['ROLE_USER'],
        ]);

        $client->jsonRequest('POST', '/api/app/register', [
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'first_name' => 'First',
            'last_name' => 'Last',
            'device_name' => 'iPhone 15',
        ]);

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Validation failed.', $response->message);
        $this->assertResponseStatusCodeSame(409);
        $this->assertCount(1, $response->errors);
        $this->assertEquals('email', $response->errors[0]->property);
    }

    public function test_register_a_user_error_invalid_email(): void
    {
        $client = $this->getAnonymousClient();

        $client->jsonRequest('POST', '/api/app/register', [
            'email' => 'invalid.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'first_name' => 'First',
            'last_name' => 'Last',
            'device_name' => 'iPhone 15',
        ]);

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Validation failed.', $response->message);

        $this->assertResponseStatusCodeSame(422);
        $this->assertCount(1, $response->errors);
        $this->assertEquals('email', $response->errors[0]->property);
    }

    public function test_register_user_error_missing_password(): void
    {
        $client = $this->getAnonymousClient();

        $client->jsonRequest('POST', '/api/app/register', [
            'email' => 'test@example.com',
            'password' => null,
            'password_confirmation' => 'password',
            'first_name' => 'First',
            'last_name' => 'Last',
            'device_name' => 'iPhone 15',
        ]);

        $this->assertResponseStatusCodeSame(400);
    }

    public function test_register_user_error_blank_password(): void
    {
        $client = $this->getAnonymousClient();

        $client->jsonRequest('POST', '/api/app/register', [
            'email' => 'test@example.com',
            'password' => '',
            'password_confirmation' => 'password',
            'first_name' => 'First',
            'last_name' => 'Last',
            'device_name' => 'iPhone 15',
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function test_register_user_error_invalid_email(): void
    {
        $client = $this->getAnonymousClient();

        $client->jsonRequest('POST', '/api/app/register', [
            'email' => 'invalid-email.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'first_name' => 'First',
            'last_name' => 'Last',
            'device_name' => 'iPhone 15',
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function test_we_can_login_a_user(): void
    {
        $client = $this->getAnonymousClient();

        static::$userSeeder->seedUser([
            'email' => 'test@example.com',
            'password' => 'password',
            'firstName' => 'First',
            'lastName' => 'Last',
            'roles' => ['ROLE_USER'],
        ]);

        $client->jsonRequest('POST', '/api/app/login', [
            'email' => 'test@example.com',
            'password' => 'password',
            'device_name' => 'iPhone 15',
        ]);

        $this->assertResponseIsSuccessful();

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('test@example.com', $response->user->email);
        $this->assertNotEmpty($response->token);

        $tokenRepository = $this->getRepository(AuthToken::class);
        $tokens = $tokenRepository->findAll();

        $this->assertCount(1, $tokens);

        $this->assertEquals('iPhone 15', $tokens[0]->getName());
        $this->assertEquals('test@example.com', $tokens[0]->getUser()->getEmail());

        // TODO: check for Mercure update message...
    }

    public function test_login_error_invalid_credentials(): void
    {
        $client = $this->getAnonymousClient();

        static::$userSeeder->seedUser([
            'email' => 'test@example.com',
            'password' => 'password',
            'firstName' => 'First',
            'lastName' => 'Last',
            'roles' => ['ROLE_USER'],
        ]);

        $client->jsonRequest('POST', '/api/app/login', [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
            'deviceName' => 'iPhone 15',
        ]);

        $this->assertResponseStatusCodeSame(401);

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Invalid credentials', $response->message);
        $this->assertFalse(isset($response->token));
    }

    public function test_we_can_logout_from_device(): void
    {
        $user = static::$userSeeder->seedUser([], [
            ['name' => 'iPhone 15', 'expiresAfter' => null],
            ['name' => 'iPad', 'expiresAfter' => null],
        ]);

        $tokens = $user['user']->getAuthTokens();

        $client = $this->getAuthenticatedClient($user, false, 'iPhone 15');

        $client->jsonRequest('DELETE', '/api/app/account/logout/'.$tokens[1]->getId());

        $this->assertResponseIsSuccessful();

        $response = json_decode($client->getResponse()->getContent());
        $this->assertCount(1, $response->user->auth_tokens);
        $this->assertEquals('iPhone 15', $response->user->auth_tokens[0]->name);

        $tokenRepository = $this->getRepository(AuthToken::class);
        $tokens = $tokenRepository->findAll();
        $this->assertCount(1, $tokens);
        $this->assertEquals('iPhone 15', $tokens[0]->getName());

        $userRepository = $this->getRepository(User::class);
        $users = $userRepository->findAll();
        $this->assertNotEmpty($users[0]->getPassword());

        // TODO: check for Mercure update message...
    }

    public function test_logout_fails_if_device_token_not_found()
    {
        $user = static::$userSeeder->seedUser([], [
            ['name' => 'iPhone 15', 'expiresAfter' => null],
        ]);

        $client = $this->getAuthenticatedClient($user, false);

        $client->jsonRequest('DELETE', '/api/app/account/logout/invalid-token');

        $this->assertResponseStatusCodeSame(404);

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals('Token not found', $response->message);
    }

    public function test_we_can_sign_out_a_user(): void
    {
        $user = static::$userSeeder->seedUser([], [
            ['name' => 'iPhone 15', 'expiresAfter' => null],
            ['name' => 'iPad', 'expiresAfter' => null],
        ]);

        $client = $this->getAuthenticatedClient($user, false, 'iPhone 15');

        $client->jsonRequest('POST', '/api/app/account/me/sign-out');

        $this->assertResponseIsSuccessful();

        $response = json_decode($client->getResponse()->getContent());
        $this->assertCount(0, $response->user->auth_tokens);

        $tokenRepository = $this->getRepository(AuthToken::class);
        $tokens = $tokenRepository->findAll();
        $this->assertCount(0, $tokens);

        $userRepository = $this->getRepository(User::class);
        $users = $userRepository->findAll();
        $this->assertNotEmpty($users[0]->getPassword());

        // TODO: check for Mercure update message...
    }

    public function test_we_can_update_a_user(): void
    {
        $user = static::$userSeeder->seedUser([
            'email' => 'test@example.com',
            'firstName' => 'First',
            'lastName' => 'Last',
        ], [
            ['name' => 'iPhone 15', 'expiresAfter' => null],
            ['name' => 'iPad', 'expiresAfter' => null],
        ]);

        $client = $this->getAuthenticatedClient($user, false);

        $client->jsonRequest('PATCH', '/api/app/account/me/update', [
            'email' => 'updated@example.com',
            'first_name' => 'First Modified',
            'last_name' => 'Last Modified',
        ]);

        $this->assertResponseIsSuccessful();

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('updated@example.com', $response->user->email);
        $this->assertEquals('First Modified', $response->user->first_name);
        $this->assertEquals('Last Modified', $response->user->last_name);

        $userRepository = $this->getRepository(User::class);
        $users = $userRepository->findAll();
        $this->assertNotEmpty($users[0]->getPassword());
    }

    public function test_a_user_can_change_password()
    {
        $user = static::$userSeeder->seedUser([
            'email' => 'test@test.com',
        ], [
            ['name' => 'iPhone 15', 'expiresAfter' => null],
        ]);

        $client = $this->getAuthenticatedClient($user, false);

        $client->jsonRequest('PATCH', '/api/app/account/me/change-password', [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $this->assertResponseIsSuccessful();

        $response = json_decode($client->getResponse()->getContent());

        // Change password action doesn't affect auth tokens.
        $this->assertCount(1, $response->user->auth_tokens);
    }

    public function test_change_password_fails_if_invalid_current_password(): void
    {
        $user = static::$userSeeder->seedUser([
            'email' => 'test@test.com',
        ], [
            ['name' => 'iPhone 15', 'expiresAfter' => null],
        ]);

        $client = $this->getAuthenticatedClient($user, false);

        $client->jsonRequest('PATCH', '/api/app/account/me/change-password', [
            'current_password' => 'wrong-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $this->assertResponseStatusCodeSame(422);

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals('Invalid credentials', $response->message);

        $this->assertCount(1, $response->errors[0]->errors);
        $this->assertEquals('currentPassword', $response->errors[0]->property);
        $this->assertEquals('User', $response->errors[0]->context);
        $this->assertEquals('Wrong value for your current password.', $response->errors[0]->errors[0]);
    }

    public function test_change_password_fails_if_wrong_password_confirmation(): void
    {
        $user = static::$userSeeder->seedUser([
            'email' => 'test@test.com',
        ], [
            ['name' => 'iPhone 15', 'expiresAfter' => null],
        ]);

        $client = $this->getAuthenticatedClient($user, false);

        $client->jsonRequest('PATCH', '/api/app/account/me/change-password', [
            'current_password' => '',
            'password' => 'new-password',
            'password_confirmation' => 'wrong-new-password',
        ]);

        $this->assertResponseStatusCodeSame(422);

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals('Validation failed.', $response->message);

        $this->assertCount(2, $response->errors);

        $this->assertCount(1, $response->errors[0]->errors);
        $this->assertEquals('currentPassword', $response->errors[0]->property);
        $this->assertEquals('User', $response->errors[0]->context);
        $this->assertEquals('This value should not be blank.', $response->errors[0]->errors[0]);

        $this->assertCount(1, $response->errors[1]->errors);
        $this->assertEquals('passwordConfirmation', $response->errors[1]->property);
        $this->assertEquals('User', $response->errors[1]->context);
        $this->assertEquals('Passwords do not match.', $response->errors[1]->errors[0]);
    }

    public function test_a_user_can_delete_account()
    {
        $user = static::$userSeeder->seedUser([
            'email' => 'test@test.com',
        ], [
            ['name' => 'iPhone 15', 'expiresAfter' => null],
        ]);

        $client = $this->getAuthenticatedClient($user, false);

        $client->jsonRequest('POST', '/api/app/account/me/delete-account', [
            'password' => 'password',
        ]);

        $this->assertResponseIsSuccessful();
    }
}
