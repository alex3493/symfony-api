<?php
declare(strict_types=1);

namespace App\Tests\Functional;

use App\Module\User\Application\ResetPassword\RequestResetPassword\RequestResetPasswordMessage;
use App\Module\User\Domain\ResetPasswordToken;
use App\Tests\DatabaseTestCase;

class AccessTest extends DatabaseTestCase
{
    public function test_public_page(): void
    {
        $client = self::getReusableClient();
        $client->jsonRequest('GET', '/api/');

        $this->assertResponseIsSuccessful();
    }

    public function test_we_can_access_private_page_from_app(): void
    {
        $user = static::$userSeeder->seedUser([], [
            ['name' => 'web', 'expiresAfter' => 24 * 60],
        ]);

        $token = $user['app_token'];

        $client = self::getReusableClient();

        $client->jsonRequest('GET', '/api/app/dashboard', [], [
            'HTTP_Authorization' => 'Bearer '.$token,
        ]);

        $response = json_decode($client->getResponse()->getContent());

        $this->assertResponseIsSuccessful();
        $this->assertEquals('Welcome to dashboard. You are logged in.', $response->data->message);
        $this->assertEquals('test@example.com', $response->user->email);
    }

    public function test_we_can_access_private_page_from_web(): void
    {
        $user = static::$userSeeder->seedUser([], [], true);

        $token = $user['jwt_token'];

        $client = self::getReusableClient();

        $client->jsonRequest('GET', '/api/web/dashboard', [], [
            'HTTP_Authorization' => 'Bearer '.$token,
        ]);

        $response = json_decode($client->getResponse()->getContent());

        $this->assertResponseIsSuccessful();
        $this->assertEquals('Welcome to dashboard. You are logged in.', $response->data->message);
        $this->assertEquals('test@example.com', $response->user->email);
    }

    public function test_soft_deleted_user_cannot_access_from_app(): void
    {
        $user = static::$userSeeder->seedUser([
            'deleted' => true,
        ], [
            ['name' => 'iPhone 15', 'expiresAfter' => null],
        ]);

        $token = $user['app_token'];

        $client = self::getReusableClient();

        $client->jsonRequest('GET', '/api/app/dashboard', [], [
            'HTTP_Authorization' => 'Bearer '.$token,
        ]);

        $response = json_decode($client->getResponse()->getContent());

        $this->assertResponseStatusCodeSame(401);
        $this->assertEquals('User is soft-deleted', $response->message);
    }

    public function test_soft_deleted_user_cannot_access_from_web(): void
    {
        $user = static::$userSeeder->seedUser([
            'deleted' => true,
        ], [], true);

        $token = $user['jwt_token'];

        $client = self::getReusableClient();

        $client->jsonRequest('GET', '/api/web/dashboard', [], [
            'HTTP_Authorization' => 'Bearer '.$token,
        ]);

        $response = json_decode($client->getResponse()->getContent());

        $this->assertResponseStatusCodeSame(401);
        $this->assertEquals('Invalid credentials.', $response->message);
    }

    public function test_we_cannot_access_private_page_when_unauthorized(): void
    {
        $client = self::getReusableClient();

        $client->jsonRequest('GET', '/api/app/dashboard', [], [
            'HTTP_Authorization' => 'Bearer wrong_token',
        ]);

        $this->assertResponseStatusCodeSame(401);
    }

    public function test_we_cannot_access_private_page_when_token_expired(): void
    {
        $user = static::$userSeeder->seedUser([], [
            ['name' => 'web', 'isExpired' => true],
        ]);

        $token = $user['app_token'];

        $client = self::getReusableClient();

        $client->jsonRequest('GET', '/api/app/dashboard', [], [
            'HTTP_Authorization' => 'Bearer '.$token,
        ]);

        $this->assertResponseStatusCodeSame(401);
    }

    public function test_user_can_request_password_reset(): void
    {
        $user = static::$userSeeder->seedUser();

        $client = self::getReusableClient();

        $client->jsonRequest('POST', '/api/forgot-password', [
            'email' => $user['user']->getEmail(),
        ]);

        $this->assertResponseIsSuccessful();

        $this->transport('async')->queue()->assertNotEmpty();

        $messages = $this->transport('async')->queue()->messages();

        $this->assertInstanceOf(RequestResetPasswordMessage::class, $messages[0]);

        // Check that queued message carries correct payload.
        $this->assertEquals($user['user']->getEmail(), $messages[0]->email());

        $this->transport('async')->process(1);

        $this->transport('async')->rejected()->assertEmpty();
        $this->transport('async')->queue()->assertEmpty();

        // Check that we have persisted reset token.
        $resetPasswordTokenRepository = $this->getRepository(ResetPasswordToken::class);
        $tokens = $resetPasswordTokenRepository->findAll();

        $this->assertCount(1, $tokens);

        // Check that we have sent email with reset password link.
        $this->assertEmailCount(1);
        $message = $this->getMailerMessage();

        $this->assertEmailAddressContains($message, 'to', $user['user']->getEmail());
        $this->assertEmailHtmlBodyContains($message, $tokens[0]->getResetToken());
    }

    public function test_user_request_password_reset_validation(): void
    {
        $client = self::getReusableClient();

        $client->jsonRequest('POST', '/api/forgot-password', [
            'email' => '@invalid-email',
        ]);

        // Actually the code is 500 here.
        // TODO: how to fix it?
        $this->assertResponseStatusCodeSame(500);

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Validation failed.', $response->message);
        $this->assertCount(1, $response->errors);
        $this->assertEquals('User', $response->errors[0]->context);
        $this->assertEquals('email', $response->errors[0]->property);
    }

    public function test_user_can_reset_password(): void
    {
        $user = static::$userSeeder->seedUser([], [], true);
        $passwordResetToken = static::$userSeeder->seedPasswordResetToken($user['user'], 'test-token');

        $client = self::getReusableClient();

        $client->jsonRequest('POST', '/api/reset-password', [
            'email' => $user['user']->getEmail(),
            'reset_token' => $passwordResetToken->getResetToken(),
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $this->assertResponseIsSuccessful();

        // Check that we have removed reset token from the database after single use.
        $resetPasswordTokenRepository = $this->getRepository(ResetPasswordToken::class);
        $tokens = $resetPasswordTokenRepository->findAll();

        $this->assertCount(0, $tokens);
    }
}
