<?php
declare(strict_types=1);

namespace App\EntryPoint\Http\Controller;

use App\EntryPoint\Http\Contract\AbstractApiController;
use App\Module\Shared\Domain\Bus\Command\CommandBus;
use App\Module\Shared\Domain\Bus\Query\QueryBus;
use App\Module\Shared\Domain\Exception\BadRequestDomainException;
use App\Module\User\Application\Admin\AdminCreateUser\AdminCreateUserCommand;
use App\Module\User\Application\Admin\AdminForceDeleteUser\AdminForceDeleteUserCommand;
use App\Module\User\Application\Admin\AdminRestoreUser\AdminRestoreUserCommand;
use App\Module\User\Application\Admin\AdminSoftDeleteUser\AdminSoftDeleteUserCommand;
use App\Module\User\Application\Admin\AdminUpdateUser\AdminUpdateUserCommand;
use App\Module\User\Application\Admin\AdminUserList\AdminUserListQuery;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin')]
class AdminUserController extends AbstractApiController
{
    /**
     * @param Request $request
     * @param QueryBus $queryBus
     * @return JsonResponse
     */
    #[Route('/users', name: 'admin-user-list', methods: ['GET'])]
    public function index(Request $request, QueryBus $queryBus): JsonResponse
    {
        $query = new AdminUserListQuery($request->query->getInt('page', 1), $request->query->getInt('limit', 15),
            $request->query->getString('orderBy', 'name'), $request->query->getString('orderType', 'ASC'),
            $request->query->getBoolean('withDeleted', false));

        $response = $queryBus->ask($query);

        $data = $this->serializer->serialize([
            'items' => $response->items,
            'totalPages' => $response->totalPages,
            'totalItems' => $response->totalItems,
        ], 'json', ['groups' => ['user', 'user-tokens']]);

        return $this->jsonResponse($data);
    }

    /**
     * @param Request $request
     * @param CommandBus $commandBus
     * @return JsonResponse
     * @throws BadRequestDomainException
     */
    #[Route('/users', name: 'admin-user-create', methods: ['POST'])]
    public function create(Request $request, CommandBus $commandBus): JsonResponse
    {
        $jsonData = $this->getRequestData($request, [
            'email',
            'password',
            ['firstName', 'first_name'],
            ['lastName', 'last_name'],
            'role',
        ], [
            'email',
            'password',
        ]);

        $command = new AdminCreateUserCommand($jsonData['email'], $jsonData['password'], $jsonData['firstName'],
            $jsonData['lastName'], $jsonData['role'] ? [$jsonData['role']] : []);

        $response = $commandBus->dispatch($command);

        $data = $this->serializer->serialize([
            'user' => $response->user,
        ], 'json', ['groups' => ['user']]);

        return $this->jsonResponse($data);
    }

    /**
     * @param Request $request
     * @param string $userId
     * @param CommandBus $commandBus
     * @return JsonResponse
     * @throws BadRequestDomainException
     */
    #[Route('/user/{userId}', name: 'admin-user-update', methods: ['PATCH'])]
    public function update(Request $request, string $userId, CommandBus $commandBus): JsonResponse
    {
        $jsonData = $this->getRequestData($request, [
            'email',
            'password',
            ['firstName', 'first_name'],
            ['lastName', 'last_name'],
            'role',
        ], [
            'email',
        ]);

        $command = new AdminUpdateUserCommand($userId, $jsonData['email'], $jsonData['password'],
            $jsonData['firstName'], $jsonData['lastName'], $jsonData['role'] ? [$jsonData['role']] : []);

        $response = $commandBus->dispatch($command);

        $data = $this->serializer->serialize([
            'user' => $response->user,
        ], 'json', ['groups' => ['user']]);

        return $this->jsonResponse($data);
    }

    /**
     * @param string $userId
     * @param CommandBus $commandBus
     * @return JsonResponse
     * @throws \App\Module\Shared\Domain\Exception\AccessDeniedDomainException
     * @throws BadRequestDomainException
     */
    #[Route('/user/delete/{userId}', name: 'admin-user-soft-delete', methods: ['PATCH'])]
    public function softDelete(string $userId, CommandBus $commandBus): JsonResponse
    {
        $currentUserId = $this->ensureCurrentUserId();

        if ($currentUserId === $userId) {
            throw new BadRequestDomainException('User cannot soft-delete self');
        }

        $command = new AdminSoftDeleteUserCommand($userId);

        $response = $commandBus->dispatch($command);

        $data = $this->serializer->serialize([
            'user' => $response->user,
        ], 'json', ['groups' => ['user']]);

        return $this->jsonResponse($data);
    }

    /**
     * @param string $userId
     * @param CommandBus $commandBus
     * @return JsonResponse
     * @throws \App\Module\Shared\Domain\Exception\AccessDeniedDomainException
     * @throws BadRequestDomainException
     */
    #[Route('/user/restore/{userId}', name: 'admin-user-restore', methods: ['PATCH'])]
    public function restore(string $userId, CommandBus $commandBus): JsonResponse
    {
        $currentUserId = $this->ensureCurrentUserId();

        if ($currentUserId === $userId) {
            throw new BadRequestDomainException('User cannot soft-delete self');
        }

        $command = new AdminRestoreUserCommand($userId);

        $response = $commandBus->dispatch($command);

        $data = $this->serializer->serialize([
            'user' => $response->user,
        ], 'json', ['groups' => ['user']]);

        return $this->jsonResponse($data);
    }

    /**
     * @param string $userId
     * @param CommandBus $commandBus
     * @return JsonResponse
     * @throws \App\Module\Shared\Domain\Exception\AccessDeniedDomainException
     * @throws BadRequestDomainException
     */
    #[Route('/user/{userId}', name: 'admin-user-force-delete', methods: ['DELETE'])]
    public function forceDelete(string $userId, CommandBus $commandBus): JsonResponse
    {
        $currentUserId = $this->ensureCurrentUserId();

        if ($currentUserId === $userId) {
            throw new BadRequestDomainException('User cannot soft-delete self');
        }

        $command = new AdminForceDeleteUserCommand($userId);

        $response = $commandBus->dispatch($command);

        return $this->json($response);
    }
}
