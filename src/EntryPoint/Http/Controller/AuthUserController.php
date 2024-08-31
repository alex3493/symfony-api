<?php
declare(strict_types=1);

namespace App\EntryPoint\Http\Controller;

use App\EntryPoint\Http\Contract\AbstractApiController;
use App\Module\Shared\Domain\Bus\Command\CommandBus;
use App\Module\User\Application\ChangePassword\ChangePasswordCommand;
use App\Module\User\Application\DeleteAppUser\DeleteAppUserCommand;
use App\Module\User\Application\LoginAppUser\LoginAppUserCommand;
use App\Module\User\Application\LogoutAppUserDevice\LogoutAppUserDeviceCommand;
use App\Module\User\Application\LogoutWebUser\LogoutWebUserCommand;
use App\Module\User\Application\RegisterAppUser\RegisterAppUserCommand;
use App\Module\User\Application\RegisterWebUser\RegisterWebUserCommand;
use App\Module\User\Application\SignOutAppUser\SignOutAppUserCommand;
use App\Module\User\Application\UpdateUserProfile\UpdateUserProfileCommand;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class AuthUserController extends AbstractApiController
{
    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \App\Module\Shared\Domain\Bus\Command\CommandBus $commandBus
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \App\Module\Shared\Domain\Exception\BadRequestDomainException
     */
    #[Route('/app/register', name: 'app-register', methods: ['POST'])]
    public function register(Request $request, CommandBus $commandBus): JsonResponse
    {
        $jsonData = $this->getRequestData($request, [
            'email',
            'password',
            ['passwordConfirmation', 'password_confirmation'],
            ['firstName', 'first_name'],
            ['lastName', 'last_name'],
            ['deviceName', 'device_name'],
        ], [
            'email',
            'password',
            'passwordConfirmation',
        ]);

        $command = new RegisterAppUserCommand($jsonData['email'], $jsonData['password'],
            $jsonData['passwordConfirmation'], $jsonData['firstName'], $jsonData['lastName'], $jsonData['deviceName']);

        $response = $commandBus->dispatch($command);

        $data = $this->serializer->serialize([
            'user' => $response->user,
            'token' => $response->token,
        ], 'json', ['groups' => ['user', 'user-tokens']]);

        return $this->jsonResponse($data);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \App\Module\Shared\Domain\Bus\Command\CommandBus $commandBus
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \App\Module\Shared\Domain\Exception\BadRequestDomainException
     */
    #[Route('/web/register', name: 'web-register', methods: ['POST'])]
    public function create(Request $request, CommandBus $commandBus): JsonResponse
    {
        $jsonData = $this->getRequestData($request, [
            'email',
            'password',
            ['passwordConfirmation', 'password_confirmation'],
            ['firstName', 'first_name'],
            ['lastName', 'last_name'],
        ], [
            'email',
            'password',
            'passwordConfirmation',
        ]);

        $command = new RegisterWebUserCommand($jsonData['email'], $jsonData['password'],
            $jsonData['passwordConfirmation'], $jsonData['firstName'], $jsonData['lastName']);

        $response = $commandBus->dispatch($command);

        $data = $this->serializer->serialize([
            'user' => $response->user,
        ], 'json', ['groups' => ['user']]);

        return $this->jsonResponse($data);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \App\Module\Shared\Domain\Bus\Command\CommandBus $commandBus
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \App\Module\Shared\Domain\Exception\BadRequestDomainException
     */
    #[Route('/app/login', name: 'login', methods: ['POST'])]
    public function login(Request $request, CommandBus $commandBus): JsonResponse
    {
        $jsonData = $this->getRequestData($request, [
            'email',
            'password',
            ['deviceName', 'device_name'],
        ], [
            'email',
            'password',
        ]);

        $command = new LoginAppUserCommand($jsonData['email'], $jsonData['password'], $jsonData['deviceName']);

        $response = $commandBus->dispatch($command);

        $data = $this->serializer->serialize([
            'user' => $response->user,
            'token' => $response->token,
        ], 'json', ['groups' => ['user', 'user-tokens']]);

        return $this->jsonResponse($data);
    }

    /**
     * @param string $tokenId
     * @param \App\Module\Shared\Domain\Bus\Command\CommandBus $commandBus
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    #[Route('/app/account/logout/{tokenId}', name: 'app-logout', methods: ['DELETE'])]
    #[Route('/web/account/logout/{tokenId}', name: 'web-logout', methods: ['DELETE'])]
    public function logout(string $tokenId, CommandBus $commandBus): JsonResponse
    {
        $command = new LogoutAppUserDeviceCommand($tokenId);

        $response = $commandBus->dispatch($command);

        // Reserved: we can reinit auth tokens collection before normalizing. For now, we
        // do it in \App\Module\User\Domain\User::getAuthTokens getter.
        //$authTokens = $response->user->getAuthTokens()->toArray();
        //$response->user->importAuthTokens($authTokens);

        $data = $this->serializer->serialize([
            'user' => $response->user,
        ], 'json', ['groups' => ['user', 'user-tokens']]);

        return $this->jsonResponse($data);
    }

    /**
     * @param \App\Module\Shared\Domain\Bus\Command\CommandBus $commandBus
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \App\Module\Shared\Domain\Exception\AccessDeniedDomainException
     */
    #[Route('/app/account/me/sign-out', name: 'me.app-sign-out', methods: ['POST'])]
    #[Route('/web/account/me/sign-out', name: 'me.web-sign-out', methods: ['POST'])]
    public function signOut(CommandBus $commandBus): JsonResponse
    {
        $userId = $this->ensureCurrentUserId();

        $command = new SignOutAppUserCommand($userId);

        $response = $commandBus->dispatch($command);

        $data = $this->serializer->serialize([
            'user' => $response->user,
        ], 'json', ['groups' => ['user', 'user-tokens']]);

        return $this->jsonResponse($data);
    }

    /**
     * @param \App\Module\Shared\Domain\Bus\Command\CommandBus $commandBus
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \App\Module\Shared\Domain\Exception\AccessDeniedDomainException
     */
    #[Route('/web/account/me/logout', name: 'me.web-logout', methods: ['POST'])]
    public function webLogout(CommandBus $commandBus): JsonResponse
    {
        $userId = $this->ensureCurrentUserId();

        $command = new LogoutWebUserCommand($userId);

        $response = $commandBus->dispatch($command);

        $data = $this->serializer->serialize($response, 'json');

        return $this->jsonResponse($data);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \App\Module\Shared\Domain\Bus\Command\CommandBus $commandBus
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \App\Module\Shared\Domain\Exception\AccessDeniedDomainException
     * @throws \App\Module\Shared\Domain\Exception\BadRequestDomainException
     */
    #[Route('/app/account/me/update', name: 'me.app-update', methods: ['PATCH'])]
    #[Route('/web/account/me/update', name: 'me.web-update', methods: ['PATCH'])]
    public function update(Request $request, CommandBus $commandBus): JsonResponse
    {
        $userId = $this->ensureCurrentUserId();

        $jsonData = $this->getRequestData($request, [
            'email',
            ['firstName', 'first_name'],
            ['lastName', 'last_name'],
        ], [
            'email',
        ]);

        $command = new UpdateUserProfileCommand($userId, $jsonData['email'], $jsonData['firstName'],
            $jsonData['lastName']);

        $response = $commandBus->dispatch($command);

        $data = $this->serializer->serialize([
            'user' => $response->user,
        ], 'json', ['groups' => ['user']]);

        return $this->jsonResponse($data);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \App\Module\Shared\Domain\Bus\Command\CommandBus $commandBus
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \App\Module\Shared\Domain\Exception\AccessDeniedDomainException
     * @throws \App\Module\Shared\Domain\Exception\BadRequestDomainException
     */
    #[Route('/app/account/me/change-password', name: 'me.app-change-password', methods: ['PATCH'])]
    #[Route('/web/account/me/change-password', name: 'me.web-change-password', methods: ['PATCH'])]
    public function changePassword(Request $request, CommandBus $commandBus): JsonResponse
    {
        $userId = $this->ensureCurrentUserId();

        $jsonData = $this->getRequestData($request, [
            ['currentPassword', 'current_password'],
            'password',
            ['passwordConfirmation', 'password_confirmation'],
        ], [
            'currentPassword',
            'password',
            'passwordConfirmation',
        ]);

        $command = new ChangePasswordCommand($userId, $jsonData['currentPassword'], $jsonData['password'],
            $jsonData['passwordConfirmation']);

        $response = $commandBus->dispatch($command);

        $data = $this->serializer->serialize([
            'user' => $response->user,
        ], 'json', ['groups' => ['user']]);

        return $this->jsonResponse($data);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \App\Module\Shared\Domain\Bus\Command\CommandBus $commandBus
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \App\Module\Shared\Domain\Exception\AccessDeniedDomainException
     * @throws \App\Module\Shared\Domain\Exception\BadRequestDomainException
     */
    #[Route('/app/account/me/delete-account', name: 'me.app-delete-account', methods: ['POST'])]
    #[Route('/web/account/me/delete-account', name: 'me.web-delete-account', methods: ['POST'])]
    public function deleteAccount(Request $request, CommandBus $commandBus): JsonResponse
    {
        $userId = $this->ensureCurrentUserId();

        $jsonData = $this->getRequestData($request, [
            'password',
        ], [
            'password',
        ]);

        $command = new DeleteAppUserCommand($userId, $jsonData['password']);

        $response = $commandBus->dispatch($command);

        $data = $this->serializer->serialize($response, 'json');

        return $this->jsonResponse($data);
    }
}
