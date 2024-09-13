<?php
declare(strict_types=1);

namespace App\Tests\Functional;

use App\Module\User\Domain\AuthToken;
use App\Module\User\Domain\RefreshToken;
use App\Module\User\Domain\User;
use App\Tests\DatabaseTestCase;

class WebUserTest extends DatabaseTestCase
{
    public function test_we_can_register_a_user(): void
    {
        $client = $this->getAnonymousClient();

        $client->jsonRequest('POST', '/api/web/register', [
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'first_name' => 'First',
            'last_name' => 'Last',
        ]);

        $response = json_decode($client->getResponse()->getContent());

        $this->assertResponseIsSuccessful();

        $this->assertEquals('test@example.com', $response->user->email);

        // No auth tokens in "web" registration flow.
        $this->assertCount(0, $response->user->auth_tokens);

        $userRepository = $this->getRepository(User::class);
        $users = $userRepository->findAll();

        $this->assertCount(1, $users);
        $this->assertEquals('test@example.com', $users[0]->getEmail());

        $roles = $users[0]->getRoles();
        $this->assertCount(1, $roles);

        $this->assertCount(0, $users[0]->getAuthTokens());

        // Check that we didn't create user device token in "web" login flow.
        $tokenRepository = $this->getRepository(AuthToken::class);
        $tokens = $tokenRepository->findAll();

        $this->assertCount(0, $tokens);
    }

    public function test_register_user_error_password_confirmation(): void
    {
        $client = $this->getAnonymousClient();

        $client->jsonRequest('POST', '/api/web/register', [
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'wrong-password',
            'first_name' => 'First',
            'last_name' => 'Last',
        ]);

        $this->assertResponseStatusCodeSame(422);

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Validation failed.', $response->message);

        $this->assertCount(1, $response->errors);

        $this->assertCount(1, $response->errors[0]->errors);
        $this->assertEquals('passwordConfirmation', $response->errors[0]->property);
        $this->assertEquals('User', $response->errors[0]->context);
        $this->assertEquals('Passwords do not match.', $response->errors[0]->errors[0]);
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

        $client->jsonRequest('POST', '/api/web/login_check', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $this->assertResponseIsSuccessful();

        $response = json_decode($client->getResponse()->getContent());

        $this->assertNotEmpty($response->token);
        $this->assertNotEmpty($response->refresh_token);

        $userRepository = $this->getRepository(User::class);
        $users = $userRepository->findAll();
        $this->assertNotEmpty($users[0]->getPassword());
    }

    public function test_login_user_error_invalid_credentials(): void
    {
        $client = $this->getAnonymousClient();

        static::$userSeeder->seedUser([
            'email' => 'test@example.com',
            'password' => 'password',
            'firstName' => 'First',
            'lastName' => 'Last',
            'roles' => ['ROLE_USER'],
        ]);

        $client->jsonRequest('POST', '/api/web/login_check', [
            'email' => 'test34@example.com',
            'password' => 'wrong-password',
        ]);

        $this->assertResponseStatusCodeSame(401);
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Invalid credentials.', $response->message);
    }

    public function test_we_can_refresh_token_jwt(): void
    {
        $client = $this->getAnonymousClient();

        $user = static::$userSeeder->seedUser([
            'email' => 'test@example.com',
            'password' => 'password',
            'firstName' => 'First',
            'lastName' => 'Last',
            'roles' => ['ROLE_USER'],
        ], [], true);

        // $token = $user['jwt_token'];

        $client->jsonRequest('POST', '/api/web/token/refresh', [
            'refresh_token' => $user['refresh_token'],
        ], [// 'HTTP_Authorization' => 'Bearer '.$token,
        ]);

        $this->assertResponseIsSuccessful();
        $response = json_decode($client->getResponse()->getContent());

        $this->assertNotEmpty($response->token);

        // Check that we have set single_use option for refresh token.
        $this->assertNotEquals($user['refresh_token'], $response->refresh_token);
    }

    public function test_we_can_update_a_user(): void
    {
        $user = static::$userSeeder->seedUser([
            'email' => 'test@example.com',
            'firstName' => 'First',
            'lastName' => 'Last',
        ], [], true);

        $client = $this->getAuthenticatedClient($user);

        $client->jsonRequest('PATCH', '/api/web/account/me/update', [
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
            // The same user has also a registered app device.
            ['name' => 'iPhone 15', 'expiresAfter' => null],
        ], true);

        $client = $this->getAuthenticatedClient($user);

        $client->jsonRequest('PATCH', '/api/web/account/me/change-password', [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $this->assertResponseIsSuccessful();

        $response = json_decode($client->getResponse()->getContent());

        // Change password action doesn't affect auth tokens.
        $this->assertCount(1, $response->user->auth_tokens);
    }

    public function test_a_user_can_logout(): void
    {
        $user = static::$userSeeder->seedUser([
            'email' => 'test@test.com',
        ], [], true);

        $client = $this->getAuthenticatedClient($user);

        $client->jsonRequest('POST', '/api/web/account/me/logout', [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $this->assertResponseIsSuccessful();

        $refreshTokenRepository = $this->getRepository(RefreshToken::class);
        $tokens = $refreshTokenRepository->findAll();
        $this->assertCount(0, $tokens);
    }
}


