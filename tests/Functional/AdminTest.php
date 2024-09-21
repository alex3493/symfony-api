<?php
declare(strict_types=1);

namespace App\Tests\Functional;

use App\Module\Shared\Domain\Message\MercureUpdateMessage;
use App\Module\User\Domain\Event\UserCreatedDomainEvent;
use App\Module\User\Domain\Event\UserEmailChangedDomainEvent;
use App\Module\User\Domain\Event\UserRestoredDomainEvent;
use App\Module\User\Domain\Event\UserSoftDeletedDomainEvent;
use App\Module\User\Domain\User;
use App\Tests\DatabaseTestCase;

class AdminTest extends DatabaseTestCase
{
    public function test_admin_can_list_users(): void
    {
        $user = static::$userSeeder->seedUser([
            'name' => 'Admin',
            'roles' => ['ROLE_ADMIN'],
        ], [], true);

        $users = [];

        for ($i = 0; $i < 20; $i++) {
            $users[] = self::$userSeeder->seedUser([
                'name' => 'User '.$i,
                'email' => 'user'.$i.'@example.com',
            ], [
                ['name' => 'iPhone 15', 'expiresAfter' => null],
            ], false)['user'];
        }

        for ($i = 20; $i < 30; $i++) {
            $users[] = self::$userSeeder->seedUser([
                'name' => 'User '.$i,
                'email' => 'user'.$i.'@example.com',
                'deleted' => true,
            ])['user'];
        }

        $client = $this->getAuthenticatedClient($user);

        $client->jsonRequest('GET', '/api/admin/users?page=1&limit=10&orderBy=name&orderType=asc');

        $response = json_decode($client->getResponse()->getContent());

        $this->assertResponseIsSuccessful();

        // Check that we have set pagination limit.
        $this->assertCount(10, $response->items);
        // We have seeded 31 users:
        // - admin
        // - 20 users coming from mobile app (device token auth)
        // - 10 users coming from web SPA (JWT auth), but these are soft-deleted.
        // We only list active users.
        $this->assertEquals(21, $response->totalItems);
        // Check that we calculate total pages.
        $this->assertEquals(3, $response->totalPages);
    }

    public function test_admin_can_create_user(): void
    {
        $user = static::$userSeeder->seedUser([
            'roles' => ['ROLE_ADMIN'],
        ], [], true);

        $client = $this->getAuthenticatedClient($user);

        $client->jsonRequest('POST', '/api/admin/users', [
            'email' => 'user@example.com',
            'password' => 'password',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $response = json_decode($client->getResponse()->getContent());

        $this->assertResponseIsSuccessful();

        $this->assertNotEmpty($response->user->id);
        $this->assertEquals('John', $response->user->first_name);
    }

    public function test_admin_can_create_admin_user(): void
    {
        $user = static::$userSeeder->seedUser([
            'roles' => ['ROLE_ADMIN'],
        ], [], true);

        $client = $this->getAuthenticatedClient($user);

        $client->jsonRequest('POST', '/api/admin/users', [
            'email' => 'user@example.com',
            'password' => 'password',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'role' => 'ROLE_ADMIN',
        ]);

        $response = json_decode($client->getResponse()->getContent());

        $this->assertResponseIsSuccessful();

        $this->assertNotEmpty($response->user->id);
        $this->assertEquals('John', $response->user->first_name);
        $this->assertEquals('Doe', $response->user->last_name);
        $this->assertEquals('ROLE_ADMIN', $response->user->role);

        $this->transport('async')->queue()->assertNotEmpty();

        $messages = $this->transport('async')->queue()->messages();

        // Check Mercure update messages.
        $this->assertInstanceOf(UserCreatedDomainEvent::class, $messages[0]);
        $this->assertInstanceOf(MercureUpdateMessage::class, $messages[1]);
        $this->assertEquals('user_create', $messages[1]->getPayload()['action']);
        $this->assertEquals($user['user']->getEmail(), $messages[1]->getPayload()['causer']);
        $this->assertEquals('users::update', $messages[1]->getTopic());

        $this->assertEquals('user@example.com', $messages[0]->toPrimitives()['user']->getEmail());
        $this->assertEquals('user@example.com', $messages[1]->getPayload()['item']['email']);

        $this->assertEquals($response->user->id, $messages[0]->toPrimitives()['user']->getId());

        $this->assertEquals($messages[0]->toPrimitives()['user']->getId(), $messages[1]->getPayload()['item']['id']);

        $this->transport('async')->process(2);

        $this->transport('async')->rejected()->assertEmpty();
        $this->transport('async')->queue()->assertEmpty();
    }

    public function test_admin_can_update_user(): void
    {
        $user = static::$userSeeder->seedUser([
            'email' => 'admin@example.com',
            'roles' => ['ROLE_ADMIN'],
        ], [], true);

        $testUser = static::$userSeeder->seedUser();

        $client = $this->getAuthenticatedClient($user);

        $client->jsonRequest('PATCH', '/api/admin/user/'.$testUser['user']->getId(), [
            'email' => 'updated@example.com',
            'password' => 'new_password',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'role' => 'ROLE_ADMIN',
        ]);

        $response = json_decode($client->getResponse()->getContent());

        $this->assertResponseIsSuccessful();

        $this->assertNotEmpty($response->user->id);
        $this->assertEquals('updated@example.com', $response->user->email);
        $this->assertEquals('Jane', $response->user->first_name);
        $this->assertEquals('Doe', $response->user->last_name);
        $this->assertEquals('ROLE_ADMIN', $response->user->role);

        // We have updated user email, so we expect messages in queue.
        $this->transport('async')->queue()->assertNotEmpty();

        $messages = $this->transport('async')->queue()->messages();

        $this->assertInstanceOf(UserEmailChangedDomainEvent::class, $messages[0]);

        // Check that queued message carries correct payload.
        $this->assertEquals('test@example.com', $messages[0]->getOldEmail());
        $this->assertEquals('updated@example.com', $messages[0]->getNewEmail());

        // Check Mercure update messages.
        $this->assertInstanceOf(MercureUpdateMessage::class, $messages[1]);
        $this->assertEquals('user_update', $messages[1]->getPayload()['action']);
        $this->assertEquals($user['user']->getEmail(), $messages[1]->getPayload()['causer']);
        $this->assertEquals('users::update', $messages[1]->getTopic());

        $this->assertInstanceOf(MercureUpdateMessage::class, $messages[2]);
        $this->assertEquals('user_update', $messages[2]->getPayload()['action']);
        $this->assertEquals($user['user']->getEmail(), $messages[2]->getPayload()['causer']);
        $this->assertEquals('user::update::'.$response->user->id, $messages[2]->getTopic());

        $this->transport('async')->process(3);

        $this->transport('async')->rejected()->assertEmpty();
        $this->transport('async')->queue()->assertEmpty();
    }

    public function test_admin_update_user_validation(): void
    {
        $user = static::$userSeeder->seedUser([
            'email' => 'admin@example.com',
            'roles' => ['ROLE_ADMIN'],
        ], [], true);

        $testUser = static::$userSeeder->seedUser();

        $client = $this->getAuthenticatedClient($user);

        $client->jsonRequest('PATCH', '/api/admin/user/'.$testUser['user']->getId(), [
            'email' => '@invalid-email',
            'password' => 'new_password',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'role' => 'ROLE_ADMIN',
        ]);

        $this->assertResponseStatusCodeSame(422);

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Validation failed.', $response->message);
        $this->assertCount(1, $response->errors);
        $this->assertEquals('User', $response->errors[0]->context);
        $this->assertEquals('email', $response->errors[0]->property);
    }

    public function test_admin_can_soft_delete_user(): void
    {
        $user = static::$userSeeder->seedUser([
            'email' => 'admin@example.com',
            'roles' => ['ROLE_ADMIN'],
        ], [], true);

        $testUser = static::$userSeeder->seedUser();

        $client = $this->getAuthenticatedClient($user);

        $client->jsonRequest('PATCH', '/api/admin/user/delete/'.$testUser['user']->getId());

        $response = json_decode($client->getResponse()->getContent());

        $this->assertResponseIsSuccessful();

        $this->assertNotEmpty($response->user->deleted_at);

        // We have soft-deleted user, so we expect messages in queue.
        $this->transport('async')->queue()->assertNotEmpty();

        $messages = $this->transport('async')->queue()->messages();

        $this->assertInstanceOf(UserSoftDeletedDomainEvent::class, $messages[0]);

        // Check Mercure update messages.
        $this->assertInstanceOf(MercureUpdateMessage::class, $messages[1]);
        $this->assertEquals('user_soft_delete', $messages[1]->getPayload()['action']);
        $this->assertEquals($user['user']->getEmail(), $messages[1]->getPayload()['causer']);
        $this->assertEquals('users::update', $messages[1]->getTopic());

        $this->assertInstanceOf(MercureUpdateMessage::class, $messages[2]);
        $this->assertEquals('user_soft_delete', $messages[2]->getPayload()['action']);
        $this->assertEquals($user['user']->getEmail(), $messages[2]->getPayload()['causer']);
        $this->assertEquals('user::update::'.$response->user->id, $messages[2]->getTopic());

        $this->transport('async')->process(3);

        $this->transport('async')->rejected()->assertEmpty();
        $this->transport('async')->queue()->assertEmpty();
    }

    public function test_admin_can_restore_user(): void
    {
        $user = static::$userSeeder->seedUser([
            'email' => 'admin@example.com',
            'roles' => ['ROLE_ADMIN'],
        ], [], true);

        $testUser = static::$userSeeder->seedUser([
            'deleted' => true,
        ]);

        $client = $this->getAuthenticatedClient($user);

        $client->jsonRequest('PATCH', '/api/admin/user/restore/'.$testUser['user']->getId());

        $response = json_decode($client->getResponse()->getContent());

        $this->assertResponseIsSuccessful();

        $this->assertEmpty($response->user->deleted_at);

        // We have restored soft-deleted user, so we expect messages in queue.
        $this->transport('async')->queue()->assertNotEmpty();

        $messages = $this->transport('async')->queue()->messages();

        $this->assertInstanceOf(UserRestoredDomainEvent::class, $messages[0]);

        // Check Mercure update messages.
        $this->assertInstanceOf(MercureUpdateMessage::class, $messages[1]);
        $this->assertEquals('user_restore', $messages[1]->getPayload()['action']);
        $this->assertEquals($user['user']->getEmail(), $messages[1]->getPayload()['causer']);
        $this->assertEquals('users::update', $messages[1]->getTopic());


        $this->transport('async')->process(2);

        $this->transport('async')->rejected()->assertEmpty();
        $this->transport('async')->queue()->assertEmpty();
    }

    public function test_admin_can_force_delete_user(): void
    {
        $user = static::$userSeeder->seedUser([
            'email' => 'admin@example.com',
            'roles' => ['ROLE_ADMIN'],
        ], [], true);

        $testUser = static::$userSeeder->seedUser();

        $client = $this->getAuthenticatedClient($user);

        $client->jsonRequest('DELETE', '/api/admin/user/'.$testUser['user']->getId());

        $response = json_decode($client->getResponse()->getContent());

        $this->assertResponseIsSuccessful();

        $this->assertEquals('User successfully deleted.', $response->message);

        $userRepository = $this->getRepository(User::class);

        // Check that we have deleted the user. Just in case, disable the filter.
        $this->getEntityManager()->getFilters()->disable('softDeleted');

        $users = $userRepository->findAll();
        $this->assertCount(1, $users);

        $messages = $this->transport('async')->queue()->messages();

        // Check Mercure update messages.
        $this->assertInstanceOf(MercureUpdateMessage::class, $messages[0]);
        $this->assertEquals('user_force_delete', $messages[0]->getPayload()['action']);
        $this->assertEquals($user['user']->getEmail(), $messages[0]->getPayload()['causer']);
        $this->assertEquals('users::update', $messages[0]->getTopic());

        $this->assertInstanceOf(MercureUpdateMessage::class, $messages[1]);
        $this->assertEquals('user_force_delete', $messages[1]->getPayload()['action']);
        $this->assertEquals($user['user']->getEmail(), $messages[1]->getPayload()['causer']);
        $this->assertEquals('user::update::'.$testUser['user']->getId(), $messages[1]->getTopic());

        $this->transport('async')->process(2);

        $this->transport('async')->rejected()->assertEmpty();
        $this->transport('async')->queue()->assertEmpty();
    }
}
